<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ITQueueApiController extends BaseApiController
{
    public function myQueue(Request $request)
    {
        $tickets = Ticket::with(['creator', 'holder'])
            ->where('team', 'it')
            ->where('holder_id', $request->user()->id)
            ->whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->latest()
            ->paginate(10);

        return $this->paginated($tickets, 'My queue loaded');
    }

    public function teamQueue(Request $request)
    {
        $tickets = Ticket::with(['creator', 'holder'])
            ->where('team', 'it')
            ->whereIn('status', ['new', 'in_progress', 'waiting_info'])
            ->latest()
            ->paginate(10);

        return $this->paginated($tickets, 'Team queue loaded');
    }

    public function history(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $sortBy = (string) $request->query('sort_by', 'resolved_at');
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $allowedSorts = ['ticket_code', 'resolved_at', 'category', 'team', 'duration'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'resolved_at';
        }

        $query = Ticket::with(['creator', 'holder'])
            ->whereIn('status', ['resolved', 'closed']);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('ticket_code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('issue_type', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('team', 'like', "%{$q}%");
            });
        }

        // Effective history datetime:
        // resolved_at -> closed_at -> updated_at -> created_at
        $effectiveDateSql = "COALESCE(resolved_at, closed_at, updated_at, created_at)";

        if ($dateFrom !== '') {
            $query->whereRaw("DATE($effectiveDateSql) >= ?", [$dateFrom]);
        }

        if ($dateTo !== '') {
            $query->whereRaw("DATE($effectiveDateSql) <= ?", [$dateTo]);
        }

        switch ($sortBy) {
            case 'resolved_at':
                $query->orderByRaw("$effectiveDateSql $sortDir");
                break;

            case 'duration':
                $query->orderByRaw("TIMESTAMPDIFF(SECOND, created_at, COALESCE(resolved_at, closed_at, updated_at, created_at)) $sortDir");
                break;

            default:
                $query->orderBy($sortBy, $sortDir);
                break;
        }

        $tickets = $query->paginate($perPage)->withQueryString();

        return $this->paginated($tickets, 'History loaded');
    }

    public function claim(Request $request, Ticket $ticket)
    {
        if ($ticket->team !== 'it') {
            return $this->error('Only IT tickets can be claimed', 422);
        }

        DB::transaction(function () use ($request, $ticket) {
            $oldStatus = $ticket->status;

            $ticket->update([
                'holder_id' => $request->user()->id,
                'claimed_at' => now(),
                'status' => 'in_progress',
            ]);

            TicketStatusHistory::create([
                'ticket_id'   => $ticket->id,
                'from_status' => $oldStatus,
                'to_status'   => 'in_progress',
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
                'note'        => 'Ticket claimed by IT',
            ]);
        });

        return $this->success(
            $ticket->fresh(['creator', 'holder']),
            'Ticket claimed successfully'
        );
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,in_progress,waiting_info,resolved,closed'],
            'note' => ['nullable', 'string'],
        ]);

        $oldStatus = $ticket->status;

        DB::transaction(function () use ($request, $ticket, $validated, $oldStatus) {
            $ticket->status = $validated['status'];

            if ($validated['status'] === 'resolved' && !$ticket->resolved_at) {
                $ticket->resolved_at = now();
            }

            if ($validated['status'] === 'closed' && !$ticket->closed_at) {
                $ticket->closed_at = now();
            }

            $ticket->save();

            TicketStatusHistory::create([
                'ticket_id'   => $ticket->id,
                'from_status' => $oldStatus,
                'to_status'   => $validated['status'],
                'changed_by'  => $request->user()->id,
                'changed_at'  => now(),
                'note'        => $validated['note'] ?? 'Status updated by IT',
            ]);
        });

        return $this->success(
            $ticket->fresh(['creator', 'holder']),
            'Ticket status updated successfully'
        );
    }
}
