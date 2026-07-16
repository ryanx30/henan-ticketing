{{-- ========= IT DASHBOARD SHELL ========= --}}
{{-- Page shell for resolver KPI, queue preview, and trend data loaded by JavaScript. --}}

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .dashboard-ticket-row {
            position: relative;
            cursor: pointer;
            transition:
                background-color 150ms ease,
                box-shadow 150ms ease,
                transform 150ms ease;
        }

        .dashboard-ticket-row:hover {
            z-index: 10;
            background-color: #ffffff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
            transform: translateY(-1px);
        }

        .dashboard-ticket-row:focus {
            outline: 2px solid rgba(37, 99, 235, 0.35);
            outline-offset: -2px;
        }
    </style>

    <div
        id="dashboard-it-page"
        data-current-user-id="{{ auth()->id() }}"
        x-data="dashboardItPage()"
        x-init="init()"
        class="p-6 bg-slate-100 min-h-screen">

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

                        {{-- Ongoing --}}
                        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.in_progress.value)"></div>
                                    <div class="mt-1 text-[16px] text-slate-700">Ongoing</div>
                                </div>
                                <img src="{{ asset('images/icons/ongoing.png') }}" alt="Ongoing" class="h-10 w-10 object-contain opacity-90" />
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
                            <span class="legend-status-dot legend-status-new"></span>
                            <span>New</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-ongoing"></span>
                            <span>Ongoing</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-waiting"></span>
                            <span>Waiting Info</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-resolved"></span>
                            <span>Resolved</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-closed"></span>
                            <span>Closed</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT MAIN --}}
            <div class="col-span-12 lg:col-span-9 space-y-6 bg-white rounded shadow p-4">

                <div class="rounded border border-slate-200 bg-slate-50 p-4">
                    @include('dashboard.partials.quick-actions')
                </div>

                {{-- Top Issue Types + Chart --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- Top Issue Types Table --}}
                    <div class="bg-white rounded shadow overflow-hidden">
                        <div class="px-4 py-3 border-b bg-slate-50 font-semibold">
                            Top Issue Types
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

                    {{-- Trend Chart --}}
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

                {{-- My Queue --}}
                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="px-4 py-3 flex items-center justify-between border-b bg-slate-50">
                        <div class="font-semibold">My Queue</div>
                        <a href="{{ route('it.my-queue') }}" class="text-sm underline text-gray-600 hover:text-slate-900">
                            Open
                        </a>
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
                                    <th class="px-3 text-right">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="loading && itMyQueue.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-500">
                                            Loading my queue...
                                        </td>
                                    </tr>
                                </template>

                                <template x-if="!loading && itMyQueue.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-500">
                                            No tickets in my queue.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="t in itMyQueue" :key="t.id">
                                    <tr
                                        class="dashboard-ticket-row border-b border-slate-200"
                                        @click="window.location.href = ticketUrl(t.id)"
                                        tabindex="0"
                                        @keydown.enter="window.location.href = ticketUrl(t.id)">
                                        <td class="py-2 px-3">
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="priorityLabel(t.priority)">
                                            </span>
                                        </td>

                                        <td class="px-3 font-mono whitespace-nowrap" x-text="ticketLabel(t)"></td>

                                        <td class="px-3 max-w-[420px] truncate">
                                            <span
                                                class="font-medium text-slate-900"
                                                x-text="t.title">
                                            </span>
                                        </td>

                                        <td class="px-3">
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="statusBadgeClass(t.status)"
                                                x-text="statusLabel(t.status)">
                                            </span>
                                        </td>

                                        <td class="px-3 text-center w-[130px]">
                                            <span
                                                class="font-mono tabular-nums inline-block text-center w-[110px]"
                                                x-text="slaCountdown(t.sla_deadline_at)">
                                            </span>
                                        </td>

                                        <td class="px-3 text-right"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Team Queue (New) --}}
                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="px-4 py-3 flex items-center justify-between border-b bg-slate-50">
                        <div class="font-semibold">Team Queue (New)</div>
                        <a href="{{ route('it.team-queue') }}" class="text-sm underline text-gray-600 hover:text-slate-900">
                            Open
                        </a>
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
                                        <td colspan="5" class="py-8 text-center text-gray-500">
                                            Loading new team tickets...
                                        </td>
                                    </tr>
                                </template>

                                <template x-if="!loading && itTeamNew.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500">
                                            No new team tickets.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="t in itTeamNew" :key="t.id">
                                    <tr
                                        class="dashboard-ticket-row border-b border-slate-200"
                                        @click="window.location.href = ticketUrl(t.id)"
                                        tabindex="0"
                                        @keydown.enter="window.location.href = ticketUrl(t.id)">
                                        <td class="py-2 px-3">
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="priorityLabel(t.priority)">
                                            </span>
                                        </td>

                                        <td class="px-3 font-mono whitespace-nowrap" x-text="ticketLabel(t)"></td>

                                        <td class="px-3 max-w-[420px] truncate">
                                            <span
                                                class="font-medium text-slate-900"
                                                x-text="t.title">
                                            </span>
                                        </td>

                                        <td class="px-3 text-center w-[130px]">
                                            <span
                                                class="font-mono tabular-nums inline-block text-center w-[110px]"
                                                x-text="slaCountdown(t.sla_deadline_at)">
                                            </span>
                                        </td>

                                        <td class="px-3 text-right">
                                            <button
                                                type="button"
                                                @click.stop="claimTicket(t.id)"
                                                class="px-3 py-1 rounded bg-slate-900 text-white text-xs transition-colors duration-150 hover:bg-slate-700">
                                                Claim
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                @include('dashboard.partials.resolver-inbox-preview', [
                    'title' => 'Resolver Inbox',
                    'subtitle' => 'Latest resolver conversations and ticket updates.',
                    'showTimeFilter' => false,
                ])

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>


</x-app-layout>