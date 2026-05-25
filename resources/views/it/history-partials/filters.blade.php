        {{-- TOP FILTER BAR --}}
        <form @submit.prevent="applyFilters()" id="historyFilterForm" class="mb-6">
            <div class="flex items-center gap-2">

                {{-- Search --}}
                <div class="flex-1">
                    <input
                        type="text"
                        x-model="filters.q"
                        placeholder="Search by Ticket ID or Keyword..."
                        class="h-8 w-full rounded-md border border-slate-300 bg-white px-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-0"
                        @keydown.enter.prevent="applyFilters()" />
                </div>

                {{-- Hidden real date inputs --}}
                <input type="hidden" id="date_from" x-model="filters.date_from">
                <input type="hidden" id="date_to" x-model="filters.date_to">

                {{-- Date Range Visual Trigger --}}
                <div
                    id="dateRangeTrigger"
                    class="relative flex h-8 w-60 shrink-0 cursor-pointer items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 hover:border-slate-400">
                    <span id="dateRangeLabel" class="truncate text-sm text-slate-700" x-text="dateLabel()"></span>
                    <svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 10h18"></path>
                    </svg>
                </div>

                {{-- Export --}}
                <div class="relative" @click.outside="exportOpen = false">
                    <button
                        type="button"
                        @click="exportOpen = !exportOpen"
                        style="display:inline-flex;height:32px;flex-shrink:0;align-items:center;justify-content:center;gap:8px;border-radius:6px;background-color:#2f88d8;padding:0 16px;font-size:14px;font-weight:500;color:#ffffff;white-space:nowrap;border:none;cursor:pointer;"
                        onmouseover="this.style.backgroundColor='#2878c3'"
                        onmouseout="this.style.backgroundColor='#2f88d8'">
                        Export Data
                        <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="exportOpen"
                        x-transition
                        class="absolute right-0 z-50 mt-2 w-40 overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg"
                        style="display:none;">
                        <button
                            type="button"
                            @click="exportData('csv')"
                            class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                            Export CSV
                        </button>

                        <button
                            type="button"
                            @click="exportData('excel')"
                            class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                            Export Excel
                        </button>

                        <button
                            type="button"
                            @click="exportData('pdf')"
                            class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                            Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </form>
