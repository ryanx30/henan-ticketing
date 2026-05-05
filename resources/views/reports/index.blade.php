<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />

    <div
        x-data="reportsPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1600px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-4">
                <h1 class="text-[34px] font-bold text-[#051823]">REPORTS</h1>
            </div>

            {{-- FILTER BAR --}}
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

                            @if(auth()->user()->role === 'cs')
                                <option value="team">All CS Tickets</option>
                            @elseif(auth()->user()->role === 'it')
                                <option value="team">Team IT</option>
                            @elseif(in_array(auth()->user()->role, ['admin', 'supervisor'], true))
                                <option value="team">All CS Tickets</option>
                                <option value="all">All Tickets</option>
                            @endif
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

            {{-- KPI CARDS --}}
            <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded bg-white p-6 text-center shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="text-lg text-slate-700">Resolved / Closed</div>
                    <div class="mt-2 text-[52px] leading-none text-[#2f6f8f]" x-text="cards.resolved"></div>
                </div>

                <div class="rounded bg-white p-6 text-center shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="text-lg text-slate-700">Avg First Response (IT)</div>
                    <div class="mt-2 text-[52px] leading-none text-[#2f6f8f]" x-text="cards.avg_response_label"></div>
                </div>

                <div class="rounded bg-white p-6 text-center shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="text-lg text-slate-700">Reopen Rate (IT)</div>
                    <div class="mt-2 text-[52px] leading-none text-[#2f6f8f]" x-text="`${cards.reopen_rate}%`"></div>
                </div>

                <div class="rounded bg-white p-6 text-center shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="text-lg text-slate-700">SLA Risk</div>
                    <div class="mt-2 text-[52px] leading-none text-[#2f6f8f]" x-text="cards.sla_risk"></div>
                </div>
            </div>

            {{-- TREND --}}
            <div class="mb-5 rounded bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="mb-6 text-[28px] font-semibold text-slate-800">Trend (Resolved / Closed)</div>

                <div class="rounded border border-slate-200 bg-slate-50 p-5">
                    <div class="h-[360px]">
                        <div
                            x-show="trend.labels.length === 0"
                            class="flex h-full items-center justify-center text-sm text-slate-500">
                            No trend data available.
                        </div>

                        <div x-show="trend.labels.length > 0" class="h-full">
                            <canvas id="reportsTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="overflow-hidden rounded bg-white shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="bg-[#051823] px-5 py-3">
                    <h2 class="text-[20px] font-semibold text-white">SLA Tracking</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-800">
                        <thead class="bg-[#d5e0e7] text-[#051823]">
                            <tr class="text-left">
                                <th class="px-5 py-3 font-semibold">Ticket</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Team</th>
                                <th class="px-5 py-3 font-semibold" x-text="meta.table_labels.sla_time"></th>
                                <th class="px-5 py-3 font-semibold">Response Time</th>
                                <th class="px-5 py-3 font-semibold" x-text="meta.table_labels.result"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">Loading report data...</td>
                                </tr>
                            </template>

                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">No rows found.</td>
                                </tr>
                            </template>

                            <template x-for="(row, index) in rows" :key="row.id">
                                <tr :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'">
                                    <td class="px-5 py-3 font-medium" x-text="row.ticket_code"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="statusBadgeClass(row.status)"
                                            x-text="statusLabel(row.status)">
                                        </span>
                                    </td>

                                    <td class="px-5 py-3" x-text="row.team"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="slaTimeBadgeClass(row.sla_time, row.result)"
                                            x-text="row.sla_time">
                                        </span>
                                    </td>

                                    <td class="px-5 py-3" x-text="row.response_time"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded px-2.5 py-1 text-xs font-semibold"
                                            :class="resultBadgeClass(row.result)"
                                            x-text="row.result">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div
                    x-show="!loading && pagination.last_page > 1"
                    class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">

                    <div class="text-sm text-slate-600">
                        Showing
                        <span class="font-semibold" x-text="pagination.from ?? 0"></span>
                        -
                        <span class="font-semibold" x-text="pagination.to ?? 0"></span>
                        of
                        <span class="font-semibold" x-text="pagination.total ?? 0"></span>
                        tickets
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="rounded border px-3 py-1 text-sm"
                            :class="pagination.current_page <= 1
                                ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                            ‹
                        </button>

                        <template x-for="(item, idx) in visiblePages()" :key="`page-${idx}-${item}`">
                            <template x-if="item === '...'">
                                <span class="px-2 py-1 text-sm text-slate-500">...</span>
                            </template>

                            <template x-if="item !== '...'">
                                <button
                                    type="button"
                                    @click="goToPage(item)"
                                    class="rounded border px-3 py-1 text-sm"
                                    :class="item === pagination.current_page
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                                    <span x-text="item"></span>
                                </button>
                            </template>
                        </template>

                        <button
                            type="button"
                            @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="rounded border px-3 py-1 text-sm"
                            :class="pagination.current_page >= pagination.last_page
                                ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                            ›
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function reportsPage() {
            return {
                loading: false,

                filters: {
                    range: 'this_week',
                    date_from: '',
                    date_to: '',
                    scope: 'my',
                    per_page: '10',
                },

                cards: {
                    resolved: 0,
                    avg_response_seconds: 0,
                    avg_response_label: '0m',
                    reopen_rate: 0,
                    sla_risk: 0,
                },

                trend: {
                    labels: [],
                    values: [],
                },

                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 10,
                    total: 0,
                    from: null,
                    to: null,
                },

                meta: {
                    table_labels: {
                        sla_time: 'SLA Remaining / Outcome',
                        result: 'SLA Result',
                    },
                },

                rows: [],
                picker: null,
                trendChart: null,

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.filters.range = params.get('range') || 'this_week';
                    this.filters.date_from = params.get('date_from') || '';
                    this.filters.date_to = params.get('date_to') || '';
                    this.filters.scope = params.get('scope') || 'my';
                    this.filters.per_page = params.get('per_page') || '10';
                    this.pagination.current_page = Number(params.get('page') || 1);

                    this.$nextTick(() => {
                        this.initDatePicker();
                    });

                    this.loadReports();

                    this._resizeHandler = () => {
                        if (this.trendChart) {
                            this.trendChart.resize();
                        }
                    };

                    window.addEventListener('resize', this._resizeHandler);
                },

                destroy() {
                    if (this.trendChart) {
                        this.trendChart.destroy();
                        this.trendChart = null;
                    }

                    if (this._resizeHandler) {
                        window.removeEventListener('resize', this._resizeHandler);
                    }
                },

                onRangeChange() {
                    if (this.filters.range !== 'custom') {
                        this.filters.date_from = '';
                        this.filters.date_to = '';

                        if (this.picker) {
                            this.picker.destroy();
                            this.picker = null;
                        }
                    }

                    this.$nextTick(() => {
                        this.initDatePicker();
                    });

                    this.applyFilters();
                },

                buildQuery(includePage = true) {
                    const params = new URLSearchParams();
                    params.set('range', this.filters.range);
                    params.set('scope', this.filters.scope);
                    params.set('per_page', this.filters.per_page);

                    if (this.filters.range === 'custom') {
                        if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                        if (this.filters.date_to) params.set('date_to', this.filters.date_to);
                    }

                    if (includePage) {
                        params.set('page', this.pagination.current_page || 1);
                    }

                    return params;
                },

                applyFilters() {
                    this.pagination.current_page = 1;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadReports();
                },

                async loadReports() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const response = await fetch(`/api/reports?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load reports');
                        }

                        const data = result.data || {};
                        this.cards = data.cards || this.cards;
                        this.trend = data.trend || this.trend;
                        this.rows = data.rows || [];
                        this.pagination = data.pagination || this.pagination;
                        this.meta = data.meta || this.meta;
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load reports', 'error');
                    } finally {
                        this.loading = false;

                        this.$nextTick(() => {
                            requestAnimationFrame(() => {
                                this.renderTrendChart(0);
                            });
                        });
                    }
                },

                goToPage(page) {
                    if (page < 1 || page > this.pagination.last_page) return;

                    this.pagination.current_page = page;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadReports();
                },

                visiblePages() {
                    const current = this.pagination.current_page || 1;
                    const last = this.pagination.last_page || 1;

                    if (last <= 7) {
                        return Array.from({ length: last }, (_, i) => i + 1);
                    }

                    const pages = [1];

                    if (current > 3) {
                        pages.push('...');
                    }

                    const start = Math.max(2, current - 1);
                    const end = Math.min(last - 1, current + 1);

                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }

                    if (current < last - 2) {
                        pages.push('...');
                    }

                    pages.push(last);

                    return pages;
                },

                exportCsv() {
                    const params = this.buildQuery(false);
                    window.location.href = `/api/reports/export?${params.toString()}`;
                },

                showAlert(message, type = 'success') {
                    const el = document.getElementById('page-alert');
                    if (!el) return;

                    el.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                    el.textContent = message;

                    if (type === 'success') {
                        el.classList.add('bg-green-100', 'text-green-800');
                    } else {
                        el.classList.add('bg-red-100', 'text-red-800');
                    }

                    setTimeout(() => el.classList.add('hidden'), 3000);
                },

                initDatePicker() {
                    if (this.filters.range !== 'custom') return;

                    const trigger = document.getElementById('dateRangeTrigger');
                    if (!trigger) return;

                    if (this.picker) {
                        this.picker.destroy();
                        this.picker = null;
                    }

                    const self = this;

                    this.picker = new Litepicker({
                        element: trigger,
                        singleMode: false,
                        numberOfMonths: 2,
                        numberOfColumns: 2,
                        format: 'YYYY-MM-DD',
                        autoApply: false,
                        showTooltip: true,
                        buttonText: {
                            apply: 'Apply',
                            cancel: 'Cancel',
                            reset: 'Reset'
                        },
                        setup(picker) {
                            picker.on('selected', (date1, date2) => {
                                if (!date1 || !date2) return;

                                self.filters.date_from = self.toYmd(new Date(date1.dateInstance));
                                self.filters.date_to = self.toYmd(new Date(date2.dateInstance));
                                self.applyFilters();
                            });
                        },
                    });
                },

                toYmd(d) {
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                },

                dateLabel() {
                    if (this.filters.date_from && this.filters.date_to) {
                        return `${this.formatHumanDate(this.filters.date_from)} → ${this.formatHumanDate(this.filters.date_to)}`;
                    }
                    return 'Select date range';
                },

                formatHumanDate(value) {
                    const date = new Date(value);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },

                statusLabel(status) {
                    const map = {
                        new: 'New',
                        in_progress: 'On Going',
                        waiting_info: 'Waiting',
                        resolved: 'Resolved',
                        closed: 'Closed',
                    };
                    return map[status] || status || '-';
                },

                statusBadgeClass(status) {
                    switch (status) {
                        case 'new':
                            return 'bg-blue-100 text-blue-700';
                        case 'in_progress':
                            return 'bg-amber-100 text-amber-700';
                        case 'waiting_info':
                            return 'bg-purple-100 text-purple-700';
                        case 'resolved':
                            return 'bg-green-100 text-green-700';
                        case 'closed':
                            return 'bg-slate-200 text-slate-700';
                        default:
                            return 'bg-slate-100 text-slate-700';
                    }
                },

                resultBadgeClass(result) {
                    if (result === 'OK') return 'bg-green-500 text-white';
                    if (result === 'Breach') return 'bg-red-500 text-white';
                    if (result === 'Open') return 'bg-blue-100 text-blue-700';
                    if (result === 'Closed') return 'bg-slate-500 text-white';
                    return 'bg-slate-200 text-slate-700';
                },

                slaTimeBadgeClass(slaTime, result) {
                    if (!slaTime || slaTime === '-') {
                        return 'bg-slate-100 text-slate-600';
                    }

                    if (result === 'Breach' || String(slaTime).startsWith('Overdue') || String(slaTime).startsWith('Breached by')) {
                        return 'bg-red-50 text-red-700';
                    }

                    if (result === 'OK' || String(slaTime).startsWith('Met by')) {
                        return 'bg-green-50 text-green-700';
                    }

                    if (result === 'Closed' || String(slaTime).startsWith('Direct close')) {
                        return 'bg-slate-100 text-slate-700';
                    }

                    if (result === 'Open' || String(slaTime).endsWith('left') || String(slaTime).startsWith('No SLA')) {
                        return 'bg-blue-50 text-blue-700';
                    }

                    return 'bg-slate-100 text-slate-700';
                },

                computedYMax() {
                    const values = Array.isArray(this.trend.values) ? this.trend.values : [];
                    const maxValue = Math.max(...values, 0);

                    if (maxValue <= 10) return 10;
                    return Math.ceil(maxValue / 10) * 10;
                },

                renderTrendChart(retry = 0) {
                    const canvas = document.getElementById('reportsTrendChart');

                    if (!canvas) {
                        if (retry < 30) {
                            setTimeout(() => this.renderTrendChart(retry + 1), 200);
                        }
                        return;
                    }

                    if (typeof Chart === 'undefined') {
                        if (retry < 30) {
                            setTimeout(() => this.renderTrendChart(retry + 1), 200);
                        }
                        return;
                    }

                    if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
                        if (retry < 30) {
                            setTimeout(() => this.renderTrendChart(retry + 1), 200);
                        }
                        return;
                    }

                    const labels = this.trend.labels || [];
                    const values = this.trend.values || [];

                    if (!labels.length) {
                        if (this.trendChart) {
                            this.trendChart.destroy();
                            this.trendChart = null;
                        }
                        return;
                    }

                    if (this.trendChart) {
                        this.trendChart.destroy();
                        this.trendChart = null;
                    }

                    const yMax = this.computedYMax();

                    this.trendChart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Resolved / Closed Tickets',
                                data: values,
                                borderColor: '#051823',
                                backgroundColor: '#051823',
                                pointBackgroundColor: '#051823',
                                pointBorderColor: '#051823',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointHitRadius: 14,
                                borderWidth: 3,
                                tension: 0,
                                fill: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            interaction: {
                                mode: 'nearest',
                                intersect: true,
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: '#051823',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    displayColors: false,
                                    callbacks: {
                                        title: (items) => items?.[0]?.label || '',
                                        label: (context) => `${context.parsed.y} ticket(s)`,
                                    }
                                }
                            },
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 10,
                                    top: 10,
                                    bottom: 0,
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: '#e5e7eb',
                                    },
                                    ticks: {
                                        color: '#334155',
                                        font: {
                                            size: 11,
                                        }
                                    },
                                    border: {
                                        color: '#94a3b8',
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    max: yMax,
                                    ticks: {
                                        stepSize: 10,
                                        color: '#334155',
                                        font: {
                                            size: 11,
                                        },
                                        precision: 0,
                                    },
                                    grid: {
                                        color: '#e5e7eb',
                                    },
                                    border: {
                                        color: '#94a3b8',
                                    }
                                }
                            }
                        }
                    });
                },
            }
        }
    </script>

    <style>
        .litepicker {
            font-family: inherit;
            font-size: 13px;
            --litepicker-container-months-color-bg: #ffffff;
            --litepicker-month-header-color: #0f172a;
            --litepicker-button-prev-month-color: #64748b;
            --litepicker-button-next-month-color: #64748b;
            --litepicker-button-prev-month-color-hover: #0f172a;
            --litepicker-button-next-month-color-hover: #0f172a;
            --litepicker-month-week-day-color: #94a3b8;
            --litepicker-day-color: #1e293b;
            --litepicker-day-color-hover: #0f172a;
            --litepicker-is-today-color: #2f88d8;
            --litepicker-is-in-range-color: #1e293b;
            --litepicker-is-in-range-color-bg: #dbeafe;
            --litepicker-is-start-color: #ffffff;
            --litepicker-is-start-color-bg: #2f88d8;
            --litepicker-is-end-color: #ffffff;
            --litepicker-is-end-color-bg: #2f88d8;
            --litepicker-button-apply-color-bg: #2f88d8;
            --litepicker-button-cancel-color-bg: #e2e8f0;
            --litepicker-button-cancel-color: #475569;
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.15);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .litepicker .container__months {
            background: #ffffff;
        }

        .litepicker .container__footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 8px 16px;
        }

        .litepicker .month-item-header div>.month-item-name,
        .litepicker .month-item-header div>.month-item-year {
            color: #0f172a;
            font-weight: 600;
        }

        .litepicker .day-item {
            color: #1e293b;
            width: 32px !important;
            height: 32px !important;
            line-height: 32px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .litepicker .day-item:hover {
            background-color: #e2e8f0;
            color: #0f172a;
            border-radius: 50% !important;
        }

        .litepicker .day-item.is-today {
            color: #2f88d8;
            font-weight: 700;
            border: 1.5px solid #2f88d8;
            border-radius: 50% !important;
            background: transparent;
        }

        .litepicker .day-item.is-start-date,
        .litepicker .day-item.is-end-date {
            background-color: #2f88d8 !important;
            color: #fff !important;
            border-radius: 50% !important;
            border: none !important;
        }

        .litepicker .day-item.is-in-range {
            background-color: #bfdbfe !important;
            color: #1e40af !important;
            border-radius: 0 !important;
        }
    </style>
</x-app-layout>