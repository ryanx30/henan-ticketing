{{-- ========= MASTER DATA TABLE BODY ========= --}}
{{-- Alpine-rendered rows and empty/loading states for master data. --}}

                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                        Loading master data...
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                        No data found.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(row, index) in rows" :key="`${activeTab}-${row.id}`">
                                <tr :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'">
                                    {{-- Categories --}}
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold" x-text="row.code_num"></td>
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3 font-medium" x-text="row.name"></td>
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3" x-text="row.slug"></td>
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-700'"
                                            x-text="statusLabel(row)">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3" x-text="formatDate(row.created_at)"></td>
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button x-show="canUpdateCurrent()" x-cloak type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button x-show="canToggleStatusCurrent()" x-cloak type="button" @click="toggleStatus(row)" :class="statusActionClass(row)" x-text="statusActionLabel(row)"></button>
                                            <span x-show="isViewOnly()" x-cloak class="text-xs text-slate-400">View only</span>
                                        </div>
                                    </td>

                                    {{-- Issue Types --}}
                                    <td x-show="activeTab === 'issue-types'" class="px-5 py-3" x-text="row.category_name || '-'"></td>
                                    <td x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold" x-text="row.code_num"></td>
                                    <td x-show="activeTab === 'issue-types'" class="px-5 py-3 font-medium" x-text="row.name"></td>
                                    <td x-show="activeTab === 'issue-types'" class="px-5 py-3" x-text="row.slug"></td>
                                    <td x-show="activeTab === 'issue-types'" class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-700'"
                                            x-text="statusLabel(row)">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'issue-types'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button x-show="canUpdateCurrent()" x-cloak type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button x-show="canToggleStatusCurrent()" x-cloak type="button" @click="toggleStatus(row)" :class="statusActionClass(row)" x-text="statusActionLabel(row)"></button>
                                            <span x-show="isViewOnly()" x-cloak class="text-xs text-slate-400">View only</span>
                                        </div>
                                    </td>

                                    {{-- Teams --}}
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold" x-text="row.code_num"></td>
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3 font-medium" x-text="row.name"></td>
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3" x-text="row.code"></td>
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-700'"
                                            x-text="statusLabel(row)">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3" x-text="formatDate(row.created_at)"></td>
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button x-show="canUpdateCurrent()" x-cloak type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button x-show="canToggleStatusCurrent()" x-cloak type="button" @click="toggleStatus(row)" :class="statusActionClass(row)" x-text="statusActionLabel(row)"></button>
                                            <span x-show="isViewOnly()" x-cloak class="text-xs text-slate-400">View only</span>
                                        </div>
                                    </td>

                                    {{-- Priorities --}}
                                    <td x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold" x-text="row.code_num"></td>
                                    <td x-show="activeTab === 'priorities'" class="px-5 py-3 font-medium" x-text="row.name"></td>
                                    <td x-show="activeTab === 'priorities'" class="px-5 py-3" x-text="row.code"></td>
                                    <td x-show="activeTab === 'priorities'" class="px-5 py-3" x-text="row.sort_order"></td>
                                    <td x-show="activeTab === 'priorities'" class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-700'"
                                            x-text="statusLabel(row)">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'priorities'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button x-show="canUpdateCurrent()" x-cloak type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button x-show="canToggleStatusCurrent()" x-cloak type="button" @click="toggleStatus(row)" :class="statusActionClass(row)" x-text="statusActionLabel(row)"></button>
                                            <span x-show="isViewOnly()" x-cloak class="text-xs text-slate-400">View only</span>
                                        </div>
                                    </td>

                                    {{-- SLA Rules --}}
                                    <td x-show="activeTab === 'sla-rules'" class="px-5 py-3" x-text="`${row.team_name || '-'} (${row.team_code_num || '-'})`"></td>
                                    <td x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-medium" x-text="`${row.priority_name || '-'} (${row.priority_code_num || '-'})`"></td>
                                    <td x-show="activeTab === 'sla-rules'" class="px-5 py-3" x-text="`${row.hours}h`"></td>
                                    <td x-show="activeTab === 'sla-rules'" class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-700'"
                                            x-text="statusLabel(row)">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'sla-rules'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button x-show="canUpdateCurrent()" x-cloak type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button x-show="canToggleStatusCurrent()" x-cloak type="button" @click="toggleStatus(row)" :class="statusActionClass(row)" x-text="statusActionLabel(row)"></button>
                                            <span x-show="isViewOnly()" x-cloak class="text-xs text-slate-400">View only</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
