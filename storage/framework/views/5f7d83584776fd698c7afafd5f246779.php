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
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <div
        x-data="dashboardItPage()"
        x-init="init()"
        class="p-6 bg-slate-100 min-h-screen">

        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        <div class="grid grid-cols-12 gap-6">

            
            <div class="col-span-12 lg:col-span-3">
                <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="space-y-5">

                        
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.total.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">Total Tickets</div>
                                </div>
                                <img src="<?php echo e(asset('images/icons/total.png')); ?>" alt="Total Tickets" class="h-10 w-10 object-contain opacity-90" />
                            </div>

                            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.total.prev_month)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.total.mom)"></span>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.total.prev_year)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.total.yoy)"></span>
                                </div>
                            </div>
                        </div>

                        
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.new.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">New Tickets</div>
                                </div>
                                <img src="<?php echo e(asset('images/icons/new.png')); ?>" alt="New Tickets" class="h-10 w-10 object-contain opacity-90" />
                            </div>

                            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.new.prev_month)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.new.mom)"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.new.prev_year)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.new.yoy)"></span>
                                </div>
                            </div>
                        </div>

                        
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.in_progress.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">On Going</div>
                                </div>
                                <img src="<?php echo e(asset('images/icons/ongoing.png')); ?>" alt="On Going" class="h-10 w-10 object-contain opacity-90" />
                            </div>

                            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.in_progress.prev_month)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.in_progress.mom)"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.in_progress.prev_year)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.in_progress.yoy)"></span>
                                </div>
                            </div>
                        </div>

                        
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.resolved.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">Resolved</div>
                                </div>
                                <img src="<?php echo e(asset('images/icons/resolved.png')); ?>" alt="Resolved" class="h-10 w-10 object-contain opacity-90" />
                            </div>

                            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.resolved.prev_month)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.resolved.mom)"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.resolved.prev_year)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.resolved.yoy)"></span>
                                </div>
                            </div>
                        </div>

                        
                        <div class="overflow-hidden rounded-sm border border-slate-200 shadow-lg">
                            <div class="min-h-[74px] bg-red-600 px-4 py-4 text-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-[28px] font-bold leading-none" x-text="formatNumber(kpi.sla_risk.value)"></div>
                                        <div class="mt-1 text-[16px]">SLA Risk</div>
                                    </div>
                                    <img src="<?php echo e(asset('images/icons/sla.png')); ?>" alt="SLA Risk" class="h-10 w-10 object-contain" />
                                </div>
                            </div>

                            <div class="space-y-1.5 bg-white px-4 py-4 text-[16px] text-slate-700">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.sla_risk.prev_month)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.sla_risk.mom)"></span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.sla_risk.prev_year)"></b></span>
                                    <span class="rounded bg-slate-200 px-1.5 py-[1px] text-[16px] text-slate-800" x-text="trendText(kpi.sla_risk.yoy)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="mt-3 rounded-md border border-slate-200 bg-white p-4 shadow-lg">
                    <div class="mb-3 text-sm font-semibold text-slate-800">Status Legend</div>

                    <div class="grid grid-cols-2 gap-2 text-sm text-slate-700">
                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="h-3 w-3 rounded-full bg-gray-400"></span>
                            <span>New</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                            <span>On Going</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="h-3 w-3 rounded-full bg-green-500"></span>
                            <span>Resolved</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="h-3 w-3 rounded-full bg-sky-600"></span>
                            <span>Closed</span>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-span-12 lg:col-span-9 space-y-6 bg-white rounded shadow p-4">

                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    
                    <div class="bg-white rounded shadow overflow-hidden">
                        <div class="px-4 py-3 border-b bg-slate-50 font-semibold">
                            Top Cases (All Team)
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-white bg-slate-900">
                                        <th class="py-2 px-3 w-[48px]">#</th>
                                        <th class="px-3">Issue Type/Tag</th>
                                        <th class="px-3 w-[90px]">Count</th>
                                        <th class="px-3 w-[110px]">Top Team</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="loading && topCases.length === 0">
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-gray-500">Loading data...</td>
                                        </tr>
                                    </template>

                                    <template x-if="!loading && topCases.length === 0">
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-gray-500">No data.</td>
                                        </tr>
                                    </template>

                                    <template x-for="(row, index) in topCases" :key="row.issue_type + '-' + index">
                                        <tr class="border-b">
                                            <td class="py-2 px-3" x-text="index + 1"></td>
                                            <td class="px-3" x-text="row.issue_type"></td>
                                            <td class="px-3" x-text="row.count"></td>
                                            <td class="px-3 uppercase" x-text="row.top_team"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    
                    <div class="bg-white rounded shadow overflow-hidden">
                        <div class="px-4 py-3 border-b bg-slate-50 font-semibold">
                            Tickets Trend (Last 7 days)
                        </div>

                        <div class="p-4">
                            <div class="h-[240px] w-full">
                                <canvas id="trendChart"></canvas>
                            </div>

                            <div class="mt-3 text-xs text-gray-500">
                                Data source: jumlah ticket dibuat per hari, dikelompokkan berdasarkan team.
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="px-4 py-3 flex items-center justify-between border-b bg-slate-50">
                        <div class="font-semibold">My Queue</div>
                        <a href="<?php echo e(route('it.my-queue')); ?>" class="text-sm underline text-gray-600">Open</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Priority</th>
                                    <th class="px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Status</th>
                                    <th class="px-3 text-center">SLA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="loading && itMyQueue.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500">Loading my queue...</td>
                                    </tr>
                                </template>

                                <template x-if="!loading && itMyQueue.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500">No tickets in my queue.</td>
                                    </tr>
                                </template>

                                <template x-for="t in itMyQueue" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-2 px-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 font-mono whitespace-nowrap" x-text="'#T-' + (t.ticket_code ?? t.id)"></td>

                                        <td class="px-3 max-w-[420px] truncate" x-text="t.title"></td>

                                        <td class="px-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="statusBadgeClass(t.status)"
                                                x-text="statusLabel(t.status)"></span>
                                        </td>

                                        <td class="px-3 text-center w-[130px]">
                                            <span class="font-mono tabular-nums inline-block text-center w-[110px]" x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="px-4 py-3 flex items-center justify-between border-b bg-slate-50">
                        <div class="font-semibold">Team Queue (New)</div>
                        <a href="<?php echo e(route('it.team-queue')); ?>" class="text-sm underline text-gray-600">Open</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-7">Priority</th>
                                    <th class="px-7">Ticket</th>
                                    <th class="px-7">Subject</th>
                                    <th class="px-7 text-center">SLA</th>
                                    <th class="px-7 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="loading && itTeamNew.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500">Loading new team tickets...</td>
                                    </tr>
                                </template>

                                <template x-if="!loading && itTeamNew.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500">No new team tickets.</td>
                                    </tr>
                                </template>

                                <template x-for="t in itTeamNew" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-2 px-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 font-mono whitespace-nowrap" x-text="'#T-' + (t.ticket_code ?? t.id)"></td>

                                        <td class="px-3 max-w-[420px] truncate" x-text="t.title"></td>

                                        <td class="px-3 text-center w-[130px]">
                                            <span class="font-mono tabular-nums inline-block text-center w-[110px]" x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>

                                        <td class="px-3 text-right">
                                            <button
                                                type="button"
                                                @click="claimTicket(t.id)"
                                                class="px-3 py-1 rounded bg-slate-900 text-white text-xs">
                                                Claim
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        function dashboardItPage() {
            return {
                loading: false,
                timer: null,
                chart: null,

                kpi: {
                    total: {
                        value: 0,
                        prev_month: 0,
                        prev_year: 0,
                        mom: {},
                        yoy: {}
                    },
                    new: {
                        value: 0,
                        prev_month: 0,
                        prev_year: 0,
                        mom: {},
                        yoy: {}
                    },
                    in_progress: {
                        value: 0,
                        prev_month: 0,
                        prev_year: 0,
                        mom: {},
                        yoy: {}
                    },
                    resolved: {
                        value: 0,
                        prev_month: 0,
                        prev_year: 0,
                        mom: {},
                        yoy: {}
                    },
                    sla_risk: {
                        value: 0,
                        prev_month: 0,
                        prev_year: 0,
                        mom: {},
                        yoy: {}
                    },
                },

                itMyQueue: [],
                itTeamNew: [],
                topCases: [],
                trend: {
                    labels: [],
                    it: [],
                    finance: [],
                    compliance: [],
                },

                init() {
                    this.loadDashboard();

                    this.timer = setInterval(() => {
                        this.itMyQueue = [...this.itMyQueue];
                        this.itTeamNew = [...this.itTeamNew];
                    }, 1000);

                    this._resizeHandler = () => {
                        if (this.chart) {
                            this.chart.resize();
                        }
                    };

                    window.addEventListener('resize', this._resizeHandler);
                },

                destroy() {
                    if (this.timer) clearInterval(this.timer);

                    if (this.chart) {
                        this.chart.destroy();
                        this.chart = null;
                    }

                    if (this._resizeHandler) {
                        window.removeEventListener('resize', this._resizeHandler);
                    }
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },

                showAlert(message, type = 'success') {
                    const el = document.getElementById('page-alert');
                    el.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                    el.textContent = message;

                    if (type === 'success') {
                        el.classList.add('bg-green-100', 'text-green-800');
                    } else {
                        el.classList.add('bg-red-100', 'text-red-800');
                    }

                    setTimeout(() => {
                        el.classList.add('hidden');
                    }, 3000);
                },

                async loadDashboard() {
                    this.loading = true;

                    try {
                        const response = await fetch('/api/dashboard', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load dashboard');
                        }

                        const data = result.data || {};

                        this.kpi = data.kpi || this.kpi;
                        this.itMyQueue = data.it_my_queue || [];
                        this.itTeamNew = data.it_team_new || [];
                        this.topCases = data.top_cases || [];
                        this.trend = data.trend || this.trend;
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load dashboard', 'error');
                    } finally {
                        this.loading = false;

                        this.$nextTick(() => {
                            requestAnimationFrame(() => {
                                this.renderChart(0);
                            });
                        });
                    }
                },

                renderChart(retry = 0) {
                    const el = document.getElementById('trendChart');
                    if (!el) {
                        if (retry < 30) {
                            setTimeout(() => this.renderChart(retry + 1), 200);
                        }
                        return;
                    }

                    // tunggu Chart.js siap
                    if (typeof Chart === 'undefined') {
                        if (retry < 30) {
                            setTimeout(() => this.renderChart(retry + 1), 200);
                        }
                        return;
                    }

                    // tunggu canvas benar-benar punya ukuran
                    if (el.offsetWidth === 0 || el.offsetHeight === 0) {
                        if (retry < 30) {
                            setTimeout(() => this.renderChart(retry + 1), 200);
                        }
                        return;
                    }

                    const labels = this.trend.labels || [];
                    if (!labels.length) {
                        if (this.chart) {
                            this.chart.destroy();
                            this.chart = null;
                        }
                        return;
                    }

                    if (this.chart) {
                        this.chart.destroy();
                        this.chart = null;
                    }

                    this.chart = new Chart(el, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                    label: 'IT',
                                    data: this.trend.it || [],
                                    tension: 0.35,
                                    fill: true,
                                    borderWidth: 2,
                                    borderColor: '#0f172a',
                                    backgroundColor: 'rgba(15, 23, 42, 0.12)',
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    pointBackgroundColor: '#0f172a',
                                    pointBorderColor: '#0f172a',
                                    pointHoverBackgroundColor: '#0f172a',
                                    pointHoverBorderColor: '#0f172a',
                                },
                                {
                                    label: 'Finance',
                                    data: this.trend.finance || [],
                                    tension: 0.35,
                                    fill: true,
                                    borderWidth: 2,
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'rgba(245, 158, 11, 0.12)',
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    pointBackgroundColor: '#f59e0b',
                                    pointBorderColor: '#f59e0b',
                                    pointHoverBackgroundColor: '#f59e0b',
                                    pointHoverBorderColor: '#f59e0b',
                                },
                                {
                                    label: 'Compliance',
                                    data: this.trend.compliance || [],
                                    tension: 0.35,
                                    fill: true,
                                    borderWidth: 2,
                                    borderColor: '#16a34a',
                                    backgroundColor: 'rgba(22, 163, 74, 0.12)',
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    pointBackgroundColor: '#16a34a',
                                    pointBorderColor: '#16a34a',
                                    pointHoverBackgroundColor: '#16a34a',
                                    pointHoverBorderColor: '#16a34a',
                                },
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                },
                            },
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                },

                async claimTicket(ticketId) {
                    try {
                        const response = await fetch(`/api/it/tickets/${ticketId}/claim`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to claim ticket');
                        }

                        this.showAlert(result.message || 'Ticket claimed successfully', 'success');
                        await this.loadDashboard();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to claim ticket', 'error');
                    }
                },

                formatNumber(value) {
                    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
                },

                trendText(item) {
                    if (!item) return '-';
                    const arrow = item.direction === 'up' ? '▲' : (item.direction === 'down' ? '▼' : '•');
                    return `${item.label ?? '-'} ${arrow}`;
                },

                ucfirst(value) {
                    if (!value) return '-';
                    value = String(value);
                    return value.charAt(0).toUpperCase() + value.slice(1);
                },

                statusLabel(status) {
                    const map = {
                        new: 'New',
                        in_progress: 'On Going',
                        waiting_info: 'Waiting Info',
                        resolved: 'Resolved',
                        closed: 'Closed',
                    };
                    return map[status] || status || '-';
                },

                statusBadgeClass(status) {
                    switch (status) {
                        case 'new':
                            return 'bg-gray-200 text-gray-800';
                        case 'in_progress':
                            return 'bg-amber-100 text-amber-700';
                        case 'waiting_info':
                            return 'bg-orange-100 text-orange-700';
                        case 'resolved':
                            return 'bg-green-100 text-green-700';
                        case 'closed':
                            return 'bg-sky-100 text-sky-700';
                        default:
                            return 'bg-slate-100 text-slate-700';
                    }
                },

                priorityBadgeClass(priority) {
                    switch (priority) {
                        case 'critical':
                            return 'bg-red-100 text-red-700';
                        case 'high':
                            return 'bg-pink-100 text-pink-700';
                        case 'medium':
                            return 'bg-amber-100 text-amber-700';
                        case 'low':
                            return 'bg-green-100 text-green-700';
                        default:
                            return 'bg-slate-100 text-slate-700';
                    }
                },

                slaCountdown(deadline) {
                    if (!deadline) return '-';

                    const end = new Date(deadline).getTime();
                    const now = Date.now();
                    const diff = end - now;

                    if (diff <= 0) return 'OVERDUE';

                    const totalSeconds = Math.floor(diff / 1000);
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;

                    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }
            }
        }
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/dashboard-it.blade.php ENDPATH**/ ?>