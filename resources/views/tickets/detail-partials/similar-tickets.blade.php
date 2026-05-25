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
                                                    class="ticket-detail-badge"
                                                    :class="statusBadgeClass(item.status)"
                                                    x-text="formatStatus(item.status)"></span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <span
                                                    class="ticket-detail-badge"
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
