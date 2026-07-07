


            
            <div class="mb-5 rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-slate-700">
                        <span>Range:</span>
                        <select
                            x-model="filters.range"
                            @change="onRangeChange()"
                            class="h-10 w-[150px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <option value="1d">1 Day</option>
                            <option value="1w">1 Week</option>
                            <option value="1m">1 Month</option>
                            <option value="3m">3 Month</option>
                            <option value="ytd">YTD</option>
                            <option value="1y">1 Year</option>
                            <option value="3y">3 Year</option>
                            <option value="5y">5 Year</option>
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
                        <span>Report Type:</span>
                        <select
                            x-model="filters.scope"
                            @change="applyFilters()"
                            class="h-10 w-[190px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <?php if(auth()->user()->role === 'admin'): ?>
                                <option value="it_performance">IT Performance</option>
                            <?php elseif(auth()->user()->role === 'head_cs'): ?>
                                <option value="cs_performance">CS Performance</option>
                            <?php elseif(auth()->user()->role === 'it'): ?>
                                <option value="my">My Performance</option>
                                <option value="team">Team Performance</option>
                            <?php elseif(auth()->user()->role === 'cs'): ?>
                                <option value="my">My Performance</option>
                            <?php elseif(auth()->user()->role === 'supervisor'): ?>
                                <option value="all">All Tickets</option>
                            <?php else: ?>
                                <option value="my">My Performance</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div x-show="userFilter.available" class="flex items-center gap-2 text-sm text-slate-700">
                        <span x-text="`${userFilter.label}:`"></span>
                        <select
                            x-model="filters.user_id"
                            @change="applyFilters()"
                            class="h-10 w-[220px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <option value="" x-text="userFilter.placeholder"></option>
                            <template x-for="option in userFilter.options" :key="option.id">
                                <option :value="String(option.id)" x-text="userOptionLabel(option)"></option>
                            </template>
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