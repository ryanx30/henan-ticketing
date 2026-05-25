                    {{-- Detail card --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-[20px] font-bold text-slate-900">Ticket Information</h2>
                            <p class="mt-1 text-sm text-slate-500">Main information and issue details.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 px-6 py-6 md:grid-cols-2">
                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Ticket Code</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="currentTicketLabel()"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Status</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatStatus(ticket.status)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Priority</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatPriority(ticket.priority)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Team</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatTeam(ticket.team)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Category</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatCategory(ticket.category)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Issue Type</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatIssueType(ticket.issue_type)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Created By</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="ticket.creator?.name || '-'"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Holder / Assignee</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="ticket.holder?.name || '-'"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Created At</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatDateTime(ticket.created_at)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Updated At</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatDateTime(ticket.updated_at)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Resolved At</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatDateTime(ticket.resolved_at)"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Closed At</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="formatDateTime(ticket.closed_at)"></div>
                            </div>

                            <div class="md:col-span-2">
                                <div class="mb-1 text-sm font-medium text-slate-500">Description</div>
                                <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-[15px] leading-7 text-slate-700"
                                    x-text="ticket.description || '-'"></div>
                            </div>
                        </div>
                    </div>
