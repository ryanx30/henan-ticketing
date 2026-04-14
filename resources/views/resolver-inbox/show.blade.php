<x-app-layout>
    <div
        x-data="resolverInboxCompose()"
        class="min-h-screen bg-[#eef1f5] p-6">
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">

            {{-- Header --}}
            <div class="mb-5 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-slate-800">Pesan</h1>

                <div class="flex items-center gap-3 text-sm">
                    <span class="text-slate-600">Filters:</span>

                    <select onchange="window.location=this.value" class="rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['unread' => 'all'])) }}" {{ $unread === 'all' ? 'selected' : '' }}>All</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['unread' => 'unread'])) }}" {{ $unread === 'unread' ? 'selected' : '' }}>Unread</option>
                    </select>

                    <select onchange="window.location=this.value" class="rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['priority' => 'all'])) }}" {{ $priority === 'all' ? 'selected' : '' }}>Priority</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['priority' => 'critical'])) }}" {{ $priority === 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['priority' => 'high'])) }}" {{ $priority === 'high' ? 'selected' : '' }}>High</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['priority' => 'medium'])) }}" {{ $priority === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['priority' => 'low'])) }}" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
                    </select>

                    <select onchange="window.location=this.value" class="rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['team' => 'all'])) }}" {{ $team === 'all' ? 'selected' : '' }}>Team</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['team' => 'it'])) }}" {{ $team === 'it' ? 'selected' : '' }}>IT</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['team' => 'finance'])) }}" {{ $team === 'finance' ? 'selected' : '' }}>Finance</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['team' => 'compliance'])) }}" {{ $team === 'compliance' ? 'selected' : '' }}>Compliance</option>
                    </select>

                    <select onchange="window.location=this.value" class="rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['date' => 'all'])) }}" {{ $date === 'all' ? 'selected' : '' }}>Date</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['date' => 'today'])) }}" {{ $date === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['date' => '7d'])) }}" {{ $date === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="{{ route('resolver-inbox.index', array_merge(request()->query(), ['date' => '30d'])) }}" {{ $date === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                    </select>
                </div>
            </div>

            {{-- Compose Button --}}
            <div class="mb-5">
                <button
                    type="button"
                    @click="openCompose()"
                    class="inline-flex items-center gap-3 rounded-[22px] bg-sky-200 px-6 py-4 text-[18px] font-medium text-slate-800 shadow-md transition hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 113.03 2.906L9.5 17l-4 1 1-4 10.362-10.513z" />
                    </svg>
                    Compose
                </button>
            </div>

            {{-- Messages Table --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md">
                <div class="bg-[#001a2c] px-6 py-4 text-2xl font-bold text-white">
                    Messages
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-sm">
                        <thead class="bg-slate-200 text-slate-700">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">Ticket</th>
                                <th class="px-4 py-3 text-left font-semibold">Code</th>
                                <th class="px-4 py-3 text-left font-semibold">Priority</th>
                                <th class="px-4 py-3 text-left font-semibold">Description</th>
                                <th class="px-4 py-3 text-right font-semibold">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $message)
                            @php
                            $priorityClass = match($message->ticket->priority) {
                            'critical' => 'bg-red-500 text-white',
                            'high' => 'bg-orange-400 text-white',
                            'medium' => 'bg-yellow-400 text-slate-900',
                            'low' => 'bg-slate-300 text-slate-800',
                            default => 'bg-slate-300 text-slate-800',
                            };
                            @endphp

                            <tr
                                class="group cursor-pointer border-t border-slate-200 odd:bg-white even:bg-slate-100 hover:bg-slate-50"
                                data-open-url="{{ route('resolver-inbox.show', $message->id) }}"
                                @click="window.location = $el.dataset.openUrl">
                                <td class="px-5 py-4 font-medium text-slate-800">
                                    #T-{{ $message->ticket->ticket_code ?? $message->ticket->id }}
                                </td>

                                <td class="px-4 py-4">
                                    @if(!$message->is_read && $message->to_user_id === auth()->id())
                                    <span class="inline-flex rounded-md bg-slate-300 px-3 py-1 text-xs font-bold text-white">
                                        NEW
                                    </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $priorityClass }}">
                                        {{ ucfirst($message->ticket->priority) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-base text-slate-800">
                                    {{ \Illuminate\Support\Str::limit($message->subject ?: $message->body, 70) }}
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <div class="relative flex justify-end">
                                        <div class="group-hover:hidden text-slate-600">
                                            {{ $message->created_at->format('H:i') }}
                                        </div>

                                        <div class="hidden items-center gap-3 group-hover:flex">
                                            {{-- Reply --}}
                                            <button
                                                type="button"
                                                data-ticket-id="{{ $message->ticket_id }}"
                                                data-to-user-id="{{ $message->from_user_id === auth()->id() ? $message->to_user_id : $message->from_user_id }}"
                                                data-subject="{{ $message->subject ?: 'Reply for #' . ($message->ticket->ticket_code ?? $message->ticket->id) }}"
                                                onclick="event.stopPropagation(); window.dispatchEvent(new CustomEvent('reply-message', {
        detail: {
            ticketId: this.dataset.ticketId,
            toUserId: this.dataset.toUserId,
            subject: this.dataset.subject
        }
    }));"
                                                class="text-slate-500 hover:text-slate-800"
                                                title="Reply">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10M3 10l4-4M3 10l4 4M21 21a8 8 0 00-8-8H7" />
                                                </svg>
                                            </button>

                                            {{-- Mark as read --}}
                                            @if(!$message->is_read && $message->to_user_id === auth()->id())
                                            <form method="POST" action="{{ route('resolver-inbox.read', $message->id) }}" onclick="event.stopPropagation();">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-slate-500 hover:text-slate-800" title="Mark as Read">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </form>
                                            @endif

                                            {{-- Delete --}}
                                            <form method="POST" action="{{ route('resolver-inbox.destroy', $message->id) }}" onclick="event.stopPropagation();" onsubmit="return confirm('Delete this message?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-500 hover:text-red-600" title="Delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 3v7m4-7v7m4-7v7M7 7l1 12h8l1-12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                    No messages found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Compose Modal --}}
        <div
            x-show="showCompose"
            x-transition
            class="fixed inset-0 z-50"
            style="display: none;">
            <div class="pointer-events-none absolute inset-0 bg-transparent"></div>

            <div class="pointer-events-auto fixed bottom-0 right-6 z-50 w-full max-w-3xl overflow-hidden rounded-t-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-slate-100 px-5 py-3">
                    <h3 class="text-[18px] font-semibold text-slate-900">New Message</h3>

                    <div class="flex items-center gap-4 text-slate-500">
                        <button type="button" @click="showCompose = false">—</button>
                        <button type="button" @click="discardDraft()">✕</button>
                    </div>
                </div>

                <form method="POST" action="{{ route('resolver-inbox.store') }}" enctype="multipart/form-data" class="p-5">
                    @csrf

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex flex-1 items-center gap-4">
                                <span class="w-14 text-sm text-slate-700">To</span>
                                <select name="to_user_id" x-model="form.to_user_id" class="w-full border-0 bg-transparent text-sm outline-none">
                                    <option value="">Choose recipient</option>
                                    @foreach($composeRecipients as $recipient)
                                    <option value="{{ $recipient->id }}">{{ $recipient->name }} - {{ strtoupper($recipient->role) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Ticket</span>
                            <select name="ticket_id" x-model="form.ticket_id" class="w-full border-0 bg-transparent text-sm outline-none">
                                <option value="">Choose Ticket</option>
                                @foreach($composeTickets as $ticket)
                                <option value="{{ $ticket->id }}">
                                    #T-{{ $ticket->ticket_code ?? $ticket->id }} - {{ $ticket->title }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Subject</span>
                            <input type="text" name="subject" x-model="form.subject" class="w-full border-0 bg-transparent text-sm outline-none" placeholder="Message subject">
                        </div>
                    </div>

                    <div class="py-4">
                        <textarea
                            name="body"
                            x-model="form.body"
                            rows="12"
                            class="w-full resize-none border-0 text-sm outline-none"
                            placeholder="Write your message..."></textarea>
                    </div>

                    <div class="mb-4 rounded-full bg-slate-100 px-4 py-3">
                        <div class="flex flex-wrap items-center gap-4 text-slate-600">
                            <span class="text-sm">Sans Serif</span>
                            <span class="text-sm font-bold">B</span>
                            <span class="text-sm italic">I</span>
                            <span class="text-sm underline">U</span>
                            <span class="text-sm">≡</span>
                            <span class="text-sm">•</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button type="submit" class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                Send
                            </button>

                            <label class="cursor-pointer text-slate-600 hover:text-slate-900" title="Attach file">
                                <input type="file" name="attachment" class="hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                </svg>
                            </label>
                        </div>

                        <button
                            type="button"
                            @click="discardDraft()"
                            class="text-slate-500 hover:text-red-600"
                            title="Discard draft">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 3v7m4-7v7m4-7v7M7 7l1 12h8l1-12" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function resolverInboxCompose() {
            return {
                showCompose: false,
                form: {
                    ticket_id: '',
                    to_user_id: '',
                    subject: '',
                    body: '',
                },

                openCompose() {
                    this.showCompose = true;
                },

                discardDraft() {
                    this.form.ticket_id = '';
                    this.form.to_user_id = '';
                    this.form.subject = '';
                    this.form.body = '';
                    this.showCompose = false;
                }
            }
        }

        window.addEventListener('reply-message', (event) => {
            const root = document.querySelector('[x-data="resolverInboxCompose()"]');
            if (!root || !root.__x) return;

            const data = event.detail || {};
            root.__x.$data.showCompose = true;
            root.__x.$data.form.ticket_id = data.ticketId || '';
            root.__x.$data.form.to_user_id = data.toUserId || '';
            root.__x.$data.form.subject = data.subject || '';
        });
    </script>
</x-app-layout>