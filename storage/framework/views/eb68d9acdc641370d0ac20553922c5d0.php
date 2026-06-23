


            
            <div class="overflow-hidden rounded bg-white shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="bg-[#051823] px-5 py-3">
                    <h2 class="text-[20px] font-semibold text-white">SLA Tracking</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-800">
                        <thead class="bg-[#d5e0e7] text-[#051823]">
                            <tr class="text-left">
                                <th class="px-5 py-3 font-semibold">Ticket</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Team</th>
                                <th class="px-5 py-3 font-semibold" x-text="meta.table_labels.sla_time"></th>
                                <th class="px-5 py-3 font-semibold">Response Time</th>
                                <th class="px-5 py-3 font-semibold" x-text="meta.table_labels.result"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">Loading report data...</td>
                                </tr>
                            </template>

                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">No rows found.</td>
                                </tr>
                            </template>

                            <template x-for="(row, index) in rows" :key="row.id">
                                <tr :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'">
                                    <td class="px-5 py-3 font-medium" x-text="row.ticket_code"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="statusBadgeClass(row.status)"
                                            x-text="statusLabel(row.status)">
                                        </span>
                                    </td>

                                    <td class="px-5 py-3" x-text="row.team"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="slaTimeBadgeClass(row.sla_time, row.result)"
                                            x-text="row.sla_time">
                                        </span>
                                    </td>

                                    <td class="px-5 py-3" x-text="row.response_time"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="resultBadgeClass(row.result)"
                                            x-text="row.result">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div
                    x-show="!loading && pagination.last_page > 1"
                    class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">

                    <div class="text-sm text-slate-600">
                        Showing
                        <span class="font-semibold" x-text="pagination.from ?? 0"></span>
                        -
                        <span class="font-semibold" x-text="pagination.to ?? 0"></span>
                        of
                        <span class="font-semibold" x-text="pagination.total ?? 0"></span>
                        tickets
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="rounded border px-3 py-1 text-sm"
                            :class="pagination.current_page <= 1
                                ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                            ‹
                        </button>

                        <template x-for="(item, idx) in visiblePages()" :key="`page-${idx}-${item}`">
                            <template x-if="item === '...'">
                                <span class="px-2 py-1 text-sm text-slate-500">...</span>
                            </template>

                            <template x-if="item !== '...'">
                                <button
                                    type="button"
                                    @click="goToPage(item)"
                                    class="rounded border px-3 py-1 text-sm"
                                    :class="item === pagination.current_page
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                                    <span x-text="item"></span>
                                </button>
                            </template>
                        </template>

                        <button
                            type="button"
                            @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="rounded border px-3 py-1 text-sm"
                            :class="pagination.current_page >= pagination.last_page
                                ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                            ›
                        </button>
                    </div>
                </div>
            </div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/reports/partials/sla-table.blade.php ENDPATH**/ ?>