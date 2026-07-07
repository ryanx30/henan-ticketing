<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\SlaRule;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Http\Resources\TicketIndexResource;
use App\Http\Resources\TicketResource;
use App\Queries\TicketIndexQuery;
use App\Queries\SimilarTicketQuery;
use App\Support\TicketStatus;
use App\Services\DashboardCacheService;
use App\Services\TicketService;
use App\Services\Tickets\TicketDetailPayloadService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles ticket listing, detail payloads, creation, updates, attachments, and related ticket lookups for the internal API.
 */
class TicketApiController extends BaseApiController
{
    public function __construct(
        private TicketService $ticketService,
        private DashboardCacheService $dashboardCache,
        private TicketIndexQuery $ticketIndexQuery,
        private SimilarTicketQuery $similarTicketQuery,
        private TicketDetailPayloadService $ticketDetailPayloadService
    ) {
    }

    // ========= LISTING AND DETAIL =========

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
            TicketIndexResource::collection($tickets->getCollection())->collection
        );

        return $this->paginated($tickets, 'Tickets loaded');
    }

    public function show(Request $request, Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        return $this->success(
            $this->ticketDetailPayloadService->build($ticket, $request),
            'Ticket detail loaded'
        );
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

            // Keep the frontend SLA preview aligned with Master Data.
            // TicketService uses the same team_id + priority_id + active rule when creating the actual SLA deadline.
            'sla_rules' => SlaRule::query()
                ->where('is_active', true)
                ->orderBy('team_id')
                ->orderBy('priority_id')
                ->get(['id', 'team_id', 'priority_id', 'hours', 'is_active']),
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

    // ========= CREATE AND UPDATE =========

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
            'client_contact' => ['nullable', 'string', 'regex:/^[0-9]{1,13}$/'],
            'client_email'   => ['nullable', 'string', 'max:255', 'regex:/@/'],
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

        return $this->createdResponse(TicketResource::make($ticket), 'Ticket created successfully');
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


    public function escalate(Request $request, Ticket $ticket)
    {
        Gate::authorize('transferHolder', $ticket);

        $actor = $request->user();

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = User::query()
            ->whereKey($validated['target_user_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $mode = $actor->isCsStaffOrHead() ? User::ROLE_CS : User::ROLE_IT;

        if ($actor->isAdmin()) {
            $mode = $ticket->isTeamCode('it') && $ticket->holder_id ? User::ROLE_IT : User::ROLE_CS;
        }

        if ($target->role !== $mode) {
            return $this->validationError([
                'target_user_id' => ['Ticket can only be handed off to another active user with the same operational role.'],
            ], 'Invalid handoff target.');
        }

        $ownerColumn = $mode === User::ROLE_CS ? 'created_by' : 'holder_id';
        $currentOwnerId = (int) ($ticket->{$ownerColumn} ?? 0);

        if ((int) $target->id === $currentOwnerId) {
            return $this->validationError([
                'target_user_id' => ['The selected user is already the current owner.'],
            ], 'The selected user is already the current owner.');
        }

        $freshTicket = DB::transaction(function () use ($request, $ticket, $actor, $target, $mode, $ownerColumn, $validated) {
            $lockedTicket = Ticket::query()
                ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $this->ticketService->snapshot($lockedTicket);
            $previousOwnerId = $lockedTicket->{$ownerColumn};
            $lockedTicket->{$ownerColumn} = $target->id;
            $lockedTicket->save();

            $note = $validated['note']
                ?: 'Ticket handoff from ' . ($mode === User::ROLE_CS ? 'CS owner' : 'IT holder') . ' to ' . $target->name . '.';

            TicketStatusHistory::create([
                'ticket_id' => $lockedTicket->id,
                'from_status' => $lockedTicket->status,
                'to_status' => $lockedTicket->status,
                'changed_by' => $actor->id,
                'changed_at' => now(),
                'note' => $note,
            ]);

            $lockedTicket = $lockedTicket->fresh(['creator', 'holder', 'teamMaster', 'priorityMaster']);

            AuditLogger::record(
                $request,
                'holder_transferred',
                'ticket',
                $lockedTicket->id,
                AuditLogger::ticketLabel($lockedTicket),
                'Transferred ' . ($mode === User::ROLE_CS ? 'CS owner' : 'IT holder') . ' for ' . AuditLogger::ticketLabel($lockedTicket) . ' to ' . $target->name,
                array_merge($before, [
                    'owner_column' => $ownerColumn,
                    'previous_owner_id' => $previousOwnerId,
                ]),
                array_merge($this->ticketService->snapshot($lockedTicket), [
                    'owner_column' => $ownerColumn,
                    'new_owner_id' => $target->id,
                ])
            );

            return $lockedTicket;
        });

        $this->dashboardCache->invalidate();

        return $this->success(
            $this->ticketDetailPayloadService->build($freshTicket, $request),
            'Ticket handoff completed successfully.'
        );
    }

    // ========= DESTRUCTIVE ACTIONS =========

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

        return $this->deletedResponse('Ticket deleted successfully');
    }


    public function downloadAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment): BinaryFileResponse
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

        $storage = Storage::disk($disk);

        return response()->download(
            $storage->path($attachment->file_path),
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
        $this->ticketIndexQuery->scopeForViewer($query, $request->user(), $request, true);

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

}
