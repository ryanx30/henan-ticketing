<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title' => 'Resolver Inbox',
    'subtitle' => 'Latest resolver conversations and ticket updates.',
    'showTimeFilter' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title' => 'Resolver Inbox',
    'subtitle' => 'Latest resolver conversations and ticket updates.',
    'showTimeFilter' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<div class="overflow-hidden rounded bg-white shadow-lg">
    <div class="flex flex-col gap-3 border-b bg-slate-50 px-4 py-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-xl font-semibold text-slate-950"><?php echo e($title); ?></div>
            <div class="mt-0.5 text-sm text-slate-500"><?php echo e($subtitle); ?></div>
        </div>

        <div class="flex items-center gap-3">
            <?php if($showTimeFilter): ?>
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
            <?php endif; ?>

            <a href="<?php echo e(route('resolver-inbox.index')); ?>" class="text-sm font-semibold text-slate-600 underline transition hover:text-slate-950">
                Open Inbox
            </a>
        </div>
    </div>

    <div class="space-y-3 bg-white p-4">
        <template x-if="loading && resolverInbox.length === 0">
            <div class="px-5 py-8 text-center text-sm text-slate-500">
                Loading resolver inbox...
            </div>
        </template>

        <template x-if="!loading && resolverInboxThreads().length === 0">
            <div class="px-5 py-8 text-center text-sm text-slate-500">
                No resolver messages yet.
            </div>
        </template>

        <template x-for="thread in resolverInboxThreads(5)" :key="thread.key">
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
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-slate-950" x-text="ticketLabel(thread.ticket)"></span>

                                    <template x-if="thread.ticket?.priority">
                                        <span :class="priorityBadgeClass(thread.ticket.priority)" x-text="priorityLabel(thread.ticket.priority)"></span>
                                    </template>

                                    <template x-if="thread.ticket?.status">
                                        <span :class="statusBadgeClass(thread.ticket.status)" x-text="statusLabel(thread.ticket.status)"></span>
                                    </template>

                                    <span class="text-xs font-medium text-slate-500" x-text="formatDateTimeShort(thread.latestMessage?.created_at)"></span>

                                    <template x-if="threadUnreadCount(thread) > 0">
                                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold text-sky-700" x-text="`${threadUnreadCount(thread)} unread`"></span>
                                    </template>
                                </div>

                                <h3 class="mt-2 truncate text-sm font-bold text-slate-950" x-text="messageTitle(thread.latestMessage)"></h3>
                                <p class="mt-1 truncate text-xs font-medium text-slate-500" x-text="`Latest: ${participantsLabel(thread.latestMessage)}`"></p>
                            </div>

                            <a
                                :href="messageUrl(thread.latestMessage)"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-700">
                                Open
                            </a>
                        </div>

                        <p class="mt-2 line-clamp-2 text-sm leading-5 text-slate-600" x-text="messagePreview(thread.latestMessage, 130)"></p>

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
                            class="mt-3 rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <div class="space-y-3">
                                <template x-for="reply in resolverThreadReplies(thread)" :key="reply.id">
                                    <div class="rounded-lg bg-white px-3 py-2 shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs font-semibold text-slate-600" x-text="participantsLabel(reply)"></span>
                                            <span class="text-[11px] font-medium text-slate-400" x-text="formatDateTimeShort(reply.created_at)"></span>
                                        </div>
                                        <p class="mt-1 text-sm leading-5 text-slate-600" x-text="messagePreview(reply, 140)"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </template>
    </div>
</div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/dashboard/partials/resolver-inbox-preview.blade.php ENDPATH**/ ?>