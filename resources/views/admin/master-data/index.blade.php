<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        x-data="masterDataPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1600px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-6">
                <h1 class="text-[34px] font-bold text-[#051823]">MASTER DATA</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Manage categories, issue types, teams, priorities, and SLA rules.
                </p>
            </div>

            <div class="mb-5 rounded bg-white p-3 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap gap-2">
                    <template x-for="tab in tabs" :key="tab.key">
                        <button
                            type="button"
                            @click="switchTab(tab.key)"
                            class="rounded-full px-4 py-2 text-sm font-medium transition"
                            :class="activeTab === tab.key
                                ? 'bg-slate-900 text-white'
                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            x-text="tab.label"></button>
                    </template>
                </div>
            </div>

            <div class="mb-5 rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        x-model="filters.q"
                        @keydown.enter.prevent="applyFilters()"
                        type="text"
                        placeholder="Search..."
                        class="h-10 w-full max-w-[300px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">

                    <div class="ml-auto flex items-center gap-2">
                        <select
                            x-model="filters.per_page"
                            @change="applyFilters()"
                            class="h-10 w-[90px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>

                        <button
                            type="button"
                            @click="applyFilters()"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Apply
                        </button>

                        <button
                            type="button"
                            @click="resetFilters()"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Reset
                        </button>

                        <button
                            type="button"
                            @click="openCreate()"
                            class="rounded bg-slate-900 px-4 py-2 text-sm text-white shadow hover:bg-slate-800">
                            <span x-text="`+ Add ${currentLabelSingle()}`"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded bg-white shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="bg-[#051823] px-5 py-3">
                    <h2 class="text-[20px] font-semibold text-white" x-text="currentLabelPlural()"></h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-800">
                        <thead class="bg-[#d5e0e7] text-[#051823]">
                            <tr class="text-left">
                                {{-- Categories --}}
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">Code</th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">Name</th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">Slug</th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">Status</th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold">Created</th>
                                <th x-show="activeTab === 'categories'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- Issue Types --}}
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">Category</th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">Code</th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">Name</th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">Slug</th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold">Status</th>
                                <th x-show="activeTab === 'issue-types'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- Teams --}}
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">Digit</th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">Name</th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">Key</th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">Status</th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold">Created</th>
                                <th x-show="activeTab === 'teams'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- Priorities --}}
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">Digit</th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">Name</th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">Key</th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">Sort Order</th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold">Status</th>
                                <th x-show="activeTab === 'priorities'" class="px-5 py-3 font-semibold text-right">Action</th>

                                {{-- SLA Rules --}}
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">Team</th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">Priority</th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">Hours</th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold">Status</th>
                                <th x-show="activeTab === 'sla-rules'" class="px-5 py-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>

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
                                            x-text="row.is_active ? 'Active' : 'Inactive'">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3" x-text="formatDate(row.created_at)"></td>
                                    <td x-show="activeTab === 'categories'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button type="button" @click="destroyRow(row)" class="rounded bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:bg-red-100">Delete</button>
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
                                            x-text="row.is_active ? 'Active' : 'Inactive'">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'issue-types'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button type="button" @click="destroyRow(row)" class="rounded bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:bg-red-100">Delete</button>
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
                                            x-text="row.is_active ? 'Active' : 'Inactive'">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3" x-text="formatDate(row.created_at)"></td>
                                    <td x-show="activeTab === 'teams'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button type="button" @click="destroyRow(row)" class="rounded bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:bg-red-100">Delete</button>
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
                                            x-text="row.is_active ? 'Active' : 'Inactive'">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'priorities'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button type="button" @click="destroyRow(row)" class="rounded bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:bg-red-100">Delete</button>
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
                                            x-text="row.is_active ? 'Active' : 'Inactive'">
                                        </span>
                                    </td>
                                    <td x-show="activeTab === 'sla-rules'" class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" @click="openEdit(row)" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Edit</button>
                                            <button type="button" @click="destroyRow(row)" class="rounded bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:bg-red-100">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div
                    x-show="!loading && meta.last_page > 1"
                    class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
                    <div class="text-sm text-slate-600">
                        Showing
                        <span class="font-semibold" x-text="meta.from ?? 0"></span>
                        -
                        <span class="font-semibold" x-text="meta.to ?? 0"></span>
                        of
                        <span class="font-semibold" x-text="meta.total ?? 0"></span>
                        records
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="goToPage(meta.current_page - 1)"
                            :disabled="meta.current_page <= 1"
                            class="rounded border px-3 py-1 text-sm"
                            :class="meta.current_page <= 1
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
                                    :class="item === meta.current_page
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                                    <span x-text="item"></span>
                                </button>
                            </template>
                        </template>

                        <button
                            type="button"
                            @click="goToPage(meta.current_page + 1)"
                            :disabled="meta.current_page >= meta.last_page"
                            class="rounded border px-3 py-1 text-sm"
                            :class="meta.current_page >= meta.last_page
                                ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                            ›
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="modal.open"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
            style="display: none;">
            <div
                @click.outside="closeModal()"
                class="w-full max-w-[760px] rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-xl font-semibold text-slate-800" x-text="modal.mode === 'create' ? `Add ${currentLabelSingle()}` : `Edit ${currentLabelSingle()}`"></h3>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <template x-if="activeTab === 'categories'">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Category Code</label>
                                <input x-model="form.code_num" maxlength="2" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none" placeholder="2 digits, e.g. 01">
                                <p x-show="errors.code_num" x-text="errors.code_num" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input x-model="form.name" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">Slug</label>
                                <input x-model="form.slug" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none" placeholder="optional-auto-generated">
                                <p x-show="errors.slug" x-text="errors.slug" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-3 rounded-md border border-slate-300 px-4 py-3">
                                    <input x-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                                    <span class="text-sm text-slate-700">Set as active</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeTab === 'issue-types'">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Category</label>
                                <select x-model="form.category_id" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                    <option value="">Select category</option>
                                    <template x-for="item in options.categories" :key="item.id">
                                        <option :value="item.id" x-text="`${item.name} (${item.code_num})`"></option>
                                    </template>
                                </select>
                                <p x-show="errors.category_id" x-text="errors.category_id" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Issue Type Code</label>
                                <input x-model="form.code_num" maxlength="3" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none" placeholder="3 digits, e.g. 001">
                                <p x-show="errors.code_num" x-text="errors.code_num" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input x-model="form.name" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Slug</label>
                                <input x-model="form.slug" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none" placeholder="optional-auto-generated">
                                <p x-show="errors.slug" x-text="errors.slug" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-3 rounded-md border border-slate-300 px-4 py-3">
                                    <input x-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                                    <span class="text-sm text-slate-700">Set as active</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeTab === 'teams'">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Team Digit</label>
                                <input x-model="form.code_num" maxlength="1" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none" placeholder="1 digit, e.g. 1">
                                <p x-show="errors.code_num" x-text="errors.code_num" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input x-model="form.name" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">System Key</label>
                                <input x-model="form.code" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm lowercase focus:border-slate-400 focus:outline-none" placeholder="it / finance / compliance">
                                <p x-show="errors.code" x-text="errors.code" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-3 rounded-md border border-slate-300 px-4 py-3">
                                    <input x-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                                    <span class="text-sm text-slate-700">Set as active</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeTab === 'priorities'">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Priority Digit</label>
                                <input x-model="form.code_num" maxlength="1" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none" placeholder="1 digit, e.g. 2">
                                <p x-show="errors.code_num" x-text="errors.code_num" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input x-model="form.name" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">System Key</label>
                                <input x-model="form.code" type="text" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm lowercase focus:border-slate-400 focus:outline-none" placeholder="critical / high / medium / low">
                                <p x-show="errors.code" x-text="errors.code" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Sort Order</label>
                                <input x-model="form.sort_order" type="number" min="0" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                <p x-show="errors.sort_order" x-text="errors.sort_order" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="inline-flex items-center gap-3 rounded-md border border-slate-300 px-4 py-3">
                                    <input x-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                                    <span class="text-sm text-slate-700">Set as active</span>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeTab === 'sla-rules'">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Team</label>
                                <select x-model="form.team_id" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                    <option value="">Select team</option>
                                    <template x-for="item in options.teams" :key="item.id">
                                        <option :value="item.id" x-text="`${item.name} (${item.code_num})`"></option>
                                    </template>
                                </select>
                                <p x-show="errors.team_id" x-text="errors.team_id" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Priority</label>
                                <select x-model="form.priority_id" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                    <option value="">Select priority</option>
                                    <template x-for="item in options.priorities" :key="item.id">
                                        <option :value="item.id" x-text="`${item.name} (${item.code_num})`"></option>
                                    </template>
                                </select>
                                <p x-show="errors.priority_id" x-text="errors.priority_id" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Hours</label>
                                <input x-model="form.hours" type="number" min="1" class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none">
                                <p x-show="errors.hours" x-text="errors.hours" class="mt-1 text-xs text-red-600"></p>
                            </div>

                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-3 rounded-md border border-slate-300 px-4 py-3">
                                    <input x-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
                                    <span class="text-sm text-slate-700">Set as active</span>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button
                        type="button"
                        @click="closeModal()"
                        class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="submit()"
                        :disabled="saving"
                        class="rounded bg-slate-900 px-5 py-2 text-sm text-white shadow hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="saving ? 'Saving...' : (modal.mode === 'create' ? 'Create' : 'Save Changes')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function masterDataPage() {
            return {
                tabs: [{
                        key: 'categories',
                        label: 'Categories'
                    },
                    {
                        key: 'issue-types',
                        label: 'Issue Types'
                    },
                    {
                        key: 'teams',
                        label: 'Teams'
                    },
                    {
                        key: 'priorities',
                        label: 'Priorities'
                    },
                    {
                        key: 'sla-rules',
                        label: 'SLA Rules'
                    },
                ],
                activeTab: 'categories',
                loading: false,
                saving: false,
                rows: [],
                options: {
                    categories: [],
                    teams: [],
                    priorities: [],
                },
                filters: {
                    q: '',
                    per_page: '10',
                },
                meta: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 10,
                    total: 0,
                    from: null,
                    to: null,
                },
                modal: {
                    open: false,
                    mode: 'create',
                    id: null,
                },
                errors: {},
                form: {},

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.activeTab = params.get('tab') || 'categories';
                    this.filters.q = params.get('q') || '';
                    this.filters.per_page = params.get('per_page') || '10';
                    this.meta.current_page = Number(params.get('page') || 1);
                    this.loadRows();
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                },

                currentLabelSingle() {
                    return {
                        'categories': 'Category',
                        'issue-types': 'Issue Type',
                        'teams': 'Team',
                        'priorities': 'Priority',
                        'sla-rules': 'SLA Rule',
                    } [this.activeTab] || 'Data';
                },

                currentLabelPlural() {
                    return {
                        'categories': 'Categories',
                        'issue-types': 'Issue Types',
                        'teams': 'Teams',
                        'priorities': 'Priorities',
                        'sla-rules': 'SLA Rules',
                    } [this.activeTab] || 'Master Data';
                },

                buildQuery() {
                    const params = new URLSearchParams();
                    params.set('tab', this.activeTab);
                    params.set('type', this.activeTab);
                    params.set('per_page', this.filters.per_page);
                    params.set('page', this.meta.current_page || 1);

                    if (this.filters.q) {
                        params.set('q', this.filters.q);
                    }

                    return params;
                },

                switchTab(tab) {
                    this.activeTab = tab;
                    this.filters.q = '';
                    this.filters.per_page = '10';
                    this.meta.current_page = 1;
                    this.loadRows();
                },

                applyFilters() {
                    this.meta.current_page = 1;
                    this.loadRows();
                },

                resetFilters() {
                    this.filters.q = '';
                    this.filters.per_page = '10';
                    this.meta.current_page = 1;
                    this.loadRows();
                },

                async loadRows() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);

                        const response = await fetch(`/api/admin/master-data?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load master data');
                        }

                        this.rows = result.data?.rows || [];
                        this.meta = result.data?.meta || this.meta;
                        this.options = result.data?.options || this.options;
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load master data', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                blankForm() {
                    return {
                        'categories': {
                            code_num: '',
                            name: '',
                            slug: '',
                            is_active: true,
                        },
                        'issue-types': {
                            category_id: '',
                            code_num: '',
                            name: '',
                            slug: '',
                            is_active: true,
                        },
                        'teams': {
                            code_num: '',
                            name: '',
                            code: '',
                            is_active: true,
                        },
                        'priorities': {
                            code_num: '',
                            name: '',
                            code: '',
                            sort_order: 0,
                            is_active: true,
                        },
                        'sla-rules': {
                            team_id: '',
                            priority_id: '',
                            hours: '',
                            is_active: true,
                        },
                    } [this.activeTab];
                },

                openCreate() {
                    this.errors = {};
                    this.modal.open = true;
                    this.modal.mode = 'create';
                    this.modal.id = null;
                    this.form = this.blankForm();
                },

                openEdit(row) {
                    this.errors = {};
                    this.modal.open = true;
                    this.modal.mode = 'edit';
                    this.modal.id = row.id;
                    this.form = {
                        ...this.blankForm(),
                        ...row,
                    };
                },

                closeModal() {
                    this.modal.open = false;
                    this.modal.mode = 'create';
                    this.modal.id = null;
                    this.errors = {};
                    this.form = {};
                },

                async submit() {
                    this.saving = true;
                    this.errors = {};

                    try {
                        const isEdit = this.modal.mode === 'edit';
                        const url = isEdit ?
                            `/api/admin/master-data/${this.activeTab}/${this.modal.id}` :
                            `/api/admin/master-data/${this.activeTab}`;

                        const method = isEdit ? 'PATCH' : 'POST';

                        const response = await fetch(url, {
                            method,
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(this.form),
                        });

                        const result = await response.json();

                        if (response.status === 422) {
                            this.errors = this.mapErrors(result.errors || {});
                            throw new Error(result.message || 'Validation failed');
                        }

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to save data');
                        }

                        this.showAlert(result.message || 'Saved successfully.', 'success');
                        this.closeModal();
                        this.loadRows();
                    } catch (error) {
                        if (!Object.keys(this.errors).length) {
                            this.showAlert(error.message || 'Failed to save data', 'error');
                        }
                    } finally {
                        this.saving = false;
                    }
                },

                async destroyRow(row) {
                    const label = row.name || row.code || `${row.team_name || ''} - ${row.priority_name || ''}`.trim();

                    if (!confirm(`Delete this ${this.currentLabelSingle().toLowerCase()}${label ? `: ${label}` : ''}?`)) {
                        return;
                    }

                    try {
                        const response = await fetch(`/api/admin/master-data/${this.activeTab}/${row.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to delete data');
                        }

                        this.showAlert(result.message || 'Deleted successfully.', 'success');

                        if (this.rows.length === 1 && this.meta.current_page > 1) {
                            this.meta.current_page -= 1;
                        }

                        this.loadRows();
                    } catch (error) {
                        this.showAlert(error.message || 'Failed to delete data', 'error');
                    }
                },

                mapErrors(errors) {
                    const mapped = {};
                    Object.keys(errors).forEach(key => {
                        mapped[key] = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                    });
                    return mapped;
                },

                visiblePages() {
                    const current = this.meta.current_page || 1;
                    const last = this.meta.last_page || 1;

                    if (last <= 7) {
                        return Array.from({
                            length: last
                        }, (_, i) => i + 1);
                    }

                    const pages = [1];

                    if (current > 3) {
                        pages.push('...');
                    }

                    const start = Math.max(2, current - 1);
                    const end = Math.min(last - 1, current + 1);

                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }

                    if (current < last - 2) {
                        pages.push('...');
                    }

                    pages.push(last);

                    return pages;
                },

                goToPage(page) {
                    if (page < 1 || page > this.meta.last_page) return;
                    this.meta.current_page = page;
                    this.loadRows();
                },

                formatDate(value) {
                    if (!value) return '-';

                    const date = new Date(value);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    });
                },

                showAlert(message, type = 'success') {
                    const el = document.getElementById('page-alert');
                    if (!el) return;

                    el.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                    el.textContent = message;

                    if (type === 'success') {
                        el.classList.add('bg-green-100', 'text-green-800');
                    } else {
                        el.classList.add('bg-red-100', 'text-red-800');
                    }

                    setTimeout(() => el.classList.add('hidden'), 3000);
                },
            }
        }
    </script>
</x-app-layout>