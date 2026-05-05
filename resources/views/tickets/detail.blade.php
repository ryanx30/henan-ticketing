<x-app-layout>
    <div
        x-data="ticketDetailPage({ ticketId: @json($ticketId), currentUserId: @json(auth()->id()) })"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1400px]">
            <div id="page-alert" class="mb-6 hidden rounded-lg border px-4 py-3 text-sm shadow-sm"></div>

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

                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="priorityBadgeClass(ticket.priority)"
                            x-text="formatPriority(ticket.priority)"></span>

                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
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

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                {{-- Left --}}
                <div class="space-y-6">
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

                    {{-- Status history --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-[20px] font-bold text-slate-900">Status History</h2>
                            <p class="mt-1 text-sm text-slate-500">Timeline of ticket progress.</p>
                        </div>

                        <div class="px-6 py-6">
                            <template x-if="statusHistories.length === 0">
                                <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                    No status history found.
                                </div>
                            </template>

                            <div class="space-y-5" x-show="statusHistories.length > 0">
                                <template x-for="item in statusHistories" :key="item.id || item.changed_at">
                                    <div class="flex gap-4">
                                        <div class="flex flex-col items-center">
                                            <div class="h-3 w-3 rounded-full bg-[#2f88d8]"></div>
                                            <div class="mt-1 h-full w-[2px] bg-slate-200"></div>
                                        </div>

                                        <div class="flex-1 rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                                <div class="text-[15px] font-semibold text-slate-900">
                                                    <span x-text="formatStatus(item.from_status) || 'Initial'"></span>
                                                    <span class="mx-1 text-slate-400">→</span>
                                                    <span x-text="formatStatus(item.to_status)"></span>
                                                </div>

                                                <div class="text-sm text-slate-500" x-text="formatDateTime(item.changed_at)"></div>
                                            </div>

                                            <div class="mt-2 text-sm text-slate-600">
                                                Changed by:
                                                <span class="font-semibold text-slate-800" x-text="item.changer?.name || '-'"></span>
                                            </div>

                                            <template x-if="item.note">
                                                <div class="mt-2 text-sm leading-6 text-slate-600" x-text="item.note"></div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Similar tickets --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-[20px] font-bold text-slate-900">Similar Tickets</h2>
                            <p class="mt-1 text-sm text-slate-500">Related tickets with similar issue pattern.</p>
                        </div>

                        <div class="overflow-x-auto px-6 py-6">
                            <template x-if="similarTickets.length === 0">
                                <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                    No similar ticket found.
                                </div>
                            </template>

                            <table class="min-w-full text-left text-sm" x-show="similarTickets.length > 0">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-500">
                                        <th class="px-3 py-3 font-semibold">Ticket</th>
                                        <th class="px-3 py-3 font-semibold">Title</th>
                                        <th class="px-3 py-3 font-semibold">Status</th>
                                        <th class="px-3 py-3 font-semibold">Priority</th>
                                        <th class="px-3 py-3 font-semibold">Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in similarTickets" :key="item.id">
                                        <tr
                                            class="cursor-pointer border-b border-slate-100 transition-colors hover:bg-slate-200/70"
                                            @click="window.location.href = `/tickets/${item.id}`">
                                            <td class="px-3 py-3 font-semibold text-slate-800 whitespace-nowrap" x-text="ticketLabel(item)"></td>
                                            <td class="px-3 py-3 text-slate-700" x-text="item.title || '-'"></td>
                                            <td class="px-3 py-3">
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                                    :class="statusBadgeClass(item.status)"
                                                    x-text="formatStatus(item.status)"></span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                                    :class="priorityBadgeClass(item.priority)"
                                                    x-text="formatPriority(item.priority)"></span>
                                            </td>
                                            <td class="px-3 py-3 text-slate-600" x-text="formatDateTime(item.created_at)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Recent updates --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-[20px] font-bold text-slate-900">Recent Updates</h2>
                            <p class="mt-1 text-sm text-slate-500">Messages or resolver updates related to this ticket.</p>
                        </div>

                        <div class="px-6 py-6">
                            <template x-if="updates.length === 0">
                                <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                    No updates available.
                                </div>
                            </template>

                            <div class="space-y-4" x-show="updates.length > 0">
                                <template x-for="item in updates" :key="item.id || item.created_at">
                                    <div class="group rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 transition hover:border-slate-300 hover:bg-white">
                                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-sky-700">
                                                        Message
                                                    </span>

                                                    <template x-if="isUnreadUpdate(item)">
                                                        <span class="inline-flex rounded-full bg-slate-300 px-2.5 py-1 text-[11px] font-bold text-white">
                                                            New
                                                        </span>
                                                    </template>
                                                </div>

                                                <div class="mt-3 text-[15px] font-semibold text-slate-900" x-text="displayUpdateTitle(item)"></div>

                                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    <span class="font-mono text-slate-600" x-text="currentTicketLabel()"></span>
                                                    <span>•</span>
                                                    <span x-text="updateParticipants(item)"></span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between gap-3 md:flex-col md:items-end">
                                                <div class="text-xs text-slate-500" x-text="formatDateTime(item.created_at)"></div>

                                                <div class="flex items-center gap-2 opacity-100 transition md:opacity-0 md:group-hover:opacity-100">
                                                    <template x-if="isUnreadUpdate(item)">
                                                        <button
                                                            type="button"
                                                            @click.stop="markUpdateAsRead(item)"
                                                            class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                            Mark as Read
                                                        </button>
                                                    </template>

                                                    <button
                                                        type="button"
                                                        @click.stop="openCompose(item)"
                                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                        Reply
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click.stop="openMessageDetail(item)"
                                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                        Open Message
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700" x-text="updateBody(item)"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

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

                            <button
                                type="button"
                                @click="openCompose()"
                                class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Send Message
                            </button>

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
                                        <option value="in_progress">On Going</option>
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
            </div>
        </div>

        {{-- Compose / Reply modal --}}
        <div
            x-show="showCompose"
            x-transition
            class="fixed inset-0 z-50"
            style="display:none;">
            <div class="absolute inset-0 bg-black/20" @click="discardDraft()"></div>

            <div class="pointer-events-auto fixed bottom-0 right-6 z-50 w-full max-w-3xl overflow-hidden rounded-t-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-slate-100 px-5 py-3">
                    <h3 class="text-[18px] font-semibold text-slate-900" x-text="composeMode === 'reply' ? 'Reply Message' : 'New Message'"></h3>

                    <div class="flex items-center gap-4 text-slate-500">
                        <button type="button" @click="discardDraft()">✕</button>
                    </div>
                </div>

                <form @submit.prevent="submitMessage" class="p-5" enctype="multipart/form-data">
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Ticket</span>
                            <div class="w-full text-sm text-slate-800" x-text="currentTicketLabel()"></div>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">To</span>
                            <div class="w-full text-sm text-slate-800" x-text="composeRecipientLabel()"></div>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Subject</span>
                            <input type="text" x-model="composeForm.subject" class="w-full border-0 bg-transparent text-sm outline-none" placeholder="Message subject">
                        </div>
                    </div>

                    <div class="py-4">
                        <textarea
                            x-model="composeForm.body"
                            rows="10"
                            class="w-full resize-none border-0 text-sm outline-none"
                            placeholder="Write your message..."></textarea>
                    </div>

                    <div class="mb-4 flex items-center gap-3">
                        <label class="cursor-pointer text-slate-600 hover:text-slate-900" title="Attach file">
                            <input type="file" class="hidden" @change="handleAttachment($event)">
                            <span class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                </svg>
                                Attach file
                            </span>
                        </label>

                        <template x-if="composeForm.attachmentName">
                            <span class="text-sm text-slate-500" x-text="composeForm.attachmentName"></span>
                        </template>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                        <button
                            type="submit"
                            class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                            :disabled="messageSubmitting || !composeForm.body.trim()">
                            <span x-text="messageSubmitting ? 'Sending...' : 'Send'"></span>
                        </button>

                        <button
                            type="button"
                            @click="discardDraft()"
                            class="text-slate-500 hover:text-red-600">
                            Discard
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function ticketDetailPage({
                ticketId,
                currentUserId
            }) {
                return {
                    ticketId,
                    currentUserId,
                    loading: true,
                    errorMessage: '',
                    ticket: {},
                    statusHistories: [],
                    updates: [],
                    similarTickets: [],
                    statusSubmitting: false,
                    claimSubmitting: false,
                    showCompose: false,
                    messageSubmitting: false,
                    composeMode: 'new',
                    composeForm: {
                        subject: '',
                        body: '',
                        attachment: null,
                        attachmentName: '',
                    },
                    statusForm: {
                        status: '',
                        note: '',
                    },
                    master: {
                        teams: [],
                        categories: [],
                        priorities: [],
                    },
                    now: Date.now(),
                    slaTicker: null,

                    async init() {
                        this.startLiveClock();
                        await this.loadAll();
                    },

                    destroy() {
                        if (this.slaTicker) {
                            clearInterval(this.slaTicker);
                            this.slaTicker = null;
                        }
                    },

                    startLiveClock() {
                        this.now = Date.now();

                        if (this.slaTicker) {
                            clearInterval(this.slaTicker);
                        }

                        this.slaTicker = setInterval(() => {
                            this.now = Date.now();
                        }, 1000);
                    },

                    async loadAll() {
                        this.loading = true;
                        this.errorMessage = '';

                        try {
                            await Promise.all([
                                this.loadDetail(),
                                this.loadSimilarTickets(),
                            ]);
                        } catch (error) {
                            console.error(error);
                            this.errorMessage = error.message || 'Failed to load ticket detail.';
                        } finally {
                            this.loading = false;
                        }
                    },

                    csrf() {
                        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    },

                    showAlert(message, type = 'success') {
                        const el = document.getElementById('page-alert');
                        if (!el) return;

                        el.className = 'mb-6 rounded-lg border px-4 py-3 text-sm shadow-sm';
                        el.classList.remove('hidden');

                        if (type === 'success') {
                            el.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
                        } else {
                            el.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
                        }

                        el.textContent = message;

                        setTimeout(() => {
                            el.classList.add('hidden');
                        }, 3000);
                    },

                    async loadDetail() {
                        const response = await fetch(`/api/tickets/${this.ticketId}`, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load ticket detail.');
                        }

                        this.ticket = result.data || {};
                        this.statusHistories = this.ticket.status_histories || this.ticket.statusHistories || [];

                        const rawUpdates = this.ticket.resolver_messages || this.ticket.resolverMessages || [];
                        this.updates = [...rawUpdates].sort((a, b) => {
                            return new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime();
                        });

                        this.statusForm.status = this.ticket.status || '';
                    },

                    async loadSimilarTickets() {
                        const response = await fetch(`/api/tickets/${this.ticketId}/similar`, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load similar tickets.');
                        }

                        this.similarTickets = result.data || [];
                    },

                    async claimTicket() {
                        if (this.claimSubmitting) return;

                        this.claimSubmitting = true;

                        try {
                            const response = await fetch(`/api/it/tickets/${this.ticketId}/claim`, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to claim ticket.');
                            }

                            this.showAlert(result.message || 'Ticket claimed successfully.', 'success');
                            await this.loadAll();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to claim ticket.', 'error');
                        } finally {
                            this.claimSubmitting = false;
                        }
                    },

                    async submitStatusChange() {
                        if (!this.statusForm.status || this.statusSubmitting) return;

                        this.statusSubmitting = true;

                        try {
                            const response = await fetch(`/api/it/tickets/${this.ticketId}/status`, {
                                method: 'PATCH',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                                body: JSON.stringify({
                                    status: this.statusForm.status,
                                    note: this.statusForm.note,
                                }),
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to update status.');
                            }

                            this.statusForm.note = '';
                            this.showAlert(result.message || 'Status updated successfully.', 'success');
                            await this.loadAll();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to update status.', 'error');
                        } finally {
                            this.statusSubmitting = false;
                        }
                    },

                    openCompose(item = null) {
                        this.composeMode = item ? 'reply' : 'new';
                        this.composeForm.subject = this.buildReplySubject();
                        this.composeForm.body = '';
                        this.composeForm.attachment = null;
                        this.composeForm.attachmentName = '';
                        this.showCompose = true;
                    },

                    discardDraft() {
                        this.showCompose = false;
                        this.composeMode = 'new';
                        this.composeForm.subject = '';
                        this.composeForm.body = '';
                        this.composeForm.attachment = null;
                        this.composeForm.attachmentName = '';
                    },

                    handleAttachment(event) {
                        const file = event.target.files?.[0] || null;
                        this.composeForm.attachment = file;
                        this.composeForm.attachmentName = file ? file.name : '';
                    },

                    async submitMessage() {
                        if (this.messageSubmitting) return;
                        if (!this.composeForm.body.trim()) return;

                        this.messageSubmitting = true;

                        try {
                            const formData = new FormData();
                            formData.append('ticket_id', this.ticketId);
                            formData.append('subject', this.composeForm.subject || '');
                            formData.append('body', this.composeForm.body || '');

                            if (this.composeForm.attachment) {
                                formData.append('attachment', this.composeForm.attachment);
                            }

                            const response = await fetch('/api/resolver-inbox', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                                body: formData,
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to send message.');
                            }

                            this.showAlert(result.message || 'Message sent successfully.', 'success');
                            this.discardDraft();
                            await this.loadDetail();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to send message.', 'error');
                        } finally {
                            this.messageSubmitting = false;
                        }
                    },

                    async markUpdateAsRead(item) {
                        if (!item?.id || !this.isUnreadUpdate(item)) return;

                        try {
                            const response = await fetch(`/api/resolver-inbox/${item.id}/read`, {
                                method: 'PATCH',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to mark message as read.');
                            }

                            item.is_read = true;
                            item.read_at = result.data?.read_at || item.read_at;
                            this.showAlert(result.message || 'Message marked as read.', 'success');
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to mark message as read.', 'error');
                        }
                    },

                    openMessageDetail(item) {
                        if (!item?.id) return;
                        window.location.href = `/resolver-inbox/${item.id}`;
                    },

                    ticketLabel(ticket = null) {
                    return window.HenanApp?.ticketLabel(ticket) ?? '-';
                },

                    currentTicketLabel() {
                        return this.ticketLabel(this.ticket?.ticket_code ? this.ticket : { id: this.ticketId });
                    },

                    buildReplySubject() {
                        const ticketLabel = this.currentTicketLabel();
                        const ticketTitle = this.ticket?.title || 'Message';
                        return `Reply for ${ticketLabel} - ${ticketTitle}`;
                    },

                    composeRecipientLabel() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();

                        if (role === 'it' || role === 'admin') {
                            return this.ticket.creator
                                ? `${this.ticket.creator.name}${this.ticket.creator.email ? ' <' + this.ticket.creator.email + '>' : ''}`
                                : 'Ticket creator';
                        }

                        return this.ticket.holder
                            ? `${this.ticket.holder.name}${this.ticket.holder.email ? ' <' + this.ticket.holder.email + '>' : ''}`
                            : 'Ticket holder';
                    },

                    displayUpdateTitle(item) {
                        if (this.ticket?.title) {
                            return this.ticket.title;
                        }

                        const normalized = this.normalizedUpdateSubject(item);
                        return normalized || 'Resolver Update';
                    },

                    normalizedUpdateSubject(item) {
                        const raw = (item?.subject || '').trim();
                        if (!raw) return '';
                        return raw.replace(/^Reply for\s+#?T-[A-Za-z0-9-]+\s*-\s*/i, '').trim();
                    },

                    updateParticipants(item) {
                        const fromName = item?.sender?.name || 'Unknown sender';
                        const toName = item?.recipient?.name || 'Unknown recipient';
                        return `${fromName} → ${toName}`;
                    },

                    updateBody(item) {
                        return item?.body || item?.message || '-';
                    },

                    isUnreadUpdate(item) {
                        return !!item && !item.is_read && Number(item.to_user_id) === Number(this.currentUserId);
                    },

                    canManageStatus() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();
                        return role === 'it' || role === 'admin';
                    },

                    canClaimTicket() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();
                        if (!(role === 'it' || role === 'admin')) return false;
                        if ((this.ticket.team || '').toLowerCase() !== 'it') return false;
                        return !this.ticket.holder_id;
                    },

                    slugify(value) {
                        return String(value || '')
                            .toLowerCase()
                            .trim()
                            .replace(/[^a-z0-9]+/g, '_')
                            .replace(/^_+|_+$/g, '');
                    },

                    masterLabel(collection, value) {
                        const target = this.slugify(value);
                        if (!target) return '-';

                        const found = collection.find(item => {
                            const candidates = [
                                item.name,
                                item.code,
                                item.slug,
                                item.code_num,
                                this.slugify(item.name),
                            ];

                            return candidates.some(candidate => this.slugify(candidate) === target);
                        });

                        return found?.name || this.humanLabel(value);
                    },

                    humanLabel(value) {
                        if (!value) return '-';

                        return String(value)
                            .replaceAll('_', ' ')
                            .replaceAll('-', ' ')
                            .replace(/\b\w/g, c => c.toUpperCase());
                    },

                    formatTeam(value) {
                        return this.masterLabel(this.master.teams, value).toUpperCase();
                    },

                    formatCategory(value) {
                        return this.masterLabel(this.master.categories, value);
                    },

                    formatIssueType(value) {
                        return this.humanLabel(value);
                    },

                    formatStatus(value) {
                        if (!value) return '-';

                        const map = {
                            new: 'New',
                            in_progress: 'On Going',
                            waiting_info: 'Waiting',
                            resolved: 'Resolved',
                            closed: 'Closed',
                        };

                        return map[value] || value.replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                    },

                    formatPriority(value) {
                        if (!value) return '-';
                        return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
                    },

                    formatDateTime(value) {
                        if (!value) return '-';

                        try {
                            return new Intl.DateTimeFormat('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                            }).format(new Date(value));
                        } catch {
                            return value;
                        }
                    },

                    statusBadgeClass(status) {
                        switch ((status || '').toLowerCase()) {
                            case 'new':
                                return 'bg-blue-100 text-blue-700';
                            case 'in_progress':
                                return 'bg-amber-100 text-amber-700';
                            case 'waiting_info':
                                return 'bg-purple-100 text-purple-700';
                            case 'resolved':
                                return 'bg-emerald-100 text-emerald-700';
                            case 'closed':
                                return 'bg-slate-200 text-slate-700';
                            default:
                                return 'bg-slate-100 text-slate-700';
                        }
                    },

                    priorityBadgeClass(priority) {
                        switch ((priority || '').toLowerCase()) {
                            case 'critical':
                                return 'bg-red-100 text-red-700';
                            case 'high':
                                return 'bg-orange-100 text-orange-700';
                            case 'medium':
                                return 'bg-yellow-100 text-yellow-700';
                            case 'low':
                                return 'bg-green-100 text-green-700';
                            default:
                                return 'bg-slate-100 text-slate-700';
                        }
                    },

                    slaLabel() {
                        if (!this.ticket.sla_deadline_at) return 'No SLA';
                        if (this.ticket.status === 'resolved' || this.ticket.status === 'closed') return 'Completed';

                        const deadline = new Date(this.ticket.sla_deadline_at).getTime();
                        const diff = deadline - this.now;

                        if (diff < 0) return 'Breached';
                        if (diff <= 2 * 60 * 60 * 1000) return 'At Risk';
                        return 'Safe';
                    },

                    slaCardClass() {
                        const label = this.slaLabel();
                        if (label === 'Breached') return 'bg-red-600';
                        if (label === 'At Risk') return 'bg-amber-500';
                        if (label === 'Completed') return 'bg-emerald-600';
                        if (label === 'No SLA') return 'bg-slate-500';
                        return 'bg-[#2f88d8]';
                    },

                    formatLiveDuration(diffMs) {
                        const safeDiff = Math.max(0, Math.abs(Number(diffMs) || 0));
                        const totalSeconds = Math.floor(safeDiff / 1000);
                        const days = Math.floor(totalSeconds / 86400);
                        const hours = Math.floor((totalSeconds % 86400) / 3600);
                        const minutes = Math.floor((totalSeconds % 3600) / 60);
                        const seconds = totalSeconds % 60;
                        const pad = (value) => String(value).padStart(2, '0');

                        if (days > 0) {
                            return `${days}d ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                        }

                        return `${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                    },

                    remainingSlaText() {
                        if (!this.ticket.sla_deadline_at) return '-';
                        if (this.ticket.status === 'resolved' || this.ticket.status === 'closed') return 'Finished';

                        const deadline = new Date(this.ticket.sla_deadline_at).getTime();
                        const diff = deadline - this.now;
                        const suffix = diff < 0 ? 'overdue' : 'left';

                        return `${this.formatLiveDuration(diff)} ${suffix}`;
                    },

                    ticketAgeText() {
                        if (!this.ticket.created_at) return '-';

                        const created = new Date(this.ticket.created_at).getTime();
                        const diff = this.now - created;

                        return this.formatLiveDuration(diff);
                    },
                }
            }
        </script>
    </div>
</x-app-layout>
