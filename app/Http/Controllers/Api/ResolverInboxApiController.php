<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ResolverMessage;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages resolver messages, follow-up inbox data, message mutations, and resolver-specific API payloads.
 */
class ResolverInboxApiController extends BaseApiController
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', ResolverMessage::class);

        $user = $request->user();

        $unread   = $request->query('unread', 'all');
        $priority = $request->query('priority', 'all');
        $team     = $request->query('team', 'all');
        $date     = $request->query('date', 'all');

        $query = ResolverMessage::query()
            ->with(['ticket', 'sender', 'recipient'])
            ->whereHas('ticket', function ($q) {
                $q->where('status', '<>', 'closed');
            });

        $this->scopeInboxMessages($query, $user);

        if ($unread === 'unread') {
            $query->where('to_user_id', $user->id)
                ->where('is_read', false);
        }

        if ($priority !== 'all') {
            $query->whereHas('ticket', function ($q) use ($priority) {
                $q->forPriorityCode($priority);
            });
        }

        if ($team !== 'all') {
            $query->whereHas('ticket', function ($q) use ($team) {
                $q->forTeamCode($team);
            });
        }

        if ($date === 'today') {
            $query->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
        } elseif ($date === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($date === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $perPage = (int) $request->query('per_page', 10);
        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        // Paginate by ticket conversation, not by individual message. This keeps one
        // ticket as one inbox card even when the conversation already has many replies.
        $conversationPage = (clone $query)
            ->select('ticket_id')
            ->selectRaw('MAX(created_at) as latest_message_at')
            ->groupBy('ticket_id')
            ->orderByDesc('latest_message_at')
            ->paginate($perPage);

        $ticketIds = $conversationPage->getCollection()
            ->pluck('ticket_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $messages = collect();

        if ($ticketIds->isNotEmpty()) {
            $threadQuery = ResolverMessage::query()
                ->with(['ticket', 'sender', 'recipient'])
                ->whereIn('ticket_id', $ticketIds)
                ->whereHas('ticket', function ($q) {
                    $q->where('status', '<>', 'closed');
                });

            $this->scopeThreadMessages($threadQuery, $user);

            $messages = $threadQuery
                ->latest()
                ->get();
        }

        $composeTickets = Ticket::query()
            ->with(['holder', 'creator'])
            ->whereIn('status', ['in_progress', 'waiting_info']);
        $this->scopeComposeTickets($composeTickets, $user);
        $composeTickets = $composeTickets
            ->latest()
            ->limit(50)
            ->get();

        $composeRecipients = $this->getComposeRecipients($user);

        return $this->success(
            $messages->values(),
            'Resolver inbox loaded',
            200,
            [
                'meta' => [
                    'current_page' => $conversationPage->currentPage(),
                    'last_page' => $conversationPage->lastPage(),
                    'per_page' => $conversationPage->perPage(),
                    'total' => $conversationPage->total(),
                ],
                'extra' => [
                    'compose_tickets' => $composeTickets,
                    'compose_recipients' => $composeRecipients,
                ],
            ]
        );
    }

    public function show(Request $request, ResolverMessage $resolverMessage)
    {
        Gate::authorize('view', $resolverMessage);

        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isSupervisor()) {
            ResolverMessage::query()
                ->where('ticket_id', $resolverMessage->ticket_id)
                ->where('to_user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);
        } elseif ((int) $resolverMessage->to_user_id === (int) $user->id && ! $resolverMessage->is_read) {
            $resolverMessage->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        $resolverMessage->refresh();
        $resolverMessage->load(['ticket.creator', 'ticket.holder', 'sender', 'recipient']);

        $threadQuery = ResolverMessage::query()
            ->with(['ticket.creator', 'ticket.holder', 'sender', 'recipient'])
            ->where('ticket_id', $resolverMessage->ticket_id)
            ->oldest();

        $this->scopeThreadMessages($threadQuery, $user);

        $threadMessages = $threadQuery->get();

        return $this->success([
            'message' => $resolverMessage,
            'ticket' => $resolverMessage->ticket,
            'thread_messages' => $threadMessages,
        ], 'Resolver conversation loaded');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', ResolverMessage::class);

        $user = $request->user();

        $validated = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt', 'max:4096'],
        ]);

        $ticket = Ticket::query()
            ->with(['holder', 'creator'])
            ->where('status', '<>', 'closed')
            ->findOrFail($validated['ticket_id']);
        Gate::authorize('view', $ticket);

        if (! $this->canMessageTicket($ticket, $user)) {
            return $this->validationError([], 'Only the current CS owner or current IT holder can send resolver messages for this ticket.');
        }

        $toUserId = $this->resolveRecipientId($validated, $ticket, $user);

        if (!$toUserId) {
            return $this->validationError([], 'Recipient is required for this message.');
        }

        if ((int) $toUserId === (int) $user->id) {
            return $this->validationError([], 'You cannot send a resolver message to yourself.');
        }

        if (! $this->isAllowedRecipient($ticket, (int) $toUserId)) {
            return $this->validationError([], 'The selected recipient is not allowed for this ticket.');
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('resolver-messages', 'local');
            $attachmentName = $file->getClientOriginalName();
        }

        $message = ResolverMessage::create([
            'ticket_id' => $ticket->id,
            'from_user_id' => $user->id,
            'to_user_id' => $toUserId,
            'subject' => $validated['subject'] ?: 'Ticket update',
            'body' => $validated['body'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'is_read' => false,
        ]);

        $message->load(['ticket', 'sender', 'recipient']);

        AuditLogger::record(
            $request,
            'sent',
            'resolver_message',
            $message->id,
            $message->subject,
            'Sent resolver message for ' . AuditLogger::ticketLabel($ticket),
            null,
            $this->snapshotMessage($message)
        );

        return $this->createdResponse(
            $message,
            'Message sent successfully'
        );
    }

    public function downloadAttachment(Request $request, ResolverMessage $resolverMessage): BinaryFileResponse|StreamedResponse
    {
        Gate::authorize('view', $resolverMessage);

        abort_if(! $resolverMessage->attachment_path || ! $resolverMessage->attachment_name, 404, 'Attachment was not found.');

        $path = ltrim(str_replace('\\', '/', $resolverMessage->attachment_path), '/');
        abort_if(str_contains($path, '..'), 422, 'Invalid attachment path.');
        abort_if(! str_starts_with($path, 'resolver-messages/'), 422, 'Invalid attachment path.');

        if (Storage::disk('local')->exists($path)) {
            return response()->download(
                Storage::disk('local')->path($path),
                basename($resolverMessage->attachment_name)
            );
        }

        // Backward-compatible fallback for older resolver attachments that were
        // previously stored on the public disk before this privacy hardening.
        abort_if(! Storage::disk('public')->exists($path), 404, 'Attachment file was not found.');

        return response()->download(
            Storage::disk('public')->path($path),
            basename($resolverMessage->attachment_name)
        );
    }

    public function markAsRead(Request $request, ResolverMessage $resolverMessage)
    {
        Gate::authorize('markAsRead', $resolverMessage);

        $before = $this->snapshotMessage($resolverMessage);

        $resolverMessage->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        AuditLogger::record(
            $request,
            'read',
            'resolver_message',
            $resolverMessage->id,
            $resolverMessage->subject,
            'Marked resolver message as read',
            $before,
            $this->snapshotMessage($resolverMessage)
        );

        return $this->success($resolverMessage, 'Message marked as read');
    }

    public function destroy(Request $request, ResolverMessage $resolverMessage)
    {
        Gate::authorize('delete', $resolverMessage);

        $before = $this->snapshotMessage($resolverMessage);
        $subject = $resolverMessage->subject;

        if ($resolverMessage->attachment_path) {
            Storage::disk('local')->delete($resolverMessage->attachment_path);
            Storage::disk('public')->delete($resolverMessage->attachment_path);
        }

        $resolverMessage->delete();

        AuditLogger::record(
            $request,
            'deleted',
            'resolver_message',
            $resolverMessage->id,
            $subject,
            'Deleted resolver message: ' . $subject,
            $before,
            null
        );

        return $this->deletedResponse('Message deleted');
    }


    protected function scopeInboxMessages(Builder $query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor() || $user->isHeadCS()) {
            return;
        }

        $query->where(function ($query) use ($user) {
            $query->where('to_user_id', $user->id)
                ->orWhere('from_user_id', $user->id);
        });
    }


    protected function scopeThreadMessages(Builder $query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor() || $user->isHeadCS()) {
            return;
        }

        $query->where(function ($query) use ($user) {
            $query->where('from_user_id', $user->id)
                ->orWhere('to_user_id', $user->id);
        });
    }

    protected function canMessageTicket(Ticket $ticket, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isHeadCS()) {
            return true;
        }

        if ($user->isCS()) {
            return (int) $ticket->created_by === (int) $user->id;
        }

        if ($user->isIT()) {
            return (int) $ticket->holder_id === (int) $user->id;
        }

        return false;
    }

    protected function resolveRecipientId(array $validated, Ticket $ticket, User $user): ?int
    {
        if (! empty($validated['to_user_id'])) {
            return (int) $validated['to_user_id'];
        }

        if ($user->isIT()) {
            return $ticket->created_by ? (int) $ticket->created_by : null;
        }

        return $ticket->holder_id ? (int) $ticket->holder_id : null;
    }

    protected function isAllowedRecipient(Ticket $ticket, int $recipientId): bool
    {
        $participantIds = collect([
            $ticket->created_by,
            $ticket->holder_id,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id);

        if ($participantIds->contains($recipientId)) {
            return true;
        }

        return User::query()
            ->whereKey($recipientId)
            ->where('is_active', true)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_HEAD_CS])
            ->exists();
    }

    protected function snapshotMessage(ResolverMessage $message): array
    {
        return [
            'id' => $message->id,
            'ticket_id' => $message->ticket_id,
            'from_user_id' => $message->from_user_id,
            'to_user_id' => $message->to_user_id,
            'subject' => $message->subject,
            'is_read' => (bool) $message->is_read,
            'read_at' => optional($message->read_at)?->toISOString(),
            'attachment_name' => $message->attachment_name,
        ];
    }

    protected function getComposeRecipients(User $user)
    {
        if ($user->isCS() || $user->isHeadCS()) {
            return User::query()
                ->whereIn('role', ['it', 'admin', 'supervisor'])
                ->orderBy('name')
                ->get();
        }

        if ($user->role === 'it') {
            return User::query()
                ->whereIn('role', ['cs', 'head_cs', 'admin', 'supervisor'])
                ->orderBy('name')
                ->get();
        }

        return User::query()
            ->where('id', '<>', $user->id)
            ->orderBy('name')
            ->get();
    }
    /**
     * Scope compose ticket choices to records the current user can legitimately reference.
     */
    protected function scopeComposeTickets(Builder $query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor() || $user->isHeadCS()) {
            return;
        }

        if ($user->isCS()) {
            $query->where('created_by', $user->id);
            return;
        }

        if ($user->isIT()) {
            $query->forTeamCode('it')
                ->where('holder_id', $user->id);
        }
    }

}
