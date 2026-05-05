<x-app-layout>
    <div
        x-data="dashboardCsPage()"
        x-init="init()"
        class="min-h-screen bg-slate-100 p-6">

        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        <div class="grid grid-cols-12 gap-6">

            {{-- LEFT KPI COLUMN --}}
            <div class="col-span-12 lg:col-span-3">
                <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="space-y-5">

                        {{-- Total Tickets --}}
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.total.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">Total Tickets</div>
                                </div>
                                <img src="{{ asset('images/icons/total.png') }}" alt="Total Tickets" class="h-10 w-10 object-contain opacity-90" />
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

                        {{-- New Tickets --}}
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.new.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">New Tickets</div>
                                </div>
                                <img src="{{ asset('images/icons/new.png') }}" alt="New Tickets" class="h-10 w-10 object-contain opacity-90" />
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

                        {{-- On Going --}}
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.in_progress.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">On Going</div>
                                </div>
                                <img src="{{ asset('images/icons/ongoing.png') }}" alt="On Going" class="h-10 w-10 object-contain opacity-90" />
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

                        {{-- Resolved --}}
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.resolved.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">Resolved</div>
                                </div>
                                <img src="{{ asset('images/icons/resolved.png') }}" alt="Resolved" class="h-10 w-10 object-contain opacity-90" />
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

                        {{-- SLA Risk --}}
                        <div class="overflow-hidden rounded-sm border border-slate-200 shadow-lg">
                            <div class="min-h-[74px] bg-red-600 px-4 py-4 text-white">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-[28px] font-bold leading-none" x-text="formatNumber(kpi.sla_risk.value)"></div>
                                        <div class="mt-1 text-[16px]">SLA Risk</div>
                                    </div>
                                    <img src="{{ asset('images/icons/sla.png') }}" alt="SLA Risk" class="h-10 w-10 object-contain" />
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

                {{-- Status Legend --}}
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

            {{-- RIGHT MAIN COLUMN --}}
            <div class="col-span-12 space-y-6 lg:col-span-9">
                <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="space-y-6">

                        {{-- Today's Focus --}}
                        <div class="rounded bg-white p-4 shadow-lg">
                            <div class="mb-3 text-xl font-semibold">Today's Focus</div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <a
                                    :href="focusLink('sla_risk')"
                                    class="group relative rounded-xl bg-gradient-to-r from-red-600 to-red-800 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-110"
                                    title="View SLA Risk Tickets">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.sla"></span>
                                    <div class="text-lg text-center font-bold leading-tight">SLA &lt; 30m</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Critical - Act Now)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>

                                <a
                                    :href="focusLink('due_today')"
                                    class="group relative rounded-xl bg-gradient-to-r from-orange-400 to-orange-600 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-105"
                                    title="View Tickets Due Today">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.due_today"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Due Today</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Clear Before EOD)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>

                                <a
                                    :href="focusLink('pending_user')"
                                    class="group relative rounded-xl bg-gradient-to-r from-yellow-400 to-yellow-500 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-105"
                                    title="View Pending User Tickets">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.pending_user"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Pending User</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Follow up)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>

                                <a
                                    :href="focusLink('reopened')"
                                    class="group relative rounded-xl bg-gradient-to-r from-sky-400 to-blue-700 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-105"
                                    title="View Reopened Tickets">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.reopened"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Reopened</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Review & Resolve)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>
                            </div>

                            <div class="mt-4 text-md font-semibold">Quick Actions:</div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <a
                                    href="{{ route('tickets.create') }}"
                                    class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm transition duration-200 hover:border-slate-900 hover:bg-slate-900 hover:text-white hover:shadow-md">
                                    + Create Ticket
                                </a>
                            </div>
                        </div>

                        {{-- My Active Tickets --}}
                        <div class="overflow-hidden rounded bg-white shadow-lg">
                            <div class="flex items-center justify-between border-b bg-slate-50 px-4 py-3">
                                <div class="text-xl font-semibold">Active Tickets</div>

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

                            <div class="max-h-[560px] overflow-auto">
                                <table class="w-full min-w-[960px] text-sm">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-slate-900 text-left text-white">
                                            <th class="w-[100px] px-3 py-2">Priority</th>
                                            <th class="w-[150px] px-2">Ticket</th>
                                            <th class="px-2">Subject</th>
                                            <th class="w-[135px] px-2">Team</th>
                                            <th class="w-[120px] px-2 text-center">Status</th>
                                            <th class="w-[115px] px-2 text-center">SLA</th>
                                            <th class="w-[80px] px-2 text-right">Action</th>
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
                                                <td class="whitespace-nowrap px-2 font-mono" x-text="ticketLabel(t)"></td>
                                                <td class="max-w-[340px] truncate px-2" x-text="t.title"></td>
                                                <td class="whitespace-nowrap px-2 uppercase" x-text="t.team"></td>
                                                <td class="whitespace-nowrap px-2 text-center">
                                                    <span class="inline-flex min-w-[82px] items-center justify-center whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium leading-none"
                                                        :class="statusBadgeClass(t.status)"
                                                        x-text="statusLabel(t.status)"></span>
                                                </td>
                                                <td class="whitespace-nowrap px-2 text-center">
                                                    <span class="inline-block w-[90px] font-mono tabular-nums text-slate-800"
                                                        x-text="slaCountdown(t.sla_deadline_at)"></span>
                                                </td>
                                                <td class="whitespace-nowrap px-2 text-right">
                                                    <a :href="`/tickets/${t.id}`" class="rounded border bg-slate-100 px-3 py-1 text-xs">Open</a>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Resolver Update Inbox --}}
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
                                                <td class="min-w-[150px] whitespace-nowrap px-3" x-text="ticketLabel(m.ticket)"></td>
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

                focusLink(type) {
                    switch (type) {
                        case 'sla_risk':
                            return '/tickets?focus=sla_risk';
                        case 'due_today':
                            return '/tickets?focus=due_today';
                        case 'pending_user':
                            return '/tickets?status=waiting_info';
                        case 'reopened':
                            return '/tickets?focus=reopened';
                        default:
                            return '/tickets';
                    }
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
                ticketLabel(ticket) {
                    return window.HenanApp?.ticketLabel(ticket) ?? '-';
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
</x-app-layout>