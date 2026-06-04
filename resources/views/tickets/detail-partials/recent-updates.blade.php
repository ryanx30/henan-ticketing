{{-- ========= RECENT UPDATES ========= --}}
{{-- Latest ticket-related updates displayed on the detail page. --}}

                    {{-- ========= RECENT UPDATES ========= --}}
                    <div class="rounded-sm border border-slate-200 bg-white shadow-lg">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-[20px] font-bold text-slate-900">Recent Updates</h2>
                            <p class="mt-1 text-sm text-slate-500">Messages or resolver updates related to this ticket.</p>
                        </div>

                        <div class="px-6 py-6">
                            <template x-if="updates.length === 0">
                                <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                    No updates available.
                                </div>
                            </template>

                            <div class="space-y-4" x-show="updates.length > 0">
                                <template x-for="item in updates" :key="item.id || item.created_at">
                                    <div class="group rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 transition hover:border-slate-300 hover:bg-white">
                                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-sky-700">
                                                        Message
                                                    </span>

                                                    <template x-if="isUnreadUpdate(item)">
                                                        <span class="inline-flex rounded-full bg-slate-300 px-2.5 py-1 text-[11px] font-bold text-white">
                                                            New
                                                        </span>
                                                    </template>
                                                </div>

                                                <div class="mt-3 text-[15px] font-semibold text-slate-900" x-text="displayUpdateTitle(item)"></div>

                                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    <span class="font-mono text-slate-600" x-text="currentTicketLabel()"></span>
                                                    <span>•</span>
                                                    <span x-text="updateParticipants(item)"></span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between gap-3 md:flex-col md:items-end">
                                                <div class="text-xs text-slate-500" x-text="formatDateTime(item.created_at)"></div>

                                                <div class="flex items-center gap-2 opacity-100 transition md:opacity-0 md:group-hover:opacity-100">
                                                    <template x-if="isUnreadUpdate(item)">
                                                        <button
                                                            type="button"
                                                            @click.stop="markUpdateAsRead(item)"
                                                            class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                            Mark as Read
                                                        </button>
                                                    </template>

                                                    <button
                                                        type="button"
                                                        @click.stop="openCompose(item)"
                                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                        Reply
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click.stop="openMessageDetail(item)"
                                                        class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                        Open Message
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700" x-text="updateBody(item)"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
