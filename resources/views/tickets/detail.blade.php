<x-app-layout>
    <div
        x-data="ticketDetailPage({ ticketId: @json($ticketId) })"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1400px]">
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
                            x-text="ticket.ticket_code || '-'"></span>

                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="priorityBadgeClass(ticket.priority)"
                            x-text="formatPriority(ticket.priority)"></span>

                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="statusBadgeClass(ticket.status)"
                            x-text="formatStatus(ticket.status)"></span>

                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700"
                            x-text="ticket.team ? ticket.team.toUpperCase() : '-'"></span>
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
                                <div class="text-[15px] font-semibold text-slate-900" x-text="ticket.ticket_code || '-'"></div>
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
                                <div class="text-[15px] font-semibold text-slate-900" x-text="ticket.team ? ticket.team.toUpperCase() : '-'"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Category</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="ticket.category || '-'"></div>
                            </div>

                            <div>
                                <div class="mb-1 text-sm font-medium text-slate-500">Issue Type</div>
                                <div class="text-[15px] font-semibold text-slate-900" x-text="ticket.issue_type || '-'"></div>
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
                                            class="border-b border-slate-100 cursor-pointer transition-colors hover:bg-slate-200/70"
                                            @click="window.location.href = `/tickets/${item.id}`">
                                            <td class="px-3 py-3 font-semibold text-slate-800" x-text="item.ticket_code || '-'"></td>
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
                                    <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-4">
                                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                            <div class="text-sm font-semibold text-slate-900" x-text="item.subject || 'Resolver Update'"></div>
                                            <div class="text-xs text-slate-500" x-text="formatDateTime(item.created_at)"></div>
                                        </div>
                                        <div class="mt-2 text-sm leading-6 text-slate-700" x-text="item.message || item.body || '-'"></div>
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

        <script>
            function ticketDetailPage({
                ticketId
            }) {
                return {
                    ticketId,
                    loading: true,
                    errorMessage: '',
                    ticket: {},
                    statusHistories: [],
                    updates: [],
                    similarTickets: [],
                    statusSubmitting: false,
                    claimSubmitting: false,
                    statusForm: {
                        status: '',
                        note: '',
                    },

                    async init() {
                        await this.loadAll();
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
                        this.updates = this.ticket.resolver_messages || this.ticket.resolverMessages || [];
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
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                },
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to claim ticket.');
                            }

                            await this.loadAll();
                        } catch (error) {
                            console.error(error);
                            alert(error.message || 'Failed to claim ticket.');
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
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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
                            await this.loadAll();
                        } catch (error) {
                            console.error(error);
                            alert(error.message || 'Failed to update status.');
                        } finally {
                            this.statusSubmitting = false;
                        }
                    },

                    canManageStatus() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();
                        return role === 'it' || role === 'admin';
                    },

                    canClaimTicket() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();
                        if (!(role === 'it' || role === 'admin')) return false;

                        // Only show claim when ticket belongs to IT team
                        if ((this.ticket.team || '').toLowerCase() !== 'it') return false;

                        // Hide claim when ticket already has a holder
                        return !this.ticket.holder_id;
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
                        const now = Date.now();
                        const diff = deadline - now;

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

                    remainingSlaText() {
                        if (!this.ticket.sla_deadline_at) return '-';
                        if (this.ticket.status === 'resolved' || this.ticket.status === 'closed') return 'Finished';

                        const deadline = new Date(this.ticket.sla_deadline_at).getTime();
                        const now = Date.now();
                        let diff = deadline - now;

                        const overdue = diff < 0;
                        diff = Math.abs(diff);

                        const hours = Math.floor(diff / (1000 * 60 * 60));
                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                        return overdue ?
                            `${hours}h ${minutes}m overdue` :
                            `${hours}h ${minutes}m left`;
                    },

                    ticketAgeText() {
                        if (!this.ticket.created_at) return '-';

                        const created = new Date(this.ticket.created_at).getTime();
                        const now = Date.now();
                        const diff = now - created;

                        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

                        if (days > 0) return `${days} day(s) ${hours} hour(s)`;
                        return `${hours} hour(s)`;
                    },
                }
            }
        </script>
    </div>
</x-app-layout>