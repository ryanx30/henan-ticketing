{{-- ========= TICKET DETAIL SIDEBAR ========= --}}
{{-- Quick actions and side metadata for the ticket detail layout. --}}

                {{-- Right --}}
                <div class="space-y-6">
                    {{-- ========= QUICK ACTION ========= --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-[18px] font-bold text-slate-900">Quick Actions</h2>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            <div>
                                <div class="mb-2 text-sm font-medium text-slate-500">Current Status</div>
                                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800"
                                    x-text="formatStatus(ticket.status)"></div>
                            </div>


                            <template x-if="canClaimTicket()">
                                <button
                                    type="button"
                                    @click="claimTicket()"
                                    :disabled="claimSubmitting"
                                    class="inline-flex w-full items-center justify-center rounded-md border border-[#2f88d8] bg-white px-4 py-2.5 text-sm font-semibold text-[#2f88d8] transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-60">
                                    <span x-text="claimSubmitting ? 'Claiming...' : 'Claim Ticket'"></span>
                                </button>
                            </template>

                            <template x-if="canManageStatus()">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-500">Change Status</label>
                                    <select
                                        x-model="statusForm.status"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#2f88d8] focus:outline-none focus:ring-2 focus:ring-[#2f88d8]/20">
                                        <option value="">Select status</option>
                                        <option value="new">New</option>
                                        <option value="in_progress">Ongoing</option>
                                        <option value="waiting_info">Waiting</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                            </template>

                            <template x-if="canManageStatus()">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-500">Note</label>
                                    <textarea
                                        x-model="statusForm.note"
                                        rows="4"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#2f88d8] focus:outline-none focus:ring-2 focus:ring-[#2f88d8]/20"
                                        placeholder="Optional note..."></textarea>
                                </div>
                            </template>

                            <template x-if="canManageStatus()">
                                <button
                                    type="button"
                                    @click="submitStatusChange()"
                                    :disabled="statusSubmitting || !statusForm.status"
                                    class="inline-flex w-full items-center justify-center rounded-md bg-[#2f88d8] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#2676bb] disabled:cursor-not-allowed disabled:opacity-60">
                                    <span x-text="statusSubmitting ? 'Updating...' : 'Update Status'"></span>
                                </button>
                            </template>

                            <template x-if="canEscalateTicket()">
                                <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-800" x-text="`Handoff ${escalationModeLabel()}`"></div>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">Move this ticket to another active user with the same role. After handoff, only the new owner can update it.</p>

                                    <div class="mt-3 space-y-3">
                                        <select
                                            x-model="escalationForm.target_user_id"
                                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#2f88d8] focus:outline-none focus:ring-2 focus:ring-[#2f88d8]/20">
                                            <option value="" x-text="escalationUsers().length ? 'Select target user' : 'No same-role user available'"></option>
                                            <template x-for="user in escalationUsers()" :key="user.id">
                                                <option :value="String(user.id)" x-text="recipientLabel(user)"></option>
                                            </template>
                                        </select>

                                        <textarea
                                            x-model="escalationForm.note"
                                            rows="2"
                                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#2f88d8] focus:outline-none focus:ring-2 focus:ring-[#2f88d8]/20"
                                            placeholder="Optional handoff note..."></textarea>

                                        <button
                                            type="button"
                                            @click="openEscalationConfirm()"
                                            :disabled="escalationSubmitting || !escalationForm.target_user_id"
                                            class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60">
                                            <span x-text="escalationSubmitting ? 'Moving...' : 'Move Holder'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!canManageStatus()">
                                <div class="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">
                                    You can monitor progress here. Status update is available for IT/Admin.
                                </div>
                            </template>
                        </div>
                    </div>



                    {{-- Handoff confirmation modal --}}
                    <div
                        x-cloak
                        x-show="showEscalationConfirm"
                        x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 py-6">
                        <div
                            x-show="showEscalationConfirm"
                            x-transition.scale.origin.center
                            @click.outside="closeEscalationConfirm()"
                            @keydown.escape.window="closeEscalationConfirm()"
                            class="w-full max-w-md rounded-2xl border border-white/70 bg-white p-6 shadow-2xl">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                                            <path d="M12 9v4" />
                                            <path d="M12 17h.01" />
                                        </svg>
                                    </div>

                                    <div>
                                        <h3 class="text-base font-bold text-slate-900" x-text="escalationConfirmTitle()"></h3>
                                        <p class="mt-1 text-xs font-medium text-amber-700">This action changes the current ticket owner.</p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="closeEscalationConfirm()"
                                    :disabled="escalationSubmitting"
                                    class="rounded-full p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <span class="sr-only">Close</span>
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                </button>
                            </div>

                            <p class="mt-5 text-sm leading-6 text-slate-600" x-text="escalationConfirmMessage()"></p>

                            <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500">Current owner</span>
                                    <span class="max-w-[60%] text-right font-semibold text-slate-900" x-text="currentEscalationOwnerLabel()"></span>
                                </div>
                                <div class="mt-3 flex items-start justify-between gap-3">
                                    <span class="text-slate-500">Move to</span>
                                    <span class="max-w-[60%] text-right font-semibold text-slate-900" x-text="escalationTargetLabel()"></span>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    @click="closeEscalationConfirm()"
                                    :disabled="escalationSubmitting"
                                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    @click="submitEscalation()"
                                    :disabled="escalationSubmitting"
                                    class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                                    <span x-text="escalationSubmitting ? 'Moving...' : 'Confirm Move'"></span>
                                </button>
                            </div>
                        </div>
                    </div>


                    {{-- Resolver conversation --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-[18px] font-bold text-slate-900">Resolver Conversation</h2>
                                    <p class="mt-1 text-xs text-slate-500">Latest resolver messages for this ticket.</p>
                                </div>

                                <template x-if="latestResolverUpdate()?.id">
                                    <button
                                        type="button"
                                        @click="openConversation()"
                                        class="shrink-0 text-xs font-semibold text-[#2f88d8] hover:text-[#236ca8]">
                                        Open Thread
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-4 px-5 py-5">
                            <template x-if="latestResolverUpdate()">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold text-slate-500" x-text="updateParticipants(latestResolverUpdate())"></div>
                                            <div class="mt-1 line-clamp-2 text-sm font-medium leading-5 text-slate-900" x-text="updateBody(latestResolverUpdate())"></div>
                                        </div>
                                        <div class="shrink-0 text-[11px] text-slate-500" x-text="formatDateTime(latestResolverUpdate()?.created_at)"></div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!latestResolverUpdate()">
                                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                                    No resolver messages yet. Send the first update from here.
                                </div>
                            </template>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500">Send To</label>
                                <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800"
                                    x-text="quickMessage.to_display || 'No recipient available'"></div>
                                <p class="text-[11px] leading-5 text-slate-500">Recipient is selected automatically from the current CS owner and IT holder.</p>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500">Quick Message</label>
                                <textarea
                                    x-model="quickMessage.body"
                                    @keydown="handleQuickMessageKeydown($event)"
                                    rows="3"
                                    class="w-full resize-none rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#2f88d8] focus:outline-none focus:ring-2 focus:ring-[#2f88d8]/20"
                                    placeholder="Write a quick update... Shift + Enter for a new line."
                                    :disabled="!canSendResolverMessage()"></textarea>
                            </div>

                            <template x-if="!canSendResolverMessage()">
                                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                    This ticket has been handed off. You can still view the detail, but only the current owner can send updates.
                                </div>
                            </template>

                            <button
                                type="button"
                                @click="submitQuickMessage()"
                                :disabled="quickMessageSubmitting || !quickMessage.body.trim() || !quickMessage.to_user_id || !canSendResolverMessage()"
                                class="inline-flex w-full items-center justify-center rounded-md bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-text="quickMessageSubmitting ? 'Sending...' : 'Send Quick Message'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- SLA --}}
                    <div class="overflow-hidden rounded-sm border border-slate-200 shadow-lg">
                        <div class="px-5 py-5 text-white" :class="slaCardClass()">
                            <div class="text-[28px] font-bold leading-none" x-text="slaLabel()"></div>
                            <div class="mt-2 text-sm opacity-90">SLA Summary</div>
                        </div>

                        <div class="space-y-3 bg-white px-5 py-5 text-sm text-slate-700">
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-slate-500">Deadline</span>
                                <span class="text-right font-semibold text-slate-900" x-text="formatDateTime(ticket.sla_deadline_at)"></span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-slate-500">Remaining</span>
                                <span class="text-right font-semibold text-slate-900" x-text="remainingSlaText()"></span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-slate-500">Assigned To</span>
                                <span class="text-right font-semibold text-slate-900" x-text="ticket.holder?.name || '-'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Insight --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <h2 class="text-[18px] font-bold text-slate-900">Ticket Insight</h2>
                        </div>

                        <div class="space-y-3 px-5 py-5 text-sm text-slate-700">
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-slate-500">Ticket Age</span>
                                <span class="text-right font-semibold text-slate-900" x-text="ticketAgeText()"></span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-slate-500">Updates</span>
                                <span class="text-right font-semibold text-slate-900" x-text="updates.length"></span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-slate-500">History Records</span>
                                <span class="text-right font-semibold text-slate-900" x-text="statusHistories.length"></span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-slate-500">Similar Tickets</span>
                                <span class="text-right font-semibold text-slate-900" x-text="similarTickets.length"></span>
                            </div>
                        </div>
                    </div>
                </div>
