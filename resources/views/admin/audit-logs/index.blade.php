<x-app-layout>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div
        x-data="auditLogsPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1600px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-6">
                <h1 class="text-[34px] font-bold text-[#051823]">AUDIT LOGS</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Monitor admin changes, ticket activity, resolver messages, and system events.
                </p>
            </div>

            <div class="mb-5 grid gap-4 md:grid-cols-4">
                <div class="rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <p class="text-sm text-slate-500">Total Logs</p>
                    <p class="mt-2 text-2xl font-bold text-[#051823]" x-text="summary.total ?? 0"></p>
                </div>

                <div class="rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <p class="text-sm text-slate-500">Today</p>
                    <p class="mt-2 text-2xl font-bold text-[#051823]" x-text="summary.today ?? 0"></p>
                </div>

                <div class="rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <p class="text-sm text-slate-500">Last 7 Days</p>
                    <p class="mt-2 text-2xl font-bold text-[#051823]" x-text="summary.last_7_days ?? 0"></p>
                </div>

                <div class="rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <p class="text-sm text-slate-500">Critical Changes</p>
                    <p class="mt-2 text-2xl font-bold text-red-600" x-text="summary.critical_changes ?? 0"></p>
                </div>
            </div>

            <div class="mb-5 overflow-visible rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        x-model="filters.q"
                        @keydown.enter.prevent="applyFilters()"
                        type="text"
                        placeholder="Search actor, action, ticket, IP, or description..."
                        class="h-10 w-full max-w-[340px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">

                    <select
                        x-model="filters.action"
                        @change="applyFilters()"
                        class="h-10 w-[140px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                        <option value="all">All Actions</option>
                        <template x-for="action in options.actions" :key="action.value">
                            <option :value="action.value" x-text="action.label"></option>
                        </template>
                    </select>

                    <select
                        x-model="filters.entity"
                        @change="applyFilters()"
                        class="h-10 w-[120px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                        <option value="all">All Entities</option>
                        <template x-for="entity in options.entities" :key="entity.value">
                            <option :value="entity.value" x-text="entity.label"></option>
                        </template>
                    </select>

                    <select
                        x-model="filters.date_range"
                        @change="applyFilters()"
                        class="h-10 w-[120px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                        <option value="today">Today</option>
                        <option value="7d">Last 7 days</option>
                        <option value="30d">Last 30 days</option>
                        <option value="all">All time</option>
                        <option value="custom">Custom</option>
                    </select>

                    <template x-if="filters.date_range === 'custom'">
                        <div class="flex flex-wrap items-center gap-2">
                            <input
                                x-model="filters.date_from"
                                @change="applyFilters()"
                                type="date"
                                class="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">

                            <input
                                x-model="filters.date_to"
                                @change="applyFilters()"
                                type="date"
                                class="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                        </div>
                    </template>

                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        <select
                            x-model="filters.per_page"
                            @change="applyFilters()"
                            class="h-10 w-[60px] rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                        </select>

                        <button
                            type="button"
                            @click="applyFilters()"
                            class="h-10 rounded-md border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Apply
                        </button>

                        <div class="relative" @click.outside="exportOpen = false">
                            <button
                                type="button"
                                @click="exportOpen = !exportOpen"
                                :disabled="exporting"
                                class="inline-flex h-10 items-center gap-2 rounded-md bg-[#2f80d1] px-5 text-sm font-semibold text-white shadow-sm hover:bg-[#246bb0] disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-text="exporting ? 'Exporting...' : 'Export Data'"></span>

                                <svg
                                    class="h-4 w-4 transition-transform"
                                    :class="exportOpen ? 'rotate-180' : ''"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div
                                x-show="exportOpen"
                                x-transition
                                x-cloak
                                class="absolute right-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                <button
                                    type="button"
                                    @click="exportLogs('csv'); exportOpen = false"
                                    class="block w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    Export CSV
                                </button>

                                <button
                                    type="button"
                                    @click="exportLogs('excel'); exportOpen = false"
                                    class="block w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    Export Excel
                                </button>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="resetFilters()"
                            class="h-10 rounded-md border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded bg-white shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="bg-[#051823] px-5 py-3">
                    <h2 class="text-[20px] font-semibold text-white">Activity History</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1180px] text-sm text-slate-800">
                        <thead class="bg-[#d5e0e7] text-[#051823]">
                            <tr class="text-left">
                                <th class="w-[170px] px-5 py-3 font-semibold">Time</th>
                                <th class="w-[220px] px-5 py-3 font-semibold">Actor</th>
                                <th class="w-[150px] px-5 py-3 font-semibold">Action</th>
                                <th class="w-[220px] px-5 py-3 font-semibold">Entity</th>
                                <th class="px-5 py-3 font-semibold">Description</th>
                                <th class="w-[130px] px-5 py-3 font-semibold">IP</th>
                                <th class="w-[100px] px-5 py-3 font-semibold text-right">Details</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                        Loading audit logs...
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                        No audit logs found.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(row, index) in rows" :key="row.id">
                                <tr :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'">
                                    <td class="whitespace-nowrap px-5 py-3" x-text="row.created_label || formatDate(row.created_at)"></td>

                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-slate-900" x-text="row.actor_name || 'System'"></div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-slate-500">
                                            <span x-text="row.actor_email || '-'"></span>
                                            <span
                                                x-show="row.actor_role"
                                                class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold uppercase text-slate-600"
                                                x-text="row.actor_role"></span>
                                        </div>
                                    </td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="actionBadgeClass(row.action)"
                                            x-text="row.action_label"></span>
                                    </td>

                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-slate-900" x-text="row.entity_label || '-'"></div>
                                        <div class="text-xs text-slate-500">
                                            <span x-text="row.entity_label_type"></span>
                                            <span x-show="row.entity_id">#<span x-text="row.entity_id"></span></span>
                                        </div>
                                    </td>

                                    <td class="px-5 py-3 text-slate-700">
                                        <div class="max-w-[420px] truncate" x-text="row.description || '-'"></div>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-slate-600" x-text="row.ip_address || '-'"></td>

                                    <td class="px-5 py-3 text-right">
                                        <button
                                            type="button"
                                            @click="openDetail(row)"
                                            class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">
                                            View
                                        </button>
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
                        logs
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
            x-show="detailOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-4xl overflow-hidden rounded bg-white shadow-xl" @click.outside="detailOpen = false">
                <div class="flex items-center justify-between bg-[#051823] px-5 py-4 text-white">
                    <div>
                        <h3 class="text-lg font-semibold">Audit Detail</h3>
                        <p class="text-xs text-slate-300" x-text="selectedLog?.created_label || '-'"></p>
                    </div>

                    <button type="button" @click="detailOpen = false" class="text-2xl leading-none text-white hover:text-slate-200">
                        ×
                    </button>
                </div>

                <div class="max-h-[75vh] overflow-y-auto p-5">
                    <div class="mb-5 grid gap-4 md:grid-cols-3">
                        <div class="rounded border border-slate-200 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-500">Actor</p>
                            <p class="mt-1 font-semibold text-slate-900" x-text="selectedLog?.actor_name || 'System'"></p>
                            <p class="text-xs text-slate-500" x-text="selectedLog?.actor_email || '-'"></p>
                        </div>

                        <div class="rounded border border-slate-200 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-500">Action</p>
                            <p class="mt-1 font-semibold text-slate-900" x-text="selectedLog?.action_label || '-'"></p>
                            <p class="text-xs text-slate-500" x-text="selectedLog?.entity_label_type || '-'"></p>
                        </div>

                        <div class="rounded border border-slate-200 p-3">
                            <p class="text-xs font-semibold uppercase text-slate-500">IP Address</p>
                            <p class="mt-1 font-semibold text-slate-900" x-text="selectedLog?.ip_address || '-'"></p>
                            <p class="truncate text-xs text-slate-500" x-text="selectedLog?.user_agent || '-'"></p>
                        </div>
                    </div>

                    <div class="mb-5 rounded border border-slate-200 p-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Description</p>
                        <p class="mt-1 text-slate-800" x-text="selectedLog?.description || '-'"></p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded border border-slate-200">
                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-2 font-semibold text-slate-700">Before</div>
                            <pre class="max-h-[320px] overflow-auto p-4 text-xs text-slate-700" x-text="prettyJson(selectedLog?.before_values)"></pre>
                        </div>

                        <div class="rounded border border-slate-200">
                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-2 font-semibold text-slate-700">After</div>
                            <pre class="max-h-[320px] overflow-auto p-4 text-xs text-slate-700" x-text="prettyJson(selectedLog?.after_values)"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>