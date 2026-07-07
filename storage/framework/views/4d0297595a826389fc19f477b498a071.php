


<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="px-6 py-6 space-y-6">
        <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4">
            <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                <div class="w-full lg:w-56">
                    <label for="timeRange" class="block text-sm font-medium text-slate-700 mb-2">Time Range</label>
                    <select
                        id="timeRange"
                        class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500 text-sm">
                        <option value="">Loading...</option>
                    </select>
                </div>

                <div class="w-full lg:w-56">
                    <label for="teamFilter" class="block text-sm font-medium text-slate-700 mb-2">Team</label>
                    <select
                        id="teamFilter"
                        class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500 text-sm">
                        <option value="">Loading...</option>
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        id="applyFiltersBtn"
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg id="applyBtnSpinner" class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"></path>
                        </svg>
                        Apply Filters
                    </button>

                    <div class="relative">
                        <button
                            id="exportMenuBtn"
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#2f80d1] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#246bb0]">
                            <span>Export Data</span>
                            <svg id="exportMenuIcon" class="h-4 w-4 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div
                            id="exportMenu"
                            class="hidden absolute right-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                            <button type="button" data-export-format="excel" class="block w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50">
                                Export Excel
                            </button>
                            <button type="button" data-export-format="pdf" class="block w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50">
                                Export PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div id="analyticsError" class="hidden rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"></div>

        <section id="analyticsSkeleton" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                <?php for($i = 0; $i < 5; $i++): ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 animate-pulse">
                        <div class="h-4 bg-slate-200 rounded w-2/3 mb-4"></div>
                        <div class="h-8 bg-slate-200 rounded w-1/2 mb-4"></div>
                        <div class="h-5 bg-slate-200 rounded w-1/3"></div>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 animate-pulse">
                    <div class="h-5 bg-slate-200 rounded w-48 mb-4"></div>
                    <div class="h-80 bg-slate-100 rounded-xl"></div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-5 animate-pulse">
                    <div class="h-5 bg-slate-200 rounded w-44 mb-4"></div>
                    <div class="h-80 bg-slate-100 rounded-xl"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <?php for($i = 0; $i < 3; $i++): ?>
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 animate-pulse">
                        <div class="h-5 bg-slate-200 rounded w-52 mb-4"></div>
                        <div class="space-y-3">
                            <?php for($j = 0; $j < 5; $j++): ?>
                                <div class="h-10 bg-slate-100 rounded-xl"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <section id="analyticsContent" class="hidden space-y-6">
            <div id="metricsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4"></div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-slate-900">Ticket Volume Trend</h3>
                        <p class="text-sm text-slate-500">Incoming vs resolved tickets</p>
                    </div>
                    <div class="h-80">
                        <canvas id="ticketVolumeChart"></canvas>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Peak Time Ticket Volume</h3>
                            <p class="text-sm text-slate-500">Ticket creation by hour</p>
                        </div>
                        <div id="peakTimeBadge" class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"></div>
                    </div>
                    <div class="h-80">
                        <canvas id="peakTimeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-slate-900">Agent Performance</h3>
                        <p class="text-sm text-slate-500">Top resolvers by completed tickets</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-500">
                                    <th class="text-left py-3 pr-3 font-medium">Rank</th>
                                    <th class="text-left py-3 pr-3 font-medium">Agent</th>
                                    <th class="text-left py-3 pr-3 font-medium">Resolved</th>
                                    <th class="text-left py-3 font-medium">Avg. Time</th>
                                </tr>
                            </thead>
                            <tbody id="leaderboardBody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-slate-900">Top Teams</h3>
                        <p class="text-sm text-slate-500">Most active teams by ticket volume</p>
                    </div>
                    <div id="topTeamsList" class="space-y-3"></div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                    <div class="mb-5">
                        <h3 class="text-base font-semibold text-slate-900">Top Issues</h3>
                        <p class="text-sm text-slate-500">Most frequent issue types</p>
                    </div>
                    <div id="topIssuesList" class="space-y-3"></div>
                </div>
            </div>
        </section>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/case-analytics/index.blade.php ENDPATH**/ ?>