{{-- ========= RESOLVER CONVERSATION SHELL ========= --}}
{{-- Single resolver message/conversation layout with API-backed actions. --}}

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- PAGE ROOT: Resolver inbox detail state and authenticated user context --}}
    <div
        id="resolver-message-detail-page"
        data-message-id="{{ $resolverMessage->id }}"
        data-current-user-id="{{ auth()->id() }}"
        data-is-it="{{ auth()->user()->role === 'it' ? '1' : '0' }}"
        x-data="resolverMessageDetailPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] p-6">

        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        <div class="mx-auto max-w-7xl space-y-6">
            {{-- HEADER: Back button, ticket title, priority badge, and status badge --}}
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <a
                        href="{{ route('resolver-inbox.index') }}"
                        data-smart-back
                        data-fallback-url="{{ route('resolver-inbox.index') }}"
                        aria-label="Back to previous page"
                        class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                        </svg>
                    </a>

                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-500">Resolver Conversation</div>
                        <h1 class="mt-1 text-[28px] font-bold leading-tight text-slate-950" x-text="conversationTitle()">
                            Resolver Conversation
                        </h1>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-bold text-slate-900" x-text="ticketLabel(ticket)"></span>
                            <span :class="priorityBadgeClass(ticket?.priority)" x-text="priorityLabel(ticket?.priority)"></span>
                            <span :class="statusBadgeClass(ticket?.status)" x-text="statusLabel(ticket?.status)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <template x-if="loading">
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center text-slate-500 shadow-sm">
                    Loading conversation...
                </div>
            </template>

            <template x-if="!loading && !message.id">
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center text-slate-500 shadow-sm">
                    Message not found.
                </div>
            </template>

            {{-- MAIN CONTENT: Conversation thread on the left, conversation contacts and ticket snapshot on the right --}}
            <div x-show="!loading && message.id" class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_330px]">
                {{-- LEFT PANEL: Selected private conversation thread --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">Conversation Thread</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                <span x-text="selectedRoom()?.title ? 'Private room with ' + selectedRoom().title : 'Messages related to this ticket.'"></span>
                            </p>
                        </div>

                        <div class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">
                            <span x-text="filteredThreadMessages().length"></span> message(s)
                        </div>
                    </div>

                    {{-- MESSAGE AREA: Keep the outer thread wrapper height unchanged; the compact composer gives this area more vertical room --}}
                    <div class="flex h-[560px] flex-col bg-slate-50">
                        <div
                            x-ref="threadScroll"
                            class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 pt-5 pb-3">
                            <template x-if="filteredThreadMessages().length === 0">
                                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-8 text-center text-sm text-slate-500">
                                    No visible messages in this room.
                                </div>
                            </template>

                            {{-- MESSAGE LIST: Bubble-style messages, aligned by sender --}}
                            <template x-for="threadMessage in filteredThreadMessages()" :key="threadMessage.id">
                                <div
                                    class="flex"
                                    :class="isMine(threadMessage) ? 'justify-end' : 'justify-start'">
                                    <div
                                        class="relative max-w-[78%] rounded-2xl px-4 py-3 shadow-sm"
                                        :class="isMine(threadMessage)
                                            ? 'rounded-br-md bg-[#d9f9c7] text-slate-900'
                                            : 'rounded-bl-md border border-slate-200 bg-white text-slate-900'">
                                        <div class="mb-2 flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-bold text-slate-950" x-text="participantsLabel(threadMessage)"></div>
                                                <div class="mt-0.5 text-xs text-slate-500" x-text="formatDateTime(threadMessage.created_at)"></div>
                                            </div>

                                            <div class="relative shrink-0">
                                                <button
                                                    type="button"
                                                    @click.stop="toggleMessageMenu(threadMessage.id)"
                                                    class="rounded-full p-1 text-slate-400 transition hover:bg-black/5 hover:text-slate-700"
                                                    aria-label="Message actions">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 14a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                                                    </svg>
                                                </button>

                                                <div
                                                    x-show="activeMenuId === threadMessage.id"
                                                    @click.outside="activeMenuId = null"
                                                    x-transition
                                                    class="absolute right-0 top-8 z-30 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl"
                                                    style="display:none;">
                                                    <button
                                                        type="button"
                                                        @click.stop="openReply(threadMessage)"
                                                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-slate-700 hover:bg-slate-50">
                                                        <img src="{{ asset('images/icons/reply.png') }}" alt="" class="h-4 w-4 object-contain">
                                                        <span>Reply</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click.stop="copyMessage(threadMessage)"
                                                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-slate-700 hover:bg-slate-50">
                                                        <img src="{{ asset('images/icons/copy.png') }}" alt="" class="h-4 w-4 object-contain">
                                                        <span>Copy</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click.stop="deleteMessage(threadMessage)"
                                                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-red-600 hover:bg-red-50">
                                                        <img src="{{ asset('images/icons/delete.png') }}" alt="" class="h-4 w-4 object-contain">
                                                        <span>Delete</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <template x-if="replyTarget && replyTarget.id === threadMessage.id && !replyContextDismissed">
                                            <div class="mb-2 rounded-lg border border-slate-200 bg-white/70 px-3 py-2 text-xs text-slate-600">
                                                Replying to this message
                                            </div>
                                        </template>

                                        <div class="whitespace-pre-line text-[15px] leading-7" x-text="threadMessage.body || '-'"></div>

                                        <template x-if="threadMessage.attachment_name">
                                            <a
                                                :href="attachmentUrl(threadMessage)"
                                                target="_blank"
                                                rel="noopener"
                                                class="mt-3 inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-white/70 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700">
                                                <span>Attachment:</span>
                                                <span class="truncate" x-text="threadMessage.attachment_name"></span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- REPLY COMPOSER: Compact chat input. Enter sends, Shift + Enter creates a new line --}}
                        <form @submit.prevent="submitReply" class="border-t border-slate-200 bg-white px-5 pt-2 pb-2">
                            <template x-if="replyTarget && !replyContextDismissed">
                                <div class="mb-2 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-700">
                                            Replying to <span x-text="reply.to_display || '-'" class="text-slate-950"></span>
                                        </div>
                                        <div class="truncate text-xs text-slate-500" x-text="truncate(replyTarget.body || '-', 120)"></div>
                                    </div>
                                    <button type="button" @click.stop.prevent="clearReplyTarget()" aria-label="Cancel reply context" class="ml-3 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-200 hover:text-slate-700">✕</button>
                                </div>
                            </template>

                            <div class="flex items-center gap-3 rounded-[26px] border border-slate-200 bg-slate-50 px-4 py-1.5 shadow-sm transition focus-within:border-slate-300 focus-within:bg-white">
                                <textarea
                                    x-model="reply.body"
                                    rows="1"
                                    @keydown.enter="handleReplyKeydown($event)"
                                    class="max-h-24 min-h-[32px] flex-1 resize-none border-0 bg-transparent px-0 py-1.5 text-sm leading-5 text-slate-900 outline-none placeholder:text-slate-500 focus:ring-0"
                                    :placeholder="replyPlaceholder()"></textarea>

                                <button
                                    type="submit"
                                    :disabled="submitting || !reply.body.trim()"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#22c55e] shadow-sm transition hover:bg-[#16a34a] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:opacity-70"
                                    aria-label="Send message">
                                    <img src="{{ asset('images/icons/send.png') }}" alt="" class="h-4 w-4 object-contain">
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                {{-- RIGHT SIDEBAR: Conversation contacts on top and compact ticket snapshot below --}}
                <aside class="space-y-5 lg:self-start">
                    {{-- CONVERSATION CONTACTS: Private rooms grouped by conversation participant --}}
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-4">
                            <h2 class="text-lg font-bold text-slate-950">Conversation Contacts</h2>
                            <p class="mt-1 text-sm text-slate-500">Choose a private room for this ticket.</p>
                        </div>

                        <div class="max-h-[290px] space-y-2 overflow-y-auto px-3 py-3">
                            <template x-if="conversationRooms().length === 0">
                                <div class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                    No conversation rooms yet.
                                </div>
                            </template>

                            <template x-for="room in conversationRooms()" :key="room.key">
                                <button
                                    type="button"
                                    @click="selectConversation(room)"
                                    class="flex w-full items-start gap-3 rounded-xl border px-3 py-3 text-left transition"
                                    :class="activeConversationKey === room.key
                                        ? 'border-sky-200 bg-[#f6f5f4] shadow-sm'
                                        : 'border-slate-200 bg-white hover:border-sky-100 hover:bg-sky-50/50'">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                        :class="activeConversationKey === room.key ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'"
                                        x-text="room.avatar">
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="truncate text-sm font-bold text-slate-950" x-text="room.title"></div>
                                            <template x-if="room.unreadCount > 0">
                                                <span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white" x-text="room.unreadCount"></span>
                                            </template>
                                        </div>
                                        <p class="mt-1 truncate text-xs font-medium text-slate-600" x-text="room.subtitle"></p>
                                        <p class="mt-1 text-xs text-slate-500" x-text="room.time"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </section>

                    {{-- TICKET SNAPSHOT: Compact ticket context for the active conversation --}}
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-4">
                            <h2 class="text-lg font-bold text-slate-950">Ticket Snapshot</h2>
                        </div>

                        <div class="space-y-4 px-4 py-4 text-sm">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ticket</div>
                                <div class="mt-1 font-mono text-sm font-bold text-slate-950" x-text="ticketLabel(ticket)"></div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Title</div>
                                <div class="mt-1 font-bold leading-snug text-slate-950" x-text="ticket?.title || '-' "></div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</div>
                                    <div class="mt-1.5">
                                        <span :class="priorityBadgeClass(ticket?.priority)" x-text="priorityLabel(ticket?.priority)"></span>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                                    <div class="mt-1.5">
                                        <span :class="statusBadgeClass(ticket?.status)" x-text="statusLabel(ticket?.status)"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Team</p>
                                    <p class="mt-2 truncate font-semibold text-slate-950" x-text="ticket.team || '-' "></p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Creator</p>
                                    <p class="mt-2 truncate font-semibold text-slate-950" x-text="ticket.creator?.name || '-' "></p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Holder</p>
                                    <p class="mt-2 truncate font-semibold text-slate-950" x-text="ticket.holder?.name || '-' "></p>
                                </div>
                            </div>

                            <a
                                :href="ticket?.id ? ticketUrl(ticket.id) : '#'"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">
                                Open Ticket
                            </a>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
