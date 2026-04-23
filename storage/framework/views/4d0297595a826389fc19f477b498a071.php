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
                    <label for="timeRange" class="block text-sm font-medium text-slate-700 mb-2">
                        Time Range
                    </label>
                    <select
                        id="timeRange"
                        class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500 text-sm">
                        <option value="">Loading...</option>
                    </select>
                </div>

                <div class="w-full lg:w-56">
                    <label for="teamFilter" class="block text-sm font-medium text-slate-700 mb-2">
                        Team
                    </label>
                    <select
                        id="teamFilter"
                        class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-slate-500 text-sm">
                        <option value="">Loading...</option>
                    </select>
                </div>

                <div>
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
            <div class="h-5 bg-slate-200 rounded w-40 mb-4"></div>
            <div class="h-80 bg-slate-100 rounded-xl"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 animate-pulse">
            <div class="h-5 bg-slate-200 rounded w-52 mb-4"></div>
            <div class="space-y-3">
                <?php for($i = 0; $i < 6; $i++): ?>
                    <div class="h-10 bg-slate-100 rounded-xl">
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-5 animate-pulse">
        <div class="h-5 bg-slate-200 rounded w-44 mb-4"></div>
        <div class="h-80 bg-slate-100 rounded-xl"></div>
    </div>
    </div>
    </section>

    <section id="analyticsContent" class="hidden space-y-6">
        <div id="metricsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4"></div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <div class="mb-5">
                    <h3 class="text-base font-semibold text-slate-900">Ticket Volume Trend</h3>
                    <p class="text-sm text-slate-500">Incoming vs Resolved tickets</p>
                </div>
                <div class="h-80">
                    <canvas id="ticketVolumeChart"></canvas>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <div class="mb-5">
                    <h3 class="text-base font-semibold text-slate-900">Top Issues by Category</h3>
                    <p class="text-sm text-slate-500">Most frequent issue distribution</p>
                </div>
                <div class="h-80">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <div class="mb-5">
                    <h3 class="text-base font-semibold text-slate-900">Agent Performance Leaderboard</h3>
                    <p class="text-sm text-slate-500">Ranked by resolved cases, speed, and CSAT</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500">
                                <th class="text-left py-3 pr-4 font-medium">Rank</th>
                                <th class="text-left py-3 pr-4 font-medium">Agent</th>
                                <th class="text-left py-3 pr-4 font-medium">Resolved</th>
                                <th class="text-left py-3 pr-4 font-medium">Avg. Time</th>
                                <th class="text-left py-3 font-medium">CSAT</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <div class="mb-5">
                    <h3 class="text-base font-semibold text-slate-900">Peak Time Ticket Volume</h3>
                    <p class="text-sm text-slate-500">Ticket creation by hour</p>
                </div>
                <div class="h-80">
                    <canvas id="peakTimeChart"></canvas>
                </div>
            </div>
        </div>
    </section>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const analyticsApiUrl = "<?php echo e(route('api.case_analytics.index')); ?>";

        const analyticsState = {
            ticketVolumeChart: null,
            categoryChart: null,
            peakTimeChart: null,
            filters: {
                time_range: '1y',
                team: 'all',
            },
            filterOptionsLoaded: false,
        };

        const els = {
            timeRange: document.getElementById('timeRange'),
            teamFilter: document.getElementById('teamFilter'),
            applyFiltersBtn: document.getElementById('applyFiltersBtn'),
            applyBtnSpinner: document.getElementById('applyBtnSpinner'),
            analyticsSkeleton: document.getElementById('analyticsSkeleton'),
            analyticsContent: document.getElementById('analyticsContent'),
            analyticsError: document.getElementById('analyticsError'),
            metricsGrid: document.getElementById('metricsGrid'),
            leaderboardBody: document.getElementById('leaderboardBody'),
        };

        document.addEventListener('DOMContentLoaded', async () => {
            bindEvents();
            await loadAnalytics(false, true);
        });

        function bindEvents() {
            els.applyFiltersBtn.addEventListener('click', async () => {
                analyticsState.filters.time_range = els.timeRange.value;
                analyticsState.filters.team = els.teamFilter.value;
                await loadAnalytics(true, false);
            });
        }

        async function loadAnalytics(fromButton = false, isInitialLoad = false) {
            try {
                setLoading(true, fromButton);
                hideError();

                const params = new URLSearchParams({
                    time_range: analyticsState.filters.time_range || '1y',
                    team: analyticsState.filters.team || 'all',
                });

                const url = new URL(analyticsApiUrl, window.location.origin);
                url.search = params.toString();

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load analytics data. Status: ${response.status}`);
                }

                const responseJson = await response.json();
                const payload = responseJson.data ?? responseJson;

                if (!analyticsState.filterOptionsLoaded || isInitialLoad) {
                    renderTimeRanges(payload.filters?.time_ranges || []);
                    renderTeams(payload.filters?.teams || []);

                    analyticsState.filters.time_range = payload.filters?.selected?.time_range || '1y';
                    analyticsState.filters.team = payload.filters?.selected?.team || 'all';

                    els.timeRange.value = analyticsState.filters.time_range;
                    els.teamFilter.value = analyticsState.filters.team;

                    analyticsState.filterOptionsLoaded = true;
                }

                renderMetrics(payload.metrics || []);
                renderLeaderboard(payload.agent_performance_leaderboard || []);
                renderTicketVolumeChart(payload.ticket_volume_trend || {});
                renderCategoryChart(payload.top_issues_by_category || {});
                renderPeakTimeChart(payload.peak_time_ticket_volume || {});
            } catch (error) {
                console.error(error);
                destroyCharts();
                showError(error.message || 'Something went wrong while loading analytics.');
            } finally {
                setLoading(false, fromButton);
            }
        }

        function renderTimeRanges(ranges) {
            els.timeRange.innerHTML = ranges.map(range => `
                    <option value="${range.value}">${range.label}</option>
                `).join('');
        }

        function renderTeams(teams) {
            els.teamFilter.innerHTML = `
                    <option value="all">All Teams</option>
                    ${teams.map(team => `<option value="${team.id}">${team.name}</option>`).join('')}
                `;
        }

        function renderMetrics(metrics) {
            els.metricsGrid.innerHTML = metrics.map(metric => {
                const pillClass = resolveMetricPillClass(metric);
                const changeText = formatChange(metric.change_pct, metric.trend);

                return `
                        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                            <div class="text-sm font-medium text-slate-500 mb-2">${metric.title}</div>
                            <div class="text-2xl font-bold text-slate-900 mb-3">${metric.value_display}</div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${pillClass}">
                                    ${changeText}
                                </span>
                                <span class="text-xs text-slate-400">vs prev. period</span>
                            </div>
                        </div>
                    `;
            }).join('');
        }

        function renderLeaderboard(rows) {
            if (!rows.length) {
                els.leaderboardBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">
                                No data available for selected filters.
                            </td>
                        </tr>
                    `;
                return;
            }

            els.leaderboardBody.innerHTML = rows.map(row => `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 pr-4 font-semibold text-slate-800">#${row.rank}</td>
                        <td class="py-3 pr-4 text-slate-700">${row.agent_name}</td>
                        <td class="py-3 pr-4 text-slate-700">${row.resolved_count}</td>
                        <td class="py-3 pr-4 text-slate-700">${row.avg_resolution_display}</td>
                        <td class="py-3 text-slate-700">${row.csat !== null ? Number(row.csat).toFixed(2) : '-'}</td>
                    </tr>
                `).join('');
        }

        function renderTicketVolumeChart(data) {
            const ctx = document.getElementById('ticketVolumeChart');

            if (analyticsState.ticketVolumeChart) {
                analyticsState.ticketVolumeChart.destroy();
            }

            analyticsState.ticketVolumeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                            label: data.datasets?.[0]?.label || 'Incoming',
                            data: data.datasets?.[0]?.data || [],
                            borderColor: '#94a3b8',
                            backgroundColor: '#94a3b8',
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBorderWidth: 2,
                            pointBackgroundColor: '#ffffff',
                            fill: false,
                        },
                        {
                            label: data.datasets?.[1]?.label || 'Resolved',
                            data: data.datasets?.[1]?.data || [],
                            borderColor: '#0f172a',
                            backgroundColor: '#0f172a',
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBorderWidth: 2,
                            pointBackgroundColor: '#ffffff',
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            max: data.y_axis_max || 10,
                            ticks: {
                                stepSize: data.step_size || 10
                            },
                            grid: {
                                color: '#e2e8f0'
                            }
                        }
                    }
                }
            });
        }

        function renderCategoryChart(data) {
            const ctx = document.getElementById('categoryChart');

            if (analyticsState.categoryChart) {
                analyticsState.categoryChart.destroy();
            }

            const values = data.values || [];

            analyticsState.categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Tickets',
                        data: values,
                        backgroundColor: '#0f172a',
                        borderRadius: 8,
                        barThickness: 18,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const item = data.items?.[context.dataIndex];
                                    const count = context.parsed.x ?? 0;
                                    const topTeam = item?.top_team ?? '-';
                                    return `Tickets: ${count} | Top Team: ${String(topTeam).toUpperCase()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: data.y_axis_max || 15,
                            ticks: {
                                stepSize: data.step_size || 5,
                                precision: 0
                            },
                            grid: {
                                color: '#e2e8f0'
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function renderPeakTimeChart(data) {
            const ctx = document.getElementById('peakTimeChart');

            if (analyticsState.peakTimeChart) {
                analyticsState.peakTimeChart.destroy();
            }

            analyticsState.peakTimeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Tickets',
                        data: data.values || [],
                        backgroundColor: '#0f172a',
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Tickets: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            max: data.y_axis_max || 10,
                            ticks: {
                                stepSize: data.step_size || 10
                            },
                            grid: {
                                color: '#e2e8f0'
                            }
                        }
                    }
                }
            });
        }

        function resolveMetricPillClass(metric) {
            if (metric.semantic === 'neutral') {
                return metric.trend === 'up' ?
                    'bg-blue-50 text-blue-700' :
                    metric.trend === 'down' ?
                    'bg-slate-100 text-slate-700' :
                    'bg-slate-100 text-slate-600';
            }

            if (metric.improved === true) return 'bg-emerald-50 text-emerald-700';
            if (metric.improved === false) return 'bg-red-50 text-red-700';

            return 'bg-slate-100 text-slate-700';
        }

        function formatChange(changePct, trend) {
            if (trend === 'flat') return '0.0%';

            const arrow = trend === 'up' ? '↑' : '↓';
            return `${arrow} ${Math.abs(changePct).toFixed(1)}%`;
        }

        function setLoading(isLoading, fromButton = false) {
            if (isLoading) {
                els.analyticsSkeleton.classList.remove('hidden');
                els.analyticsContent.classList.add('hidden');
            } else {
                els.analyticsSkeleton.classList.add('hidden');
                els.analyticsContent.classList.remove('hidden');
            }

            if (fromButton) {
                els.applyFiltersBtn.disabled = isLoading;
                els.applyBtnSpinner.classList.toggle('hidden', !isLoading);
            }
        }

        function showError(message) {
            els.analyticsError.textContent = message;
            els.analyticsError.classList.remove('hidden');
        }

        function hideError() {
            els.analyticsError.classList.add('hidden');
            els.analyticsError.textContent = '';
        }

        function destroyCharts() {
            if (analyticsState.ticketVolumeChart) analyticsState.ticketVolumeChart.destroy();
            if (analyticsState.categoryChart) analyticsState.categoryChart.destroy();
            if (analyticsState.peakTimeChart) analyticsState.peakTimeChart.destroy();

            analyticsState.ticketVolumeChart = null;
            analyticsState.categoryChart = null;
            analyticsState.peakTimeChart = null;
        }
    </script>
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
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/case-analytics/index.blade.php ENDPATH**/ ?>