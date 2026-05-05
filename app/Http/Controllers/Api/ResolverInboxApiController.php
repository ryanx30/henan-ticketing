<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;
use App\Models\User;
use App\Models\ResolverMessage;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResolverInboxApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $unread   = $request->query('unread', 'all');
        $priority = $request->query('priority', 'all');
        $team     = $request->query('team', 'all');
        $date     = $request->query('date', 'all');

        $query = ResolverMessage::query()
            ->with(['ticket', 'sender', 'recipient'])
            ->where(function ($q) use ($user) {
                $q->where('to_user_id', $user->id)
                    ->orWhere('from_user_id', $user->id);
            })
            ->whereHas('ticket', function ($q) {
                $q->where('status', '<>', 'closed');
            });

        if ($unread === 'unread') {
            $query->where('to_user_id', $user->id)
                ->where('is_read', false);
        }

        if ($priority !== 'all') {
            $query->whereHas('ticket', function ($q) use ($priority) {
                $q->where('priority', $priority);
            });
        }

        if ($team !== 'all') {
            $query->whereHas('ticket', function ($q) use ($team) {
                $q->where('team', $team);
            });
        }

        if ($date === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($date === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($date === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $messages = $query->latest()->paginate(10);

        $composeTickets = Ticket::query()
            ->with(['holder', 'creator'])
            ->where('status', '<>', 'closed')
            ->latest()
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
        $user = $request->user();

        if ($resolverMessage->to_user_id === $user->id && !$resolverMessage->is_read) {
            $resolverMessage->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        $resolverMessage->load(['ticket', 'sender', 'recipient']);

        return $this->success($resolverMessage, 'Resolver message detail loaded');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:4096'],
        ]);

        $ticket = Ticket::query()
            ->with(['holder', 'creator'])
            ->where('status', '<>', 'closed')
            ->findOrFail($validated['ticket_id']);

        $toUserId = null;

        if ($user->role === 'it') {
            $toUserId = $ticket->created_by;
        } else {
            $toUserId = $ticket->holder_id;
        }

        if (!$toUserId) {
            return $this->error(
                $user->role === 'it'
                    ? 'Ticket ini belum punya pembuat ticket yang valid.'
                    : 'Ticket ini belum memiliki holder, jadi belum bisa dikirim.',
                422
            );
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
            'subject' => 'Reply for ' . AuditLogger::ticketLabel($ticket) . ' - ' . $ticket->title,
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
        if ($resolverMessage->to_user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

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
        $user = $request->user();

        if (
            $resolverMessage->from_user_id !== $user->id
            && $resolverMessage->to_user_id !== $user->id
            && $user->role !== 'admin'
        ) {
            return $this->error('Forbidden', 403);
        }

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
}
