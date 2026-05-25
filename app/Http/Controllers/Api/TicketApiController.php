<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistory;
use App\Http\Resources\TicketDetailResource;
use App\Http\Resources\TicketResource;
use App\Queries\TicketIndexQuery;
use App\Queries\SimilarTicketQuery;
use App\Support\TicketStatus;
use App\Services\DashboardCacheService;
use App\Services\TicketService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketApiController extends BaseApiController
{
    public function __construct(
        private TicketService $ticketService,
        private DashboardCacheService $dashboardCache,
        private TicketIndexQuery $ticketIndexQuery,
        private SimilarTicketQuery $similarTicketQuery
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $tickets = $this->ticketIndexQuery
            ->build($request, $request->user())
            ->paginate($perPage)
            ->withQueryString();

        $tickets->setCollection(
            TicketResource::collection($tickets->getCollection())->collection
        );

        return $this->paginated($tickets, 'Tickets loaded');
    }

    public function show(Request $request, Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $ticket->load([
            'creator',
            'holder',
            'client',
            'teamMaster',
            'categoryMaster',
            'issueTypeMaster',
            'priorityMaster',
        ]);

        $historyPerPage = $this->boundedPerPage($request, 'history_per_page', 10, 50);
        $messagesPerPage = $this->boundedPerPage($request, 'messages_per_page', 10, 50);
        $attachmentsPerPage = $this->boundedPerPage($request, 'attachments_per_page', 10, 50);

        // Heavy relations are paginated separately to keep the ticket detail payload stable.
        $history = $ticket->statusHistories()
            ->with('changer')
            ->latest('changed_at')
            ->paginate($historyPerPage, ['*'], 'history_page');

        $messages = $ticket->resolverMessages()
            ->with(['sender', 'recipient'])
            ->latest()
            ->paginate($messagesPerPage, ['*'], 'messages_page');

        $attachments = $ticket->attachments()
            ->with('uploader')
            ->latest()
            ->paginate($attachmentsPerPage, ['*'], 'attachments_page');

        $data = (new TicketDetailResource($ticket))->toArray($request);
        $data['viewer_role'] = $request->user()->role;
        $data['viewer_id'] = $request->user()->id;
        $data['relations'] = [
            'history' => $this->relationPage($history),
            'messages' => $this->relationPage($messages),
            'attachments' => $this->attachmentRelationPage($attachments, $ticket),
        ];

        return $this->success($data, 'Ticket detail loaded');
    }

    /**
     * Read-only options untuk Create/Edit Ticket form.
     */
    public function formOptions(Request $request)
    {
        Gate::authorize('create', Ticket::class);

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
     * Issue types berdasarkan category yang dipilih.
     */
    public function issueTypesByCategory(Request $request)
    {
        Gate::authorize('create', Ticket::class);

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
        Gate::authorize('create', Ticket::class);

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string'],
            'team_id'        => ['required', 'integer', 'exists:teams,id'],
            'category_id'    => ['required', 'integer', 'exists:categories,id'],
            'issue_type_id'  => ['required', 'integer', 'exists:issue_types,id'],
            'priority_id'    => ['required', 'integer', 'exists:priorities,id'],
            'client_id'      => ['nullable', 'integer', 'exists:clients,id'],
            'client_name'    => ['nullable', 'string', 'max:255'],
            'client_contact' => ['nullable', 'string', 'max:100'],
            'client_email'   => ['nullable', 'email', 'max:255'],
            'platform_type'  => ['nullable', 'string', 'max:50'],
            'amount'         => ['nullable', 'string', 'max:100'],
            'flow_type'      => ['nullable', 'string', 'max:50'],
            'request_time'   => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string'],
            'attachment'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt', 'max:5120'],
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

        $this->dashboardCache->invalidate();

        return $this->success(TicketResource::make($ticket), 'Ticket created successfully', 201);
    }

    public function update(Request $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);

        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['required', 'string'],
            'status'        => ['required', TicketStatus::validationRule()],
            'team_id'       => ['required', 'integer', 'exists:teams,id'],
            'category_id'   => ['required', 'integer', 'exists:categories,id'],
            'issue_type_id' => ['required', 'integer', 'exists:issue_types,id'],
            'priority_id'   => ['required', 'integer', 'exists:priorities,id'],
        ]);

        $before      = $this->ticketService->snapshot($ticket);
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

        $this->dashboardCache->invalidate();

        return $this->success(
            TicketResource::make($freshTicket),
            'Ticket updated successfully'
        );
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        Gate::authorize('delete', $ticket);

        $before      = $this->ticketService->snapshot($ticket);
        $ticketLabel = AuditLogger::ticketLabel($ticket);
        $title       = $ticket->title;

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

        $this->dashboardCache->invalidate();

        return $this->success(null, 'Ticket deleted successfully');
    }


    public function downloadAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        if ((int) $attachment->ticket_id !== (int) $ticket->id) {
            abort(404);
        }

        Gate::authorize('view', $attachment);

        $disk = Storage::disk('local')->exists($attachment->file_path)
            ? 'local'
            : (Storage::disk('public')->exists($attachment->file_path) ? 'public' : null);

        if ($disk === null) {
            abort(404, 'Attachment file not found.');
        }

        return Storage::disk($disk)->download(
            $attachment->file_path,
            $attachment->file_name,
            ['Content-Type' => $attachment->file_type ?: 'application/octet-stream']
        );
    }

    public function similar(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $keyword = trim((string) $request->query('q', ''));

        if ($keyword === '') {
            return $this->success([], 'No keyword');
        }

        $probe = new Ticket([
            'title' => $keyword,
            'team' => trim((string) $request->query('team', '')),
            'category' => trim((string) $request->query('category', '')),
        ]);
        $probe->created_at = now();

        $tickets = $this->similarTicketQuery->forTicket($probe, $request->user());

        return $this->success(TicketResource::collection($tickets), 'Similar tickets loaded');
    }

    public function similarByTicket(Request $request, Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $tickets = $this->similarTicketQuery->forTicket($ticket, $request->user());

        return $this->success(TicketResource::collection($tickets), 'Similar tickets loaded');
    }

    public function clientHistory(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $createdBy = $request->query('created_by');

        if (!$createdBy) {
            return $this->success([], 'No creator selected');
        }

        $query = Ticket::query();
        $this->scopeTicketsForViewer($query, $request);

        $data = $query
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
    /**
     * Scope list endpoints by resource-level visibility, not just role middleware.
     */
    private function scopeTicketsForViewer($query, Request $request): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isSupervisor()) {
            return;
        }

        if ($user->isCS()) {
            $query->where('created_by', $user->id);
            return;
        }

        if ($user->isIT()) {
            $query->forTeamCode('it')
                ->where(function ($q) use ($user) {
                    $q->whereNull('holder_id')
                        ->orWhere('holder_id', $user->id);
                });
        }
    }

    /**
     * Clamp relation pagination values to prevent expensive payloads.
     */
    private function boundedPerPage(Request $request, string $key, int $default, int $max): int
    {
        $value = (int) $request->query($key, $default);

        if ($value <= 0) {
            return $default;
        }

        return min($value, $max);
    }


    private function attachmentRelationPage($paginator, Ticket $ticket): array
    {
        return [
            'data' => collect($paginator->items())->map(fn (TicketAttachment $attachment) => [
                'id' => $attachment->id,
                'ticket_id' => $attachment->ticket_id,
                'file_name' => $attachment->file_name,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
                'uploaded_by' => $attachment->uploaded_by,
                'uploader' => $attachment->relationLoaded('uploader') ? $attachment->uploader : null,
                'created_at' => optional($attachment->created_at)?->toISOString(),
                'download_url' => url('/api/tickets/' . $ticket->id . '/attachments/' . $attachment->id . '/download'),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ];
    }

    /**
     * Normalize paginator output for nested heavy relations.
     */
    private function relationPage($paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ];
    }

}
