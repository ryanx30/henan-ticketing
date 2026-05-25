{{-- Table markup is kept here; data formatting and API response shape are handled by JS helpers and API Resources. --}}
<div class="overflow-hidden rounded-lg border border-slate-300 shadow-[0_4px_12px_rgba(15,23,42,0.08)]">
    <div class="bg-[#051823] px-7 py-3">
        <h2 class="text-xl font-semibold leading-none text-white">Ticket Repository</h2>
    </div>

    <div class="ticket-table-scroll max-h-[606px] overflow-auto">
        <table class="w-full text-sm text-slate-800">
            <thead class="sticky top-0 z-10 bg-[#d5e0e7] text-[#051823]">
                <tr class="text-left">
                    <th class="px-7 py-3 font-semibold">
                        <button type="button" @click="sort('ticket_code')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Ticket <span x-html="sortIcon('ticket_code')"></span>
                        </button>
                    </th>
                    <th class="px-7 py-3 font-semibold">
                        <button type="button" @click="sort('title')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Title <span x-html="sortIcon('title')"></span>
                        </button>
                    </th>
                    <th class="px-7 py-3 font-semibold">
                        <button type="button" @click="sort('priority')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Priority <span x-html="sortIcon('priority')"></span>
                        </button>
                    </th>
                    <th class="px-7 py-3 font-semibold">
                        <button type="button" @click="sort('category')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Category <span x-html="sortIcon('category')"></span>
                        </button>
                    </th>
                    <th class="px-7 py-3 font-semibold">
                        <button type="button" @click="sort('team')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Team <span x-html="sortIcon('team')"></span>
                        </button>
                    </th>
                    <th class="px-7 py-3 font-semibold">
                        <button type="button" @click="sort('status')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Status <span x-html="sortIcon('status')"></span>
                        </button>
                    </th>
                    <th class="px-7 py-3 font-semibold">
                        <button type="button" @click="sort('created_at')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                            Date <span x-html="sortIcon('created_at')"></span>
                        </button>
                    </th>
                </tr>
            </thead>

            <tbody>
                <template x-if="loading">
                    <tr>
                        <td colspan="7" class="px-8 py-10 text-center text-slate-500">Loading tickets...</td>
                    </tr>
                </template>

                <template x-if="!loading && tickets.length === 0">
                    <tr>
                        <td colspan="7" class="px-8 py-10 text-center text-slate-500">No tickets found.</td>
                    </tr>
                </template>

                <template x-for="(ticket, index) in tickets" :key="ticket.id">
                    <tr
                        class="ticket-click-row border-t border-slate-200"
                        :class="index % 2 === 0 ? 'bg-white' : 'bg-[#dfe8ee]'"
                        @click="openTicket(ticket.id)"
                        tabindex="0"
                        @keydown.enter="openTicket(ticket.id)">
                        <td class="min-w-[170px] px-7 py-3 whitespace-nowrap font-medium tracking-tight" x-text="ticketLabel(ticket)"></td>
                        <td class="max-w-[340px] truncate px-7 py-3" x-text="ticket.title || '-'"></td>
                        <td class="px-7 py-3 whitespace-nowrap">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="priorityBadgeClass(ticket.priority)"
                                x-text="priorityLabel(ticket)"></span>
                        </td>
                        <td class="px-7 py-3 whitespace-nowrap" x-text="categoryLabel(ticket)"></td>
                        <td class="px-7 py-3 whitespace-nowrap uppercase" x-text="teamLabel(ticket)"></td>
                        <td class="px-7 py-3 whitespace-nowrap">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusBadgeClass(ticket.status)"
                                x-text="statusLabel(ticket.status)"></span>
                        </td>
                        <td class="px-7 py-3 whitespace-nowrap" x-text="createdLabel(ticket)"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
