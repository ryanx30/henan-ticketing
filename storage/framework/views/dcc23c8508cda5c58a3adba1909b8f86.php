
<div class="overflow-hidden rounded bg-white shadow-lg">
    <div class="flex items-center justify-between border-b bg-slate-50 px-4 py-3">
        <div>
            <div class="text-xl font-semibold">Active Tickets</div>
            <div class="mt-1 text-sm text-slate-500">All active tickets</div>
        </div>

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
                <option value="in_progress">Ongoing</option>
                <option value="waiting_info">Waiting Info</option>
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

            <button type="button" @click="resetTicketFilters()" class="ml-2 text-gray-500 underline hover:text-slate-900">Reset</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] table-fixed text-sm">
            <thead>
                <tr class="bg-slate-900 text-left text-white">
                    <th class="w-[105px] px-3 py-2">Priority</th>
                    <th class="w-[185px] px-3">Ticket</th>
                    <th class="px-3">Subject</th>
                    <th class="w-[130px] px-3">Status</th>
                    <th class="w-[130px] px-3 text-center">SLA</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading && activeTickets.length === 0">
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">Loading active tickets...</td>
                    </tr>
                </template>

                <template x-if="!loading && activeTickets.length === 0">
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">No active tickets.</td>
                    </tr>
                </template>

                <template x-for="t in activeTickets" :key="t.id">
                    <tr
                        class="dashboard-ticket-row border-b border-slate-200"
                        @click="openTicket(t)"
                        tabindex="0"
                        @keydown.enter="openTicket(t)">
                        <td class="px-3 py-2">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                :class="window.HenanApp?.priorityBadgeClass ? window.HenanApp.priorityBadgeClass(t.priority) : { 'badge-priority-critical': String(t.priority || '').trim().toLowerCase() === 'critical', 'badge-priority-high': String(t.priority || '').trim().toLowerCase() === 'high', 'badge-priority-medium': String(t.priority || '').trim().toLowerCase() === 'medium', 'badge-priority-low': String(t.priority || '').trim().toLowerCase() === 'low', 'badge-priority-default': !['critical', 'high', 'medium', 'low'].includes(String(t.priority || '').trim().toLowerCase()) }"
                                x-text="window.HenanApp?.priorityLabel ? window.HenanApp.priorityLabel(t.priority) : ucfirst(t.priority)">
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-3 font-mono" x-text="ticketLabel(t)"></td>

                        <td class="max-w-[420px] truncate px-3">
                            <span class="font-medium text-slate-900" x-text="t.title"></span>
                        </td>

                        <td class="px-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                :class="window.HenanApp?.statusBadgeClass ? window.HenanApp.statusBadgeClass(t.status) : { 'badge-status-new': String(t.status || '').trim().toLowerCase().replace(/[\s-]+/g, '_') === 'new', 'badge-status-ongoing': ['in_progress', 'ongoing', 'on_going'].includes(String(t.status || '').trim().toLowerCase().replace(/[\s-]+/g, '_')), 'badge-status-waiting': ['waiting_info', 'waiting'].includes(String(t.status || '').trim().toLowerCase().replace(/[\s-]+/g, '_')), 'badge-status-resolved': String(t.status || '').trim().toLowerCase() === 'resolved', 'badge-status-closed': String(t.status || '').trim().toLowerCase() === 'closed' }"
                                x-text="window.HenanApp?.statusLabel ? window.HenanApp.statusLabel(t.status) : statusLabel(t.status)">
                            </span>
                        </td>

                        <td class="w-[130px] px-3 text-center">
                            <span
                                class="inline-block w-[110px] text-center font-mono tabular-nums"
                                x-text="slaCountdown(t.sla_deadline_at)">
                            </span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/dashboard/partials/cs-active-tickets.blade.php ENDPATH**/ ?>