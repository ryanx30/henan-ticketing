<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $status   = (string) $request->query('status', 'all');
        $priority = (string) $request->query('priority', 'all');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo   = (string) $request->query('date_to', '');

        $query = Ticket::query()
            ->with(['creator', 'holder'])
            ->latest();

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('ticket_code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%");

                if (ctype_digit($q)) {
                    $qq->orWhere('id', (int) $q);
                }
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $tickets = $query->paginate(10)->withQueryString();

        return $this->paginated($tickets, 'Tickets loaded');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['creator', 'holder', 'statusHistories', 'resolverMessages']);

        return $this->success($ticket, 'Ticket detail loaded');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority'    => ['required', 'in:low,medium,high,critical'],
            'team'        => ['required', 'in:it,finance,compliance'],
            'category'    => ['required', 'string', 'max:50'],
            'issue_type'  => ['required', 'string', 'max:80'],
        ]);

        $priorityMap = [
            'critical' => '10',
            'high'     => '20',
            'medium'   => '30',
            'low'      => '40',
        ];
        $prefix = '10' . $priorityMap[$validated['priority']];

        $slaHoursByPriority = [
            'critical' => 2,
            'high'     => 6,
            'medium'   => 12,
            'low'      => 24,
        ];
        $slaHours = $slaHoursByPriority[$validated['priority']] ?? null;

        $status = $validated['team'] === 'it' ? 'new' : 'closed';

        $ticket = DB::transaction(function () use ($request, $validated, $prefix, $slaHours, $status) {
            $lastCode = Ticket::where('ticket_code', 'like', $prefix . '%')
                ->orderBy('ticket_code', 'desc')
                ->value('ticket_code');

            $nextSeq = 10;
            if ($lastCode) {
                $lastSeqStr = substr($lastCode, strlen($prefix));
                $nextSeq = ((int) $lastSeqStr) + 1;
            }

            $seqPart = str_pad((string) $nextSeq, 2, '0', STR_PAD_LEFT);
            $ticketCode = $prefix . $seqPart;

            $slaDeadline = null;
            if ($validated['team'] === 'it' && $slaHours) {
                $slaDeadline = now()->addHours($slaHours);
            }

            $ticket = Ticket::create([
                'ticket_code'     => $ticketCode,
                'title'           => $validated['title'],
                'description'     => $validated['description'],
                'priority'        => $validated['priority'],
                'team'            => $validated['team'],
                'status'          => $status,
                'created_by'      => $request->user()->id,
                'sla_deadline_at' => $slaDeadline,
                'category'        => $validated['category'],
                'issue_type'      => $validated['issue_type'],
                'resolved_at'     => $status === 'resolved' ? now() : null,
                'closed_at'       => $status === 'closed' ? now() : null,
            ]);

            TicketStatusHistory::create([
                'ticket_id'   => $ticket->id,
                'from_status' => null,
                'to_status'   => $status,
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
                'note'        => 'Initial status on ticket creation',
            ]);

            return $ticket->load(['creator', 'holder']);
        });

        return $this->success($ticket, 'Ticket created successfully', 201);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority'    => ['required', 'in:low,medium,high,critical'],
            'team'        => ['required', 'in:it,finance,compliance'],
            'status'      => ['required', 'in:new,in_progress,waiting_info,resolved,closed'],
            'category'    => ['required', 'string', 'max:50'],
            'issue_type'  => ['required', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($request, $ticket, $validated) {
            $oldStatus = $ticket->status;
            $newTeam   = $validated['team'];
            $newStatus = $validated['status'];

            if ($newTeam !== 'it') {
                $newStatus = 'closed';
                $ticket->sla_deadline_at = null;
                $ticket->holder_id = null;
                $ticket->claimed_at = null;
            }

            $ticket->fill([
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'priority'    => $validated['priority'],
                'team'        => $newTeam,
                'status'      => $newStatus,
                'category'    => $validated['category'],
                'issue_type'  => $validated['issue_type'],
            ]);

            if ($newStatus === 'resolved' && !$ticket->resolved_at) {
                $ticket->resolved_at = now();
            }

            if ($newStatus === 'closed' && !$ticket->closed_at) {
                $ticket->closed_at = now();
            }

            $ticket->save();

            if ($oldStatus !== $newStatus) {
                TicketStatusHistory::create([
                    'ticket_id'   => $ticket->id,
                    'from_status' => $oldStatus,
                    'to_status'   => $newStatus,
                    'changed_by'  => $request->user()->id,
                    'changed_at'  => now(),
                    'note'        => 'Status updated from API',
                ]);
            }
        });

        return $this->success(
            $ticket->fresh(['creator', 'holder']),
            'Ticket updated successfully'
        );
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return $this->success(null, 'Ticket deleted successfully');
    }

    public function similar(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        if ($keyword === '') {
            return $this->success([], 'No keyword');
        }

        $data = Ticket::query()
            ->where('title', 'like', "%{$keyword}%")
            ->orWhere('description', 'like', "%{$keyword}%")
            ->latest()
            ->take(5)
            ->get(['id', 'ticket_code', 'title', 'status', 'priority']);

        return $this->success($data, 'Similar tickets loaded');
    }

    public function clientHistory(Request $request)
    {
        $createdBy = $request->query('created_by');

        if (!$createdBy) {
            return $this->success([], 'No creator selected');
        }

        $data = Ticket::query()
            ->where('created_by', $createdBy)
            ->latest()
            ->take(10)
            ->get(['id', 'ticket_code', 'title', 'status', 'priority', 'created_at']);

        return $this->success($data, 'Client history loaded');
    }
}
