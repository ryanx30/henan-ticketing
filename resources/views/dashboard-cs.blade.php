<x-app-layout>
    <style>
        .dashboard-ticket-row {
            position: relative;
            cursor: pointer;
            transition:
                background-color 150ms ease,
                filter 150ms ease,
                transform 150ms ease;
        }

        .dashboard-ticket-row td {
            transition: background-color 150ms ease;
        }

        .dashboard-ticket-row:hover {
            z-index: 10;
            filter: drop-shadow(0 8px 14px rgba(15, 23, 42, 0.14));
            transform: translateY(-1px);
        }

        .dashboard-ticket-row:hover td {
            background-color: #ffffff;
        }

        .dashboard-ticket-row:focus {
            outline: 2px solid rgba(37, 99, 235, 0.35);
            outline-offset: -2px;
        }
    </style>

    <div
        id="dashboard-cs-page"
        data-current-user-id="{{ auth()->id() }}"
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
                                    x-show="!isSupervisor()"
                                    href="{{ route('tickets.create') }}"
                                    class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm transition duration-200 hover:border-slate-900 hover:bg-slate-900 hover:text-white hover:shadow-md">
                                    + Create Ticket
                                </a>
                            </div>
                        </div>

                        @include('dashboard.partials.cs-my-tickets')

                        @include('dashboard.partials.cs-active-tickets')

                        @include('dashboard.partials.resolver-inbox-preview', [
                            'title' => 'Resolver Update Inbox',
                            'subtitle' => 'Latest resolver conversations that need attention.',
                            'showTimeFilter' => true,
                        ])

                    </div>
                </div>
            </div>

        </div>
    </div>


</x-app-layout>