                {{-- Right --}}
                <div class="space-y-6">
                    {{-- Quick action --}}
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

                            <template x-if="!canManageStatus()">
                                <div class="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">
                                    You can monitor progress here. Status update is available for IT/Admin.
                                </div>
                            </template>
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
                                <select
                                    x-model="quickMessage.to_user_id"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#2f88d8] focus:outline-none focus:ring-2 focus:ring-[#2f88d8]/20">
                                    <option value="" x-show="conversationRecipients().length === 0">No recipient available</option>

                                    <template x-for="recipient in conversationRecipients()" :key="recipient.id">
                                        <option :value="recipient.id" x-text="recipientLabel(recipient)"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-slate-500">Quick Message</label>
                                <textarea
                                    x-model="quickMessage.body"
                                    @keydown="handleQuickMessageKeydown($event)"
                                    rows="3"
                                    class="w-full resize-none rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-[#2f88d8] focus:outline-none focus:ring-2 focus:ring-[#2f88d8]/20"
                                    placeholder="Write a quick update... Shift + Enter for a new line."></textarea>
                            </div>

                            <button
                                type="button"
                                @click="submitQuickMessage()"
                                :disabled="quickMessageSubmitting || !quickMessage.body.trim() || !quickMessage.to_user_id"
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
