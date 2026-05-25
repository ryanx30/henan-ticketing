            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-3">
                        <a
                            href="javascript:history.back()"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
                            </svg>
                        </a>

                        <div>
                            <div class="text-sm font-medium text-slate-500">Ticket Detail</div>
                            <h1 class="text-[28px] font-bold leading-tight text-slate-900" x-text="ticket.title || 'Loading ticket...'"></h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pl-[52px]">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                            x-text="currentTicketLabel()"></span>

                        <span class="ticket-detail-badge"
                            :class="priorityBadgeClass(ticket.priority)"
                            x-text="formatPriority(ticket.priority)"></span>

                        <span class="ticket-detail-badge"
                            :class="statusBadgeClass(ticket.status)"
                            x-text="formatStatus(ticket.status)"></span>

                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700"
                            x-text="formatTeam(ticket.team)"></span>
                    </div>
                </div>

                <div class="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 shadow-sm">
                    Last Update:
                    <span class="font-semibold text-slate-900" x-text="formatDateTime(ticket.updated_at)"></span>
                </div>
            </div>

            <template x-if="errorMessage">
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                    <span x-text="errorMessage"></span>
                </div>
            </template>

