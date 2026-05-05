<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        id="team-queue-page"
        data-user-id="{{ auth()->id() }}"
        x-data="teamQueuePage()"
        x-init="init()"
        class="p-6 bg-slate-100 min-h-screen">
        <div class="max-w-6xl mx-auto">

            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold">Team Queue</h1>
            </div>

            {{-- ========= NEW TICKETS ========= --}}
            <div class="mb-10">
                <div class="font-semibold text-lg mb-3">New Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="loading && newTickets.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-10 text-center text-gray-500">Loading new tickets...</td>
                                    </tr>
                                </template>

                                <template x-if="!loading && newTickets.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-10 text-center text-gray-500">No new tickets.</td>
                                    </tr>
                                </template>

                                <template x-for="t in newTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="ticketLabel(t)"></td>

                                        <td class="px-3">
                                            <a :href="ticketUrl(t.id)" class="block">
                                                <div class="font-semibold text-slate-900 hover:text-blue-600 hover:underline" x-text="t.title"></div>
                                                <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                            </a>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 w-[110px] whitespace-nowrap">
                                            <span class="font-mono tabular-nums inline-block w-[110px] mx-auto text-slate-800"
                                                x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>

                                        <td class="px-3 text-right">
                                            <button
                                                type="button"
                                                @click="claimTicket(t.id)"
                                                class="px-4 py-2 rounded bg-slate-900 text-white text-xs">
                                                Claim
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ========= ON GOING ========= --}}
            <div class="mb-10">
                <div class="font-semibold text-lg mb-3">On Going Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Holder</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="!loading && ongoingTickets.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-gray-500">No on going tickets.</td>
                                    </tr>
                                </template>

                                <template x-for="t in ongoingTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="ticketLabel(t)"></td>

                                        <td class="px-3">
                                            <a :href="ticketUrl(t.id)" class="block">
                                                <div class="font-semibold text-slate-900 hover:text-blue-600 hover:underline" x-text="t.title"></div>
                                                <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                            </a>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 w-[110px] whitespace-nowrap">
                                            <span class="font-mono tabular-nums inline-block w-[110px] mx-auto text-slate-800"
                                                x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>

                                        <td class="px-3" x-text="t.holder?.name ?? '-'"></td>

                                        <td class="px-3">
                                            <select class="border rounded px-2 py-2 text-xs w-[140px]"
                                                :value="t.status"
                                                :disabled="t.holder_id !== currentUserId"
                                                @change="updateStatus(t.id, $event.target.value)">
                                                <option value="new">New</option>
                                                <option value="in_progress">On Going</option>
                                                <option value="waiting_info">Waiting Info</option>
                                                <option value="resolved">Resolved</option>
                                            </select>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ========= WAITING INFO ========= --}}
            <div class="mb-10">
                <div class="font-semibold text-lg mb-3">Waiting Info Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Holder</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="!loading && waitingTickets.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-gray-500">No waiting info tickets.</td>
                                    </tr>
                                </template>

                                <template x-for="t in waitingTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="ticketLabel(t)"></td>

                                        <td class="px-3">
                                            <a :href="ticketUrl(t.id)" class="block">
                                                <div class="font-semibold text-slate-900 hover:text-blue-600 hover:underline" x-text="t.title"></div>
                                                <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                            </a>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 w-[110px] whitespace-nowrap">
                                            <span class="font-mono tabular-nums inline-block w-[110px] mx-auto text-slate-800"
                                                x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>

                                        <td class="px-3" x-text="t.holder?.name ?? '-'"></td>

                                        <td class="px-3">
                                            <select class="border rounded px-2 py-2 text-xs w-[140px]"
                                                :value="t.status"
                                                :disabled="t.holder_id !== currentUserId"
                                                @change="updateStatus(t.id, $event.target.value)">
                                                <option value="new">New</option>
                                                <option value="in_progress">On Going</option>
                                                <option value="waiting_info">Waiting Info</option>
                                                <option value="resolved">Resolved</option>
                                            </select>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ========= RESOLVED ========= --}}
            <div class="mb-2">
                <div class="font-semibold text-lg mb-3">Resolved Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Holder</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="!loading && resolvedTickets.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-10 text-center text-gray-500">No resolved tickets.</td>
                                    </tr>
                                </template>

                                <template x-for="t in resolvedTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="ticketLabel(t)"></td>

                                        <td class="px-3">
                                            <a :href="ticketUrl(t.id)" class="block">
                                                <div class="font-semibold text-slate-900 hover:text-blue-600 hover:underline" x-text="t.title"></div>
                                                <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                            </a>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3">
                                            <span class="text-green-600 text-xs">SLA Met</span>
                                        </td>

                                        <td class="px-3" x-text="t.holder?.name ?? '-'"></td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                :class="statusBadgeClass(t.status)"
                                                x-text="statusLabel(t.status)"></span>
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

    <script>
        const TEAM_QUEUE_CURRENT_USER_ID = Number(
            document.getElementById('team-queue-page')?.dataset.userId || 0
        );

        function teamQueuePage() {
            return {
                loading: false,
                timer: null,
                currentUserId: TEAM_QUEUE_CURRENT_USER_ID,
                newTickets: [],
                ongoingTickets: [],
                waitingTickets: [],
                resolvedTickets: [],

                init() {
                    this.loadQueue();

                    this.timer = setInterval(() => {
                        this.newTickets = [...this.newTickets];
                        this.ongoingTickets = [...this.ongoingTickets];
                        this.waitingTickets = [...this.waitingTickets];
                    }, 1000);
                },

                destroy() {
                    if (this.timer) clearInterval(this.timer);
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },

                ticketUrl(ticketId) {
                    return `/tickets/${ticketId}`;
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

                async loadQueue() {
                    this.loading = true;

                    try {
                        const response = await fetch('/api/it/team-queue', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load team queue');
                        }

                        const data = result.data || {};
                        this.newTickets = data.new_tickets || [];
                        this.ongoingTickets = data.ongoing_tickets || [];
                        this.waitingTickets = data.waiting_tickets || [];
                        this.resolvedTickets = data.resolved_tickets || [];
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load team queue', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async claimTicket(ticketId) {
                    try {
                        const response = await fetch(`/api/it/tickets/${ticketId}/claim`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to claim ticket');
                        }

                        this.showAlert(result.message || 'Ticket claimed successfully', 'success');
                        await this.loadQueue();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to claim ticket', 'error');
                    }
                },

                async updateStatus(ticketId, status) {
                    try {
                        const response = await fetch(`/api/it/tickets/${ticketId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                status
                            })
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to update status');
                        }

                        this.showAlert(result.message || 'Status updated successfully', 'success');
                        await this.loadQueue();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to update status', 'error');
                        await this.loadQueue();
                    }
                },
                ticketLabel(ticket) {
                    return window.HenanApp?.ticketLabel(ticket) ?? '-';
                },


                ucfirst(value) {
                    if (!value) return '-';
                    value = String(value);
                    return value.charAt(0).toUpperCase() + value.slice(1);
                },

                statusLabel(status) {
                    const map = {
                        new: 'New',
                        in_progress: 'On Going',
                        waiting_info: 'Wait Info',
                        resolved: 'Resolved',
                        closed: 'Closed',
                    };
                    return map[status] || status || '-';
                },

                priorityBadgeClass(priority) {
                    switch (priority) {
                        case 'critical':
                            return 'bg-red-600 text-white';
                        case 'high':
                            return 'bg-orange-500 text-white';
                        case 'medium':
                            return 'bg-amber-300 text-slate-900';
                        case 'low':
                            return 'bg-green-600 text-white';
                        default:
                            return 'bg-gray-200 text-slate-900';
                    }
                },

                statusBadgeClass(status) {
                    switch (status) {
                        case 'new':
                            return 'bg-gray-200 text-slate-900';
                        case 'in_progress':
                            return 'bg-orange-500 text-white';
                        case 'waiting_info':
                            return 'bg-amber-400 text-slate-900';
                        case 'resolved':
                            return 'bg-green-600 text-white';
                        case 'closed':
                            return 'bg-sky-700 text-white';
                        default:
                            return 'bg-gray-200 text-slate-900';
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
                }
            }
        }
    </script>
</x-app-layout>