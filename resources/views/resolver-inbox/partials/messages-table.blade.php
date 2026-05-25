{{-- MESSAGE LIST: Compact ticket conversation inbox. Each row opens the resolver conversation detail. --}}
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-950">Conversation Inbox</h2>
            <p class="mt-1 text-sm text-slate-500">Latest resolver conversations grouped around ticket context.</p>
        </div>

        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">
            <span x-text="meta.total || 0"></span>
            <span>message(s)</span>
        </div>
    </div>

    <div class="divide-y divide-slate-100">
        <template x-if="loading">
            <div class="px-6 py-10 text-center text-slate-500">
                Loading conversations...
            </div>
        </template>

        <template x-if="!loading && messages.length === 0">
            <div class="px-6 py-10 text-center text-slate-500">
                No resolver conversations found.
            </div>
        </template>

        <template x-for="message in messages" :key="message.id">
            <article
                class="group cursor-pointer px-6 py-4 transition hover:bg-slate-50"
                @click="openMessage(message)">
                <div class="flex items-start gap-4">
                    <div class="pt-2">
                        <span
                            class="block h-2.5 w-2.5 rounded-full"
                            :class="isUnreadMessage(message) ? 'bg-sky-500' : 'bg-slate-200'">
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-slate-950" x-text="ticketLabel(message.ticket)"></span>

                                    <template x-if="message.ticket?.priority">
                                        <span
                                            :class="priorityBadgeClass(message.ticket.priority)"
                                            x-text="priorityLabel(message.ticket.priority)">
                                        </span>
                                    </template>

                                    <template x-if="message.ticket?.status">
                                        <span
                                            :class="statusBadgeClass(message.ticket.status)"
                                            x-text="statusLabel(message.ticket.status)">
                                        </span>
                                    </template>

                                    <template x-if="message.ticket?.team">
                                        <span
                                            class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600"
                                            x-text="message.ticket.team">
                                        </span>
                                    </template>

                                    <template x-if="isUnreadMessage(message)">
                                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold text-sky-700">
                                            Unread
                                        </span>
                                    </template>
                                </div>

                                <h3 class="mt-2 truncate text-base font-bold text-slate-950" x-text="messageTitle(message)"></h3>
                                <p class="mt-1 text-xs font-medium text-slate-500" x-text="participantsLabel(message)"></p>
                                <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600" x-text="messagePreview(message, 120)"></p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3 text-sm text-slate-500 lg:flex-col lg:items-end">
                                <span x-text="formatDateTimeShort(message.created_at)"></span>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click.stop="replyMessage(message)"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                        Reply
                                    </button>

                                    <a
                                        :href="messageUrl(message)"
                                        @click.stop
                                        class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-700">
                                        Open
                                    </a>
                                </div>
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
