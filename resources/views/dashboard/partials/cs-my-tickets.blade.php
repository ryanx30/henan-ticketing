{{-- My Tickets --}}
<div x-show="!isSupervisor()" class="overflow-hidden rounded bg-white shadow-lg">
    <div class="flex items-center justify-between border-b bg-slate-50 px-4 py-4">
        <div>
            <div class="text-xl font-semibold">My Tickets</div>
            <div class="mt-1 text-sm text-slate-500">Your New and Ongoing tickets</div>
        </div>

        <a href="{{ route('tickets.index', ['mine' => 1, 'status' => 'all']) }}" class="text-sm text-gray-700 underline hover:text-slate-950">
            Open
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] table-fixed text-sm">
            <thead>
                <tr class="bg-slate-900 text-left text-white">
                    <th class="w-[105px] px-3 py-2">Priority</th>
                    <th class="w-[185px] px-3">Ticket</th>
                    <th class="px-3">Subject</th>
                    <th class="w-[130px] px-3">Status</th>
                    <th class="w-[150px] px-3">Created</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading && myTickets.length === 0">
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">Loading your tickets...</td>
                    </tr>
                </template>

                <template x-if="!loading && myTickets.length === 0">
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">You have no New or Ongoing tickets.</td>
                    </tr>
                </template>

                <template x-for="t in myTickets" :key="t.id">
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

                        <td class="whitespace-nowrap px-3 text-slate-700" x-text="t.created_at_label || formatDateTime(t.created_at)"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
