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
    <div
        x-data="dashboardCsPage()"
        x-init="init()"
        class="min-h-screen bg-slate-100 p-6">

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

            
            <div class="col-span-12 space-y-6 lg:col-span-9">
                <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="space-y-6">

                        
                        <div class="rounded bg-white p-4 shadow-lg">
                            <div class="mb-3 text-xl font-semibold">Today's Focus</div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <div class="relative rounded-xl bg-gradient-to-r from-red-600 to-red-800 p-4 text-white shadow-lg">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow" x-text="focus.sla"></span>
                                    <div class="text-lg text-center font-bold leading-tight">SLA &lt; 30m</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Critical - Act Now)</div>
                                </div>

                                <div class="relative rounded-xl bg-gradient-to-r from-orange-400 to-orange-600 p-4 text-white shadow-lg">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow" x-text="focus.due_today"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Due Today</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Clear Before EOD)</div>
                                </div>

                                <div class="relative rounded-xl bg-gradient-to-r from-yellow-400 to-yellow-500 p-4 text-white shadow-lg">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow" x-text="focus.pending_user"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Pending User</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Follow up)</div>
                                </div>

                                <div class="relative rounded-xl bg-gradient-to-r from-sky-400 to-blue-700 p-4 text-white shadow-lg">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow" x-text="focus.reopened"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Reopened</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Review & Resolve)</div>
                                </div>
                            </div>

                            <div class="mt-4 text-md font-semibold">Quick Actions:</div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <a href="<?php echo e(route('tickets.create')); ?>" class="rounded bg-slate-900 px-4 py-2 text-sm text-white shadow">+ Create Ticket</a>
                                <button class="rounded border bg-white px-4 py-2 text-sm">Knowledge Base</button>
                                <button class="rounded border bg-white px-4 py-2 text-sm">Bulk Action</button>
                            </div>
                        </div>

                        
                        <div class="overflow-hidden rounded bg-white shadow-lg">
                            <div class="flex items-center justify-between border-b bg-slate-50 px-4 py-3">
                                <div class="text-xl font-semibold">My Active Tickets</div>

                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <label class="text-gray-500">Priority</label>
                                    <select x-model="filters.priority" @change="applyFilters()" class="h-10 min-w-[100px] rounded-lg border border-gray-300 bg-white pl-3 text-sm">
                                        <option value="all">All</option>
                                        <option value="critical">Critical</option>
                                        <option value="high">High</option>
                                        <option value="medium">Medium</option>
                                        <option value="low">Low</option>
                                    </select>

                                    <label class="text-gray-500">Status</label>
                                    <select x-model="filters.status" @change="applyFilters()" class="h-10 min-w-[100px] rounded-lg border border-gray-300 bg-white pl-3 text-sm">
                                        <option value="all">All</option>
                                        <option value="new">New</option>
                                        <option value="in_progress">On Going</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>

                                    <label class="text-gray-500">SLA</label>
                                    <select x-model="filters.sla" @change="applyFilters()" class="h-10 min-w-[100px] rounded-lg border border-gray-300 bg-white pl-3 text-sm">
                                        <option value="all">All</option>
                                        <option value="lt_1h">&lt; 1 hour</option>
                                        <option value="1h_4h">1-4 hours</option>
                                        <option value="gt_4h">&gt; 4 hours</option>
                                    </select>

                                    <label class="text-gray-500">Sort</label>
                                    <select x-model="filters.sort" @change="applyFilters()" class="h-10 min-w-[100px] rounded-lg border border-gray-300 bg-white pl-3 text-sm">
                                        <option value="latest">Latest</option>
                                        <option value="oldest">Oldest</option>
                                    </select>

                                    <button type="button" @click="resetTicketFilters()" class="ml-2 underline text-gray-500">Reset</button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-slate-900 text-left text-white">
                                            <th class="px-3 py-2">Priority</th>
                                            <th class="px-3">Ticket</th>
                                            <th class="px-3">Subject</th>
                                            <th class="px-3">Team</th>
                                            <th class="px-3">Status</th>
                                            <th class="px-3">SLA</th>
                                            <th class="px-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="loading && activeTickets.length === 0">
                                            <tr>
                                                <td colspan="7" class="py-8 text-center text-gray-500">Loading active tickets...</td>
                                            </tr>
                                        </template>

                                        <template x-if="!loading && activeTickets.length === 0">
                                            <tr>
                                                <td colspan="7" class="py-8 text-center text-gray-500">No active tickets.</td>
                                            </tr>
                                        </template>

                                        <template x-for="t in activeTickets" :key="t.id">
                                            <tr class="border-b">
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                          :class="priorityBadgeClass(t.priority)"
                                                          x-text="ucfirst(t.priority)"></span>
                                                </td>
                                                <td class="font-mono px-3" x-text="'#T-' + (t.ticket_code ?? t.id)"></td>
                                                <td class="max-w-[260px] truncate px-3" x-text="t.title"></td>
                                                <td class="px-3 uppercase" x-text="t.team"></td>
                                                <td class="px-3">
                                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                          :class="statusBadgeClass(t.status)"
                                                          x-text="statusLabel(t.status)"></span>
                                                </td>
                                                <td class="w-[140px] px-3">
                                                    <div class="flex justify-center">
                                                        <span class="inline-block w-[110px] font-mono tabular-nums text-slate-800"
                                                              x-text="slaCountdown(t.sla_deadline_at)"></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 text-right">
                                                    <a :href="`/tickets/${t.id}/edit`" class="rounded border bg-slate-100 px-3 py-1 text-xs">Open</a>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        
                        <div class="overflow-hidden rounded bg-white shadow-lg">
                            <div class="flex items-center justify-between border-b bg-slate-50 px-4 py-4">
                                <div class="text-xl font-semibold">Resolver Update Inbox</div>

                                <div class="flex items-center gap-2 text-xs">
                                    <label class="text-sm text-gray-500">Time</label>
                                    <select x-model="filters.inbox_period" @change="applyFilters()" class="h-10 min-w-[100px] rounded-lg border border-gray-300 bg-white pl-3 text-sm">
                                        <option value="today">Today</option>
                                        <option value="7d">Last 7 days</option>
                                        <option value="30d">Last 30 days</option>
                                        <option value="all">All time</option>
                                    </select>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-slate-900 text-left text-white">
                                            <th class="px-3 py-2">Time</th>
                                            <th class="px-3">Ticket</th>
                                            <th class="px-3">Update</th>
                                            <th class="px-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-if="loading && resolverInbox.length === 0">
                                            <tr>
                                                <td colspan="4" class="py-8 text-center text-gray-500">Loading inbox updates...</td>
                                            </tr>
                                        </template>

                                        <template x-if="!loading && resolverInbox.length === 0">
                                            <tr>
                                                <td colspan="4" class="py-8 text-center text-gray-500">No inbox updates.</td>
                                            </tr>
                                        </template>

                                        <template x-for="m in resolverInbox" :key="m.id">
                                            <tr class="border-b">
                                                <td class="px-3 py-2" x-text="formatTime(m.created_at)"></td>
                                                <td class="px-3" x-text="'#T-' + (m.ticket?.ticket_code ?? m.ticket?.id ?? '-')"></td>
                                                <td class="max-w-[520px] truncate px-3" x-text="truncate(m.subject || m.body || '-', 70)"></td>
                                                <td class="space-x-2 px-3 text-right">
                                                    <a :href="`/resolver-inbox/${m.id}`" class="rounded border bg-slate-100 px-3 py-1 text-xs">Open</a>
                                                    <button type="button"
                                                            @click="copyText(m.subject || m.body || '')"
                                                            class="rounded border bg-slate-100 px-3 py-1 text-xs">
                                                        Copy
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

        </div>
    </div>

    <script>
        function dashboardCsPage() {
            return {
                loading: false,
                timer: null,

                kpi: {
                    total: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
                    new: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
                    in_progress: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
                    resolved: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
                    sla_risk: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
                },

                focus: {
                    sla: 0,
                    due_today: 0,
                    pending_user: 0,
                    reopened: 0,
                },

                activeTickets: [],
                resolverInbox: [],

                filters: {
                    priority: 'all',
                    status: 'all',
                    sla: 'all',
                    sort: 'latest',
                    inbox_period: 'today',
                },

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.filters.priority = params.get('priority') || 'all';
                    this.filters.status = params.get('status') || 'all';
                    this.filters.sla = params.get('sla') || 'all';
                    this.filters.sort = params.get('sort') || 'latest';
                    this.filters.inbox_period = params.get('inbox_period') || 'today';

                    this.loadDashboard();

                    this.timer = setInterval(() => {
                        this.activeTickets = [...this.activeTickets];
                    }, 1000);
                },

                destroy() {
                    if (this.timer) clearInterval(this.timer);
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

                buildQuery() {
                    const params = new URLSearchParams();
                    params.set('priority', this.filters.priority);
                    params.set('status', this.filters.status);
                    params.set('sla', this.filters.sla);
                    params.set('sort', this.filters.sort);
                    params.set('inbox_period', this.filters.inbox_period);
                    return params;
                },

                applyFilters() {
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadDashboard();
                },

                resetTicketFilters() {
                    this.filters.priority = 'all';
                    this.filters.status = 'all';
                    this.filters.sla = 'all';
                    this.filters.sort = 'latest';
                    this.applyFilters();
                },

                async loadDashboard() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const response = await fetch(`/api/dashboard?${params.toString()}`, {
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
                        this.focus = data.focus || this.focus;
                        this.activeTickets = data.active_tickets || [];
                        this.resolverInbox = data.resolver_inbox || [];
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load dashboard', 'error');
                    } finally {
                        this.loading = false;
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

                truncate(value, limit = 70) {
                    value = value || '';
                    if (value.length <= limit) return value;
                    return value.substring(0, limit) + '...';
                },

                formatTime(value) {
                    if (!value) return '-';
                    return new Date(value).toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                    });
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
                        case 'new': return 'bg-gray-200 text-gray-800';
                        case 'in_progress': return 'bg-amber-100 text-amber-700';
                        case 'waiting_info': return 'bg-orange-100 text-orange-700';
                        case 'resolved': return 'bg-green-100 text-green-700';
                        case 'closed': return 'bg-sky-100 text-sky-700';
                        default: return 'bg-slate-100 text-slate-700';
                    }
                },

                priorityBadgeClass(priority) {
                    switch (priority) {
                        case 'critical': return 'bg-red-100 text-red-700';
                        case 'high': return 'bg-pink-100 text-pink-700';
                        case 'medium': return 'bg-amber-100 text-amber-700';
                        case 'low': return 'bg-green-100 text-green-700';
                        default: return 'bg-slate-100 text-slate-700';
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
                },

                async copyText(text) {
                    try {
                        await navigator.clipboard.writeText(text || '');
                        this.showAlert('Copied to clipboard', 'success');
                    } catch (error) {
                        console.error(error);
                        this.showAlert('Failed to copy text', 'error');
                    }
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
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/dashboard-cs.blade.php ENDPATH**/ ?>