<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ResolverMessage;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

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

        $messages = $query->latest()->paginate(10);

        $composeTickets = Ticket::query()
            ->with(['holder', 'creator'])
            ->where('status', '<>', 'closed');
        $this->scopeComposeTickets($composeTickets, $user);
        $composeTickets = $composeTickets
            ->latest()
            ->limit(50)
            ->get();

        $composeRecipients = $this->getComposeRecipients($user);

        return response()->json([
            'success' => true,
            'message' => 'Resolver inbox loaded',
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
            'extra' => [
                'compose_tickets' => $composeTickets,
                'compose_recipients' => $composeRecipients,
            ]
        ]);
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

        $toUserId = $this->resolveRecipientId($validated, $ticket, $user);

        if (!$toUserId) {
            return $this->error('Recipient is required for this message.', 422);
        }

        if ((int) $toUserId === (int) $user->id) {
            return $this->error('You cannot send a resolver message to yourself.', 422);
        }

        if (! $this->isAllowedRecipient($ticket, (int) $toUserId)) {
            return $this->error('The selected recipient is not allowed for this ticket.', 422);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('resolver-messages', 'public');
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

        return $this->success(
            $message,
            'Message sent successfully',
            201
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

        return $this->success(null, 'Message deleted');
    }


    protected function scopeInboxMessages($query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return;
        }

        $query->where('to_user_id', $user->id);
    }


    protected function scopeThreadMessages($query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return;
        }

        $query->where(function ($query) use ($user) {
            $query->where('from_user_id', $user->id)
                ->orWhere('to_user_id', $user->id);
        });
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
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR])
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
        if ($user->role === 'cs') {
            return User::query()
                ->whereIn('role', ['it', 'admin', 'supervisor'])
                ->orderBy('name')
                ->get();
        }

        if ($user->role === 'it') {
            return User::query()
                ->whereIn('role', ['cs', 'admin', 'supervisor'])
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
    protected function scopeComposeTickets($query, User $user): void
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
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
