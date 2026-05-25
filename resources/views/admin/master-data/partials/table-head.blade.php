                        <thead class="bg-[#d5e0e7] text-[#051823]">
                            <tr class="text-left">
                                {{-- Categories --}}
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('code_num')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Code <span class="text-xs" x-text="sortIcon('code_num')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('name')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Name <span class="text-xs" x-text="sortIcon('name')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('slug')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Slug <span class="text-xs" x-text="sortIcon('slug')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('is_active')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Status <span class="text-xs" x-text="sortIcon('is_active')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('created_at')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Created <span class="text-xs" x-text="sortIcon('created_at')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- Issue Types --}}
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('category')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Category <span class="text-xs" x-text="sortIcon('category')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('code_num')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Code <span class="text-xs" x-text="sortIcon('code_num')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('name')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Name <span class="text-xs" x-text="sortIcon('name')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('slug')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Slug <span class="text-xs" x-text="sortIcon('slug')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('is_active')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Status <span class="text-xs" x-text="sortIcon('is_active')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- Teams --}}
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('code_num')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Digit <span class="text-xs" x-text="sortIcon('code_num')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('name')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Name <span class="text-xs" x-text="sortIcon('name')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('code')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Key <span class="text-xs" x-text="sortIcon('code')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('is_active')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Status <span class="text-xs" x-text="sortIcon('is_active')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('created_at')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Created <span class="text-xs" x-text="sortIcon('created_at')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- Priorities --}}
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('code_num')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Digit <span class="text-xs" x-text="sortIcon('code_num')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('name')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Name <span class="text-xs" x-text="sortIcon('name')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('code')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Key <span class="text-xs" x-text="sortIcon('code')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('sort_order')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Sort Order <span class="text-xs" x-text="sortIcon('sort_order')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('is_active')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Status <span class="text-xs" x-text="sortIcon('is_active')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- SLA Rules --}}
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('team')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Team <span class="text-xs" x-text="sortIcon('team')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('priority')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Priority <span class="text-xs" x-text="sortIcon('priority')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('hours')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Hours <span class="text-xs" x-text="sortIcon('hours')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">
                                    <button type="button" @click="sortBy('is_active')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Status <span class="text-xs" x-text="sortIcon('is_active')"></span>
                                    </button>
                                </th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
