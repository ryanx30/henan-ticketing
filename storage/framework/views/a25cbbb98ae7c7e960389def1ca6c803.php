


<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-950">Conversation Inbox</h2>
            <p class="mt-1 text-sm text-slate-500">Latest resolver conversations grouped by ticket.</p>
        </div>

        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">
            <span x-text="resolverConversationCount()"></span>
            <span>conversation(s)</span>
        </div>
    </div>

    <div class="space-y-3 bg-white p-4">
        <template x-if="loading">
            <div class="px-6 py-10 text-center text-slate-500">
                Loading conversations...
            </div>
        </template>

        <template x-if="!loading && resolverInboxThreads().length === 0">
            <div class="px-6 py-10 text-center text-slate-500">
                No resolver conversations found.
            </div>
        </template>

        <template x-for="thread in resolverInboxThreads()" :key="thread.key">
            <article class="group rounded-xl border border-slate-100 bg-white px-5 py-4 transition duration-200 hover:-translate-y-[1px] hover:border-sky-200 hover:bg-slate-50 hover:shadow-md">
                <div class="flex items-start gap-3">
                    <div class="pt-2">
                        <span
                            class="block h-2.5 w-2.5 rounded-full"
                            :class="threadUnreadCount(thread) > 0 ? 'bg-sky-500' : 'bg-slate-200'">
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-slate-950" x-text="ticketLabel(thread.ticket)"></span>

                                    <template x-if="thread.ticket?.priority">
                                        <span :class="priorityBadgeClass(thread.ticket.priority)" x-text="priorityLabel(thread.ticket.priority)"></span>
                                    </template>

                                    <template x-if="thread.ticket?.status">
                                        <span :class="statusBadgeClass(thread.ticket.status)" x-text="statusLabel(thread.ticket.status)"></span>
                                    </template>

                                    <template x-if="thread.ticket?.team">
                                        <span
                                            class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600"
                                            x-text="thread.ticket.team">
                                        </span>
                                    </template>

                                    <span class="text-xs font-medium text-slate-500" x-text="formatDateTimeShort(thread.latestMessage?.created_at)"></span>

                                    <template x-if="threadUnreadCount(thread) > 0">
                                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold text-sky-700" x-text="`${threadUnreadCount(thread)} unread`"></span>
                                    </template>
                                </div>

                                <h3 class="mt-2 truncate text-base font-bold text-slate-950" x-text="messageTitle(thread.latestMessage)"></h3>
                                <p class="mt-1 truncate text-xs font-medium text-slate-500" x-text="`Latest: ${participantsLabel(thread.latestMessage)}`"></p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    type="button"
                                    @click.stop="replyMessage(thread.latestMessage)"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    Reply
                                </button>

                                <a
                                    :href="messageUrl(thread.latestMessage)"
                                    @click.stop
                                    class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-700">
                                    Open
                                </a>
                            </div>
                        </div>

                        <p class="mt-2 line-clamp-2 text-sm leading-5 text-slate-600" x-text="messagePreview(thread.latestMessage, 140)"></p>

                        <template x-if="thread.latestMessage?.attachment_name">
                            <a
                                :href="attachmentUrl(thread.latestMessage)"
                                @click.stop
                                class="mt-3 inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-sky-200 hover:text-slate-900">
                                <span
                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md ring-1"
                                    :class="attachmentIconClass(thread.latestMessage)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                    </svg>
                                </span>
                                <span class="truncate" x-text="thread.latestMessage.attachment_name"></span>
                            </a>
                        </template>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <template x-if="threadReplyCount(thread) > 0">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-950"
                                    @click="toggleResolverThread(thread.key)">
                                    <span x-text="isResolverThreadExpanded(thread.key) ? `Hide ${threadReplyCount(thread)} replies` : `Show ${threadReplyCount(thread)} replies`"></span>
                                    <svg
                                        class="h-4 w-4 transition-transform duration-200"
                                        :class="isResolverThreadExpanded(thread.key) ? 'rotate-180' : ''"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M5 7.5L10 12.5L15 7.5"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </template>

                            <template x-if="threadReplyCount(thread) === 0">
                                <span></span>
                            </template>
                        </div>

                        <div
                            x-show="isResolverThreadExpanded(thread.key)"
                            x-transition
                            class="mt-3 rounded-lg border border-slate-100 bg-slate-50 p-3"
                            style="display:none;">
                            <div class="space-y-3">
                                <template x-for="reply in resolverThreadReplies(thread)" :key="reply.id">
                                    <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs font-semibold text-slate-600" x-text="participantsLabel(reply)"></span>
                                            <span class="text-[11px] font-medium text-slate-400" x-text="formatDateTimeShort(reply.created_at)"></span>
                                        </div>
                                        <p class="mt-1 text-sm leading-5 text-slate-600" x-text="messagePreview(reply, 160)"></p>

                                        <template x-if="reply.attachment_name">
                                            <a
                                                :href="attachmentUrl(reply)"
                                                @click.stop
                                                class="mt-2 inline-flex max-w-full items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-white hover:text-slate-900">
                                                <span
                                                    class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded ring-1"
                                                    :class="attachmentIconClass(reply)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                                    </svg>
                                                </span>
                                                <span class="truncate" x-text="reply.attachment_name"></span>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </template>
    </div>

    <div class="border-t border-slate-200 px-5 py-4" x-show="meta.last_page > 1">
        <div class="flex flex-wrap items-center gap-2">
            <template x-for="page in meta.last_page" :key="page">
                <button
                    type="button"
                    @click="goToPage(page)"
                    class="rounded border px-3 py-1 text-sm"
                    :class="page === meta.current_page ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                    <span x-text="page"></span>
                </button>
            </template>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/resolver-inbox/partials/messages-table.blade.php ENDPATH**/ ?>