<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketAttachment;
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
        $focus    = (string) $request->query('focus', '');

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

        if ($focus !== '') {
            if ($focus === 'sla_risk') {
                $query->whereIn('status', ['new', 'in_progress', 'waiting_info'])
                    ->whereNotNull('sla_deadline_at')
                    ->whereBetween('sla_deadline_at', [now(), now()->copy()->addMinutes(59)]);
            } elseif ($focus === 'due_today') {
                $query->whereIn('status', ['new', 'in_progress', 'waiting_info'])
                    ->whereDate('sla_deadline_at', now()->toDateString());
            } elseif ($focus === 'reopened') {
                $ticketIds = TicketStatusHistory::query()
                    ->where('to_status', 'in_progress')
                    ->whereNotNull('from_status')
                    ->where('from_status', 'resolved')
                    ->whereDate('changed_at', now()->toDateString())
                    ->pluck('ticket_id');

                $query->whereIn('id', $ticketIds);
            }
        }

        $tickets = $query->paginate(10)->withQueryString();

        return $this->paginated($tickets, 'Tickets loaded');
    }

    public function show(Request $request, Ticket $ticket)
    {
        $ticket->load([
            'creator',
            'holder',
            'attachments',
            'statusHistories.changer',
            'resolverMessages',
        ]);

        $data = $ticket->toArray();
        $data['viewer_role'] = $request->user()->role;

        return $this->success($data, 'Ticket detail loaded');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string'],
            'priority'        => ['required', 'in:low,medium,high,critical'],
            'team'            => ['required', 'in:it,finance,compliance'],
            'category'        => ['required', 'string', 'max:50'],
            'issue_type'      => ['required', 'string', 'max:80'],

            'client_name'     => ['nullable', 'string', 'max:255'],
            'client_contact'  => ['nullable', 'string', 'max:100'],
            'client_email'    => ['nullable', 'email', 'max:255'],
            'platform_type'   => ['nullable', 'string', 'max:50'],
            'amount'          => ['nullable', 'string', 'max:100'],
            'flow_type'       => ['nullable', 'string', 'max:50'],
            'request_time'    => ['nullable', 'date'],
            'internal_notes'  => ['nullable', 'string'],

            'attachment'      => ['nullable', 'file', 'max:5120'],
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

                'client_name'     => $validated['client_name'] ?? null,
                'client_contact'  => $validated['client_contact'] ?? null,
                'client_email'    => $validated['client_email'] ?? null,
                'platform_type'   => $validated['platform_type'] ?? null,
                'amount'          => $validated['amount'] ?? null,
                'flow_type'       => $validated['flow_type'] ?? null,
                'request_time'    => $validated['request_time'] ?? null,
                'internal_notes'  => $validated['internal_notes'] ?? null,

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

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('ticket-attachments', 'public');

                TicketAttachment::create([
                    'ticket_id'   => $ticket->id,
                    'file_name'   => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'file_type'   => $file->getClientMimeType(),
                    'file_size'   => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }

            return $ticket->load(['creator', 'holder', 'attachments']);
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
            $ticket->fresh(['creator', 'holder', 'attachments']),
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
        $team = trim((string) $request->query('team', ''));
        $category = trim((string) $request->query('category', ''));

        if ($keyword === '') {
            return $this->success([], 'No keyword');
        }

        $query = Ticket::query();

        if ($team !== '') {
            $query->where('team', $team);
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $data = $query
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->latest()
            ->take(5)
            ->get([
                'id',
                'ticket_code',
                'title',
                'status',
                'priority',
                'category',
                'issue_type',
                'team',
                'created_at',
            ]);

        return $this->success($data, 'Similar tickets loaded');
    }

    public function similarByTicket(Ticket $ticket)
    {
        $words = collect(preg_split('/\s+/', trim((string) $ticket->title)))
            ->filter(fn ($word) => mb_strlen($word) >= 4)
            ->map(fn ($word) => mb_strtolower($word))
            ->unique()
            ->take(3)
            ->values();

        $tickets = Ticket::query()
            ->where('id', '!=', $ticket->id)
            ->where(function ($query) use ($ticket, $words) {
                $query->where('category', $ticket->category)
                    ->orWhere('issue_type', $ticket->issue_type);

                foreach ($words as $word) {
                    $query->orWhere('title', 'like', '%' . $word . '%');
                }
            })
            ->with(['creator', 'holder'])
            ->latest()
            ->get([
                'id',
                'ticket_code',
                'title',
                'status',
                'priority',
                'created_at',
                'category',
                'issue_type',
                'team',
                'created_by',
                'holder_id',
            ])
            ->map(function ($item) use ($ticket, $words) {
                $score = 0;

                if (
                    !empty($ticket->issue_type) &&
                    !empty($item->issue_type) &&
                    mb_strtolower($item->issue_type) === mb_strtolower($ticket->issue_type)
                ) {
                    $score += 3;
                }

                if (
                    !empty($ticket->category) &&
                    !empty($item->category) &&
                    mb_strtolower($item->category) === mb_strtolower($ticket->category)
                ) {
                    $score += 2;
                }

                if (
                    !empty($ticket->team) &&
                    !empty($item->team) &&
                    mb_strtolower($item->team) === mb_strtolower($ticket->team)
                ) {
                    $score += 1;
                }

                $title = mb_strtolower((string) $item->title);
                foreach ($words as $word) {
                    if (str_contains($title, $word)) {
                        $score += 2;
                    }
                }

                $item->similarity_score = $score;

                return $item;
            })
            ->filter(fn ($item) => $item->similarity_score > 0)
            ->sortByDesc('similarity_score')
            ->take(5)
            ->values();

        return $this->success($tickets, 'Similar tickets loaded');
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
            ->get([
                'id',
                'ticket_code',
                'title',
                'status',
                'priority',
                'created_at',
            ]);

        return $this->success($data, 'Client history loaded');
    }
}