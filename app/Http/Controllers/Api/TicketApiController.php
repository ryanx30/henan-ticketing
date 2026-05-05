<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Services\TicketService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class TicketApiController extends BaseApiController
{
    public function __construct(
        private TicketService $ticketService
    ) {
    }

    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $status   = (string) $request->query('status', 'all');
        $priority = (string) $request->query('priority', 'all');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo   = (string) $request->query('date_to', '');
        $focus    = (string) $request->query('focus', '');
        $sortBy   = (string) $request->query('sort_by', 'created_at');
        $sortDir  = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage  = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $query = Ticket::query()->with(['creator', 'holder']);

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

        $allowedSorts = ['ticket_code', 'title', 'priority', 'category', 'team', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        switch ($sortBy) {
            case 'priority':
                $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') " . $sortDir);
                break;

            case 'status':
                $query->orderByRaw("FIELD(status, 'new', 'in_progress', 'waiting_info', 'resolved', 'closed') " . $sortDir);
                break;

            default:
                $query->orderBy($sortBy, $sortDir);
                break;
        }

        if ($sortBy !== 'created_at') {
            $query->orderByDesc('created_at');
        }

        $tickets = $query->paginate($perPage)->withQueryString();

        return $this->paginated($tickets, 'Tickets loaded');
    }

    public function show(Request $request, Ticket $ticket)
    {
        $ticket->load([
            'creator',
            'holder',
            'client',
            'attachments',
            'statusHistories.changer',
            'resolverMessages' => function ($query) {
                $query->with(['sender', 'recipient'])
                    ->latest();
            },
        ]);

        $data = $ticket->toArray();
        $data['viewer_role'] = $request->user()->role;
        $data['viewer_id'] = $request->user()->id;

        return $this->success($data, 'Ticket detail loaded');
    }

    /**
     * Read-only options for Create/Edit Ticket form.
     */
    public function formOptions(Request $request)
    {
        return $this->success([
            'teams' => Team::query()
                ->where('is_active', true)
                ->orderBy('code_num')
                ->get(['id', 'code_num', 'name', 'code']),

            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('code_num')
                ->get(['id', 'code_num', 'name', 'slug']),

            'priorities' => Priority::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code_num')
                ->get(['id', 'code_num', 'name', 'code']),
        ], 'Ticket form options loaded');
    }

    /**
     * Read-only issue types for selected category.
     */
    public function issueTypesByCategory(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $rows = IssueType::query()
            ->where('category_id', $validated['category_id'])
            ->where('is_active', true)
            ->orderBy('code_num')
            ->get(['id', 'category_id', 'code_num', 'name', 'slug']);

        return $this->success($rows, 'Issue types loaded');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string'],
            'team_id'         => ['required', 'integer', 'exists:teams,id'],
            'category_id'     => ['required', 'integer', 'exists:categories,id'],
            'issue_type_id'   => ['required', 'integer', 'exists:issue_types,id'],
            'priority_id'     => ['required', 'integer', 'exists:priorities,id'],
            'client_id'       => ['nullable', 'integer', 'exists:clients,id'],
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

        $ticket = $this->ticketService->create(
            $validated,
            $request->user(),
            $request->file('attachment')
        );

        AuditLogger::record(
            $request,
            'created',
            'ticket',
            $ticket->id,
            AuditLogger::ticketLabel($ticket),
            'Created ticket ' . AuditLogger::ticketLabel($ticket) . ': ' . $ticket->title,
            null,
            $this->ticketService->snapshot($ticket)
        );

        return $this->success($ticket, 'Ticket created successfully', 201);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string'],
            'status'          => ['required', 'in:new,in_progress,waiting_info,resolved,closed'],
            'team_id'         => ['required', 'integer', 'exists:teams,id'],
            'category_id'     => ['required', 'integer', 'exists:categories,id'],
            'issue_type_id'   => ['required', 'integer', 'exists:issue_types,id'],
            'priority_id'     => ['required', 'integer', 'exists:priorities,id'],
        ]);

        $before = $this->ticketService->snapshot($ticket);
        $freshTicket = $this->ticketService->update($ticket, $validated, $request->user());

        AuditLogger::record(
            $request,
            'updated',
            'ticket',
            $freshTicket->id,
            AuditLogger::ticketLabel($freshTicket),
            'Updated ticket ' . AuditLogger::ticketLabel($freshTicket) . ': ' . $freshTicket->title,
            $before,
            $this->ticketService->snapshot($freshTicket)
        );

        return $this->success(
            $freshTicket,
            'Ticket updated successfully'
        );
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        $before = $this->ticketService->snapshot($ticket);
        $ticketLabel = AuditLogger::ticketLabel($ticket);
        $title = $ticket->title;

        $ticket->delete();

        AuditLogger::record(
            $request,
            'deleted',
            'ticket',
            $ticket->id,
            $ticketLabel,
            'Deleted ticket ' . $ticketLabel . ': ' . $title,
            $before,
            null
        );

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
