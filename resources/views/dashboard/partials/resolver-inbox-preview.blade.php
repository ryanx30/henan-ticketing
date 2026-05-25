@props([
    'title' => 'Resolver Inbox',
    'subtitle' => 'Latest resolver conversations and ticket updates.',
    'showTimeFilter' => false,
])

{{-- RESOLVER INBOX PREVIEW: Compact dashboard-only conversation list. --}}
<div class="overflow-hidden rounded bg-white shadow-lg">
    <div class="flex flex-col gap-3 border-b bg-slate-50 px-4 py-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-xl font-semibold text-slate-950">{{ $title }}</div>
            <div class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</div>
        </div>

        <div class="flex items-center gap-3">
            @if ($showTimeFilter)
                <label class="sr-only" for="dashboard-inbox-period">Inbox period</label>
                <select
                    id="dashboard-inbox-period"
                    x-model="filters.inbox_period"
                    @change="applyFilters()"
                    class="h-10 min-w-[112px] rounded-lg border border-slate-300 bg-white pl-3 pr-8 text-sm text-slate-700">
                    <option value="today">Today</option>
                    <option value="7d">Last 7 days</option>
                    <option value="30d">Last 30 days</option>
                    <option value="all">All time</option>
                </select>
            @endif

            <a href="{{ route('resolver-inbox.index') }}" class="text-sm font-semibold text-slate-600 underline transition hover:text-slate-950">
                Open Inbox
            </a>
        </div>
    </div>

    <div class="divide-y divide-slate-100">
        <template x-if="loading && resolverInbox.length === 0">
            <div class="px-5 py-8 text-center text-sm text-slate-500">
                Loading resolver inbox...
            </div>
        </template>

        <template x-if="!loading && resolverInbox.length === 0">
            <div class="px-5 py-8 text-center text-sm text-slate-500">
                No resolver messages yet.
            </div>
        </template>

        <template x-for="message in resolverInbox.slice(0, 5)" :key="message.id">
            <article
                class="group cursor-pointer px-5 py-4 transition hover:bg-slate-50"
                @click="openResolverMessage(message)">
                <div class="flex items-start gap-3">
                    <div class="pt-2">
                        <span
                            class="block h-2.5 w-2.5 rounded-full"
                            :class="isUnreadMessage(message) ? 'bg-sky-500' : 'bg-slate-200'">
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-bold text-slate-950" x-text="ticketLabel(message.ticket)"></span>

                            <template x-if="message.ticket?.priority">
                                <span :class="priorityBadgeClass(message.ticket.priority)" x-text="priorityLabel(message.ticket.priority)"></span>
                            </template>

                            <template x-if="message.ticket?.status">
                                <span :class="statusBadgeClass(message.ticket.status)" x-text="statusLabel(message.ticket.status)"></span>
                            </template>

                            <template x-if="isUnreadMessage(message)">
                                <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold text-sky-700">
                                    Unread
                                </span>
                            </template>
                        </div>

                        <div class="mt-2 flex flex-col gap-1 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold text-slate-950" x-text="messageTitle(message)"></h3>
                                <p class="mt-1 truncate text-xs font-medium text-slate-500" x-text="participantsLabel(message)"></p>
                            </div>

                            <div class="shrink-0 text-xs text-slate-500" x-text="formatDateTimeShort(message.created_at)"></div>
                        </div>

                        <p class="mt-2 line-clamp-2 text-sm leading-5 text-slate-600" x-text="messagePreview(message, 110)"></p>

                        <div class="mt-3 flex items-center gap-2 opacity-100 transition lg:opacity-0 lg:group-hover:opacity-100">
                            <a
                                :href="messageUrl(message)"
                                @click.stop
                                class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700">
                                Open
                            </a>
                            <button
                                type="button"
                                @click.stop="copyText(message.body || message.subject || '')"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        </template>
    </div>
</div>
