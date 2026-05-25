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
