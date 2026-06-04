{{-- ========= INBOX HEADER FILTERS ========= --}}
{{-- Inbox title, counters, and filtering controls. --}}

{{-- ========= HEADER + FILTERS ========= --}}
<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Resolver Inbox</h1>
            <p class="mt-1 text-sm text-slate-500">Monitor resolver updates, reply to ticket messages, and keep ticket communication centralized.</p>
        </div>

        <button
            type="button"
            @click="openCompose()"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 113.03 2.906L9.5 17l-4 1 1-4 10.362-10.513z" />
            </svg>
            Compose
        </button>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-4">
        <select x-model="filters.unread" @change="applyFilters()" class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
            <option value="all">All messages</option>
            <option value="unread">Unread only</option>
        </select>

        <select x-model="filters.priority" @change="applyFilters()" class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
            <option value="all">All priorities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>

        <select x-model="filters.team" @change="applyFilters()" class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
            <option value="all">All teams</option>
            <option value="it">IT</option>
            <option value="finance">Finance</option>
            <option value="compliance">Compliance</option>
        </select>

        <select x-model="filters.date" @change="applyFilters()" class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
            <option value="all">All dates</option>
            <option value="today">Today</option>
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
        </select>
    </div>
</section>
