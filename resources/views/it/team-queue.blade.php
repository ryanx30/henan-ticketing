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

            {{-- ========= ONGOING ========= --}}
            <div class="mb-10">
                <div class="font-semibold text-lg mb-3">Ongoing Tickets</div>

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
                                        <td colspan="6" class="py-10 text-center text-gray-500">No ongoing tickets.</td>
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
                                                <option value="in_progress">Ongoing</option>
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
                                                <option value="in_progress">Ongoing</option>
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

</x-app-layout>