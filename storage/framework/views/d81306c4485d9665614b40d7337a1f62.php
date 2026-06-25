


<div class="overflow-hidden rounded-lg border border-slate-300 shadow-[0_4px_12px_rgba(15,23,42,0.08)]">
    <div class="bg-[#051823] px-5 py-3">
        <h2 class="text-xl font-semibold leading-none text-white">Ticket Repository</h2>
    </div>

    <div class="ticket-table-scroll max-h-[606px] overflow-y-auto overflow-x-hidden">
        <table class="ticket-index-table w-full table-fixed text-[13px] text-slate-800">
            <colgroup>
                <col style="width: 13%;">
                <col style="width: 28%;">
                <col style="width: 9%;">
                <col style="width: 11%;">
                <col style="width: 9%;">
                <col style="width: 9%;">
                <col style="width: 12%;">
                <col style="width: 9%;">
            </colgroup>

            <thead class="sticky top-0 z-10 bg-[#d5e0e7] text-[#051823]">
                <tr class="text-left">
                    <th class="px-4 py-3 font-semibold">
                        <button type="button" @click="sort('ticket_code')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Ticket <span x-html="sortIcon('ticket_code')"></span>
                        </button>
                    </th>
                    <th class="px-4 py-3 font-semibold">
                        <button type="button" @click="sort('title')" class="inline-flex min-w-0 items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Title <span x-html="sortIcon('title')"></span>
                        </button>
                    </th>
                    <th class="px-4 py-3 font-semibold">
                        <button type="button" @click="sort('priority')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Priority <span x-html="sortIcon('priority')"></span>
                        </button>
                    </th>
                    <th class="px-4 py-3 font-semibold">
                        <button type="button" @click="sort('category')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Category <span x-html="sortIcon('category')"></span>
                        </button>
                    </th>
                    <th class="px-4 py-3 font-semibold">
                        <button type="button" @click="sort('team')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Team <span x-html="sortIcon('team')"></span>
                        </button>
                    </th>
                    <th class="px-4 py-3 font-semibold">
                        <button type="button" @click="sort('status')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Status <span x-html="sortIcon('status')"></span>
                        </button>
                    </th>
                    <th class="px-4 py-3 font-semibold">
                        <button type="button" @click="sort('created_at')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Date <span x-html="sortIcon('created_at')"></span>
                        </button>
                    </th>
                    <th class="px-3 py-3 text-center">Action</th>
                </tr>
            </thead>

            <tbody>
                <template x-if="loading">
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-slate-500">Loading tickets...</td>
                    </tr>
                </template>

                <template x-if="!loading && tickets.length === 0">
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-slate-500">No tickets found.</td>
                    </tr>
                </template>

                <template x-for="(ticket, index) in tickets" :key="ticket.id">
                    <tr :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'">
                        <td class="px-4 py-3 font-medium tracking-tight">
                            <span class="block truncate" x-text="ticketLabel(ticket)" :title="ticketLabel(ticket)"></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="block truncate" x-text="ticket.title || '-'" :title="ticket.title || '-'"></span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span
                                class="inline-flex max-w-full items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="priorityBadgeClass(ticket.priority)"
                                x-text="priorityLabel(ticket)"></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="block truncate" x-text="categoryLabel(ticket)" :title="categoryLabel(ticket)"></span>
                        </td>
                        <td class="px-4 py-3 uppercase">
                            <span class="block truncate" x-text="teamLabel(ticket)" :title="teamLabel(ticket)"></span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span
                                class="inline-flex max-w-full items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusBadgeClass(ticket.status)"
                                x-text="statusLabel(ticket.status)"></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="block truncate" x-text="createdLabel(ticket)" :title="createdLabel(ticket)"></span>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <a
                                    :href="`/tickets/${ticket.id}`"
                                    class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-[#2f88d8] hover:text-[#2f88d8]"
                                    title="Open ticket detail">
                                    Open
                                </a>

                                <a
                                    :href="`/tickets/${ticket.id}/edit`"
                                    class="inline-flex h-8 items-center justify-center rounded-lg bg-[#051823] px-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#0b2a3a]"
                                    title="Edit ticket">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/tickets/partials/index-table.blade.php ENDPATH**/ ?>