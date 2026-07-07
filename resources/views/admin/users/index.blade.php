{{-- ========= ADMIN USERS INDEX ========= --}}
{{-- User management filter and table containers for API-backed rendering. --}}

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        data-can-manage-users="{{ auth()->user()?->role === 'admin' ? '1' : '0' }}"
        x-data="{ ...adminUsersPage(), canManageUsers: false }"
        x-init="canManageUsers = $el.dataset.canManageUsers === '1'; init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1600px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-6">
                <h1 class="text-[34px] font-bold text-[#051823]">USERS</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Manage system users, roles, and account status.
                </p>
            </div>

            {{-- ========= FILTER BAR ========= --}}
            <div class="mb-5 rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        x-model="filters.q"
                        @keydown.enter.prevent="applyFilters()"
                        type="text"
                        placeholder="Search by name or email..."
                        class="h-10 w-full max-w-[320px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">

                    <select
                        x-model="filters.role"
                        @change="applyFilters()"
                        class="h-10 w-[140px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                        <option value="all">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="cs">CS</option>
                        <option value="head_cs">Head CS</option>
                        <option value="it">IT</option>
                        <option value="supervisor">Supervisor</option>
                    </select>

                    <select
                        x-model="filters.status"
                        @change="applyFilters()"
                        class="h-10 w-[140px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

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

                        <a
                            x-show="canManageUsers"
                            x-cloak
                            href="{{ route('admin.users.create') }}"
                            class="rounded bg-slate-900 px-4 py-2 text-sm text-white shadow hover:bg-slate-800">
                            + Add User
                        </a>
                    </div>
                </div>
            </div>

            {{-- ========= TABLE ========= --}}
            <div class="overflow-hidden rounded bg-white shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="bg-[#051823] px-5 py-3">
                    <h2 class="text-[20px] font-semibold text-white">User Management</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-slate-800">
                        <thead class="bg-[#d5e0e7] text-[#051823]">
                            <tr class="text-left">
                                <th class="px-5 py-3 font-semibold">Name</th>
                                <th class="px-5 py-3 font-semibold">Email</th>
                                <th class="px-5 py-3 font-semibold">Role</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Created</th>
                                <th class="px-5 py-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                        Loading users...
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                        No users found.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(row, index) in rows" :key="row.id">
                                <tr :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'">
                                    <td class="px-5 py-3 font-medium" x-text="row.name"></td>
                                    <td class="px-5 py-3" x-text="row.email"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase"
                                            :class="roleBadgeClass(row.role)"
                                            x-text="roleLabel(row.role)">
                                        </span>
                                    </td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="row.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-700'"
                                            x-text="row.is_active ? 'Active' : 'Inactive'">
                                        </span>
                                    </td>

                                    <td class="px-5 py-3" x-text="formatDate(row.created_at)"></td>

                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <template x-if="canManageUsers">
                                                <div class="flex justify-end gap-2">
                                                    <a
                                                        :href="`/admin/users/${row.id}/edit`"
                                                        class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">
                                                        Edit
                                                    </a>

                                                    <button
                                                        type="button"
                                                        @click="toggleStatus(row)"
                                                        class="rounded px-3 py-1.5 text-xs font-medium"
                                                        :class="row.is_active
                                                            ? 'bg-red-50 text-red-700 hover:bg-red-100'
                                                            : 'bg-green-50 text-green-700 hover:bg-green-100'"
                                                        x-text="row.is_active ? 'Deactivate' : 'Activate'">
                                                    </button>
                                                </div>
                                            </template>

                                            <template x-if="!canManageUsers">
                                                <span class="text-xs text-slate-400">View only</span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
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
                        users
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

            {{-- ========= USER STATUS CONFIRMATION MODAL ========= --}}
            <div
                x-show="confirmation.open"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                style="display: none;">
                <div class="w-full max-w-[560px] rounded-2xl bg-white shadow-2xl">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-slate-800" x-text="`${confirmation.actionLabel} User`"></h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Please review this account status change before continuing.
                        </p>
                    </div>

                    <div class="space-y-5 px-6 py-5">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm font-semibold text-slate-700">Change Summary</div>
                            <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
                                <template x-for="change in confirmation.changes" :key="change.key">
                                    <div class="grid grid-cols-1 gap-2 border-b border-slate-100 px-3 py-2 text-sm last:border-b-0 md:grid-cols-[145px_1fr]">
                                        <div class="font-medium text-slate-600" x-text="change.label"></div>
                                        <div class="text-slate-700">
                                            <span class="text-slate-400" x-text="change.before"></span>
                                            <span class="mx-2 text-slate-400">→</span>
                                            <span class="font-semibold text-slate-900" x-text="change.after"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <div class="font-semibold">Review required</div>
                            <p class="mt-1 text-xs leading-5">
                                This action changes whether the user can access the system. Continue only if this status change is intentional.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                        <button
                            type="button"
                            @click="closeConfirmation()"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>

                        <button
                            type="button"
                            @click="confirmToggleStatus()"
                            class="rounded bg-slate-900 px-5 py-2 text-sm text-white shadow hover:bg-slate-800">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>

    </div>

</x-app-layout>