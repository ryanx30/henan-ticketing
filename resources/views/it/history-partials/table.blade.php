        {{-- TABLE CARD --}}
        <div class="overflow-hidden rounded-lg border border-slate-300 shadow-[0_4px_12px_rgba(15,23,42,0.08)]">
            <div class="bg-[#051823] px-7 py-2">
                <h2 class="text-2xl px-4 font-semibold leading-none text-white">
                    Ticket History Repository
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-slate-800">
                    <thead class="bg-[#d5e0e7] text-[#051823]">
                        <tr class="text-left">
                            <th class="px-7 py-3 font-semibold">
                                <button type="button" @click="sort('ticket_code')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                                    Ticket <span x-html="sortIcon('ticket_code')"></span>
                                </button>
                            </th>
                            <th class="px-7 py-3 font-semibold">
                                <button type="button" @click="sort('resolved_at')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                                    Resolved Date <span x-html="sortIcon('resolved_at')"></span>
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
                            <th class="px-7 py-3 font-semibold">Resolution Note</th>
                            <th class="px-7 py-3 font-semibold">Duration (SLA)</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="px-8 py-10 text-center text-slate-500">Loading history...</td>
                            </tr>
                        </template>

                        <template x-if="!loading && tickets.length === 0">
                            <tr>
                                <td colspan="6" class="px-8 py-10 text-center text-slate-500">No history found.</td>
                            </tr>
                        </template>

                        <template x-for="(t, index) in tickets" :key="t.id">
                            <tr
                                class="history-click-row border-t border-slate-200"
                                :class="index % 2 === 0 ? 'bg-white' : 'bg-[#dfe8ee]'"
                                @click="openTicket(t.id)"
                                tabindex="0"
                                @keydown.enter="openTicket(t.id)">
                                <td class="px-7 py-2 whitespace-nowrap font-medium" x-text="ticketLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap" x-text="resolvedLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap" x-text="categoryLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap uppercase" x-text="t.team ?? '-'"></td>
                                <td class="px-7 py-2 whitespace-nowrap" x-text="resolutionLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap">
                                    <span x-text="durationText(t)"></span>
                                    <span
                                        class="ml-1 text-[13px] font-medium"
                                        :class="slaBadge(t) === 'Met' ? 'text-green-600' : 'text-slate-500'"
                                        x-show="slaBadge(t) !== ''"
                                        x-text="'(' + slaBadge(t) + ')'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
