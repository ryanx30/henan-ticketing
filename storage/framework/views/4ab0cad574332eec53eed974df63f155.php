            
            <div class="mb-5 rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-slate-700">
                        <span>Range:</span>
                        <select
                            x-model="filters.range"
                            @change="onRangeChange()"
                            class="h-10 w-[130px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <option value="this_week">This Week</option>
                            <option value="7d">Last 7 Days</option>
                            <option value="30d">Last 30 Days</option>
                            <option value="this_month">This Month</option>
                            <option value="one_year">One Year</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <div x-show="filters.range === 'custom'" class="flex items-center gap-2">
                        <input type="hidden" id="date_from" x-model="filters.date_from">
                        <input type="hidden" id="date_to" x-model="filters.date_to">

                        <div
                            id="dateRangeTrigger"
                            class="relative flex h-10 min-w-[200px] cursor-pointer items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 hover:border-slate-400">
                            <span id="dateRangeLabel" class="truncate text-sm text-slate-700" x-text="dateLabel()"></span>
                            <svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-slate-700">
                        <span>Scope:</span>
                        <select
                            x-model="filters.scope"
                            @change="applyFilters()"
                            class="h-10 w-[150px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <option value="my">My Tickets</option>

                            <?php if(auth()->user()->role === 'cs'): ?>
                                <option value="team">All CS Tickets</option>
                            <?php elseif(auth()->user()->role === 'it'): ?>
                                <option value="team">Team IT</option>
                            <?php elseif(in_array(auth()->user()->role, ['admin', 'supervisor'], true)): ?>
                                <option value="team">All CS Tickets</option>
                                <option value="all">All Tickets</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <div class="flex items-center gap-2 text-sm text-slate-700">
                            <span>Show:</span>
                            <select
                                x-model="filters.per_page"
                                @change="applyFilters()"
                                class="h-10 w-[90px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            @click="applyFilters()"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Apply
                        </button>

                        <button
                            type="button"
                            @click="exportCsv()"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/reports/partials/filter-bar.blade.php ENDPATH**/ ?>