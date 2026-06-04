{{-- ========= TICKET FILTERS ========= --}}
{{-- Declarative ticket filters; behavior lives in the ticket index page script. --}}

{{-- Ticket index filters are declarative; all behavior lives in resources/js/pages/tickets/index.js. --}}
<form @submit.prevent="applyFilters()" class="mb-6">
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_150px_150px_150px_280px_auto]">
        <input
            type="text"
            x-model="filters.q"
            placeholder="Search by T-code or Keyword..."
            class="h-10 w-full rounded-md border border-slate-300 bg-white px-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-0"
            @keydown.enter.prevent="applyFilters()" />

        <select
            x-model="filters.status"
            class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-0">
            <option value="all">All Status</option>
            <option value="new">New</option>
            <option value="in_progress">Ongoing</option>
            <option value="waiting_info">Waiting</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
        </select>

        <select
            x-model="filters.priority"
            class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-0">
            <option value="all">All Priority</option>
            <template x-for="priority in priorityOptions" :key="priority.id || priority.code">
                <option :value="priority.code || slugify(priority.name)" x-text="priority.name"></option>
            </template>
        </select>


        <select
            x-model="filters.mine"
            class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-0">
            <option value="">All Creators</option>
            <option value="1">My Tickets</option>
        </select>

        <div class="flex items-center gap-2">
            <input type="hidden" id="date_from" x-model="filters.date_from">
            <input type="hidden" id="date_to" x-model="filters.date_to">

            <button
                id="dateRangeTrigger"
                type="button"
                @click.prevent="openDatePicker()"
                class="relative flex h-10 w-full shrink-0 cursor-pointer items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 text-left hover:border-slate-400">
                <span class="pointer-events-none truncate text-sm text-slate-700" x-text="dateLabel()"></span>
                <svg class="pointer-events-none h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                </svg>
            </button>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="submit"
                class="inline-flex h-10 items-center justify-center rounded-md bg-[#2f88d8] px-4 text-sm font-semibold text-white transition hover:bg-[#2878c3]">
                Apply
            </button>

            <button
                type="button"
                @click="resetFilters()"
                class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Reset
            </button>
        </div>
    </div>
</form>
