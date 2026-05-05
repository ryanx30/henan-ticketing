<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        x-data="adminUsersPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1600px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-6">
                <h1 class="text-[34px] font-bold text-[#051823]">USERS</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Manage system users, roles, and account status.
                </p>
            </div>

            {{-- FILTER BAR --}}
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
                            href="{{ route('admin.users.create') }}"
                            class="rounded bg-slate-900 px-4 py-2 text-sm text-white shadow hover:bg-slate-800">
                            + Add User
                        </a>
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
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
                                            x-text="row.role">
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
    </div>

    <script>
        function adminUsersPage() {
            return {
                loading: false,
                rows: [],
                filters: {
                    q: '',
                    role: 'all',
                    status: 'all',
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

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.filters.q = params.get('q') || '';
                    this.filters.role = params.get('role') || 'all';
                    this.filters.status = params.get('status') || 'all';
                    this.filters.per_page = params.get('per_page') || '10';
                    this.meta.current_page = Number(params.get('page') || 1);

                    this.loadUsers();
                },

                buildQuery(includePage = true) {
                    const params = new URLSearchParams();

                    if (this.filters.q) params.set('q', this.filters.q);
                    if (this.filters.role) params.set('role', this.filters.role);
                    if (this.filters.status) params.set('status', this.filters.status);
                    params.set('per_page', this.filters.per_page);

                    if (includePage) {
                        params.set('page', this.meta.current_page || 1);
                    }

                    return params;
                },

                applyFilters() {
                    this.meta.current_page = 1;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadUsers();
                },

                resetFilters() {
                    this.filters.q = '';
                    this.filters.role = 'all';
                    this.filters.status = 'all';
                    this.filters.per_page = '10';
                    this.meta.current_page = 1;

                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadUsers();
                },

                async loadUsers() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const response = await fetch(`/api/admin/users?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load users');
                        }

                        this.rows = result.data?.rows || [];
                        this.meta = result.data?.meta || this.meta;
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load users', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async toggleStatus(row) {
                    try {
                        const response = await fetch(`/api/admin/users/${row.id}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to update status');
                        }

                        row.is_active = result.data?.is_active ?? row.is_active;
                        this.showAlert(result.message || 'Status updated successfully', 'success');
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to update status', 'error');
                    }
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
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadUsers();
                },

                roleBadgeClass(role) {
                    switch (role) {
                        case 'admin':
                            return 'bg-red-100 text-red-700';
                        case 'cs':
                            return 'bg-blue-100 text-blue-700';
                        case 'it':
                            return 'bg-amber-100 text-amber-700';
                        case 'supervisor':
                            return 'bg-violet-100 text-violet-700';
                        default:
                            return 'bg-slate-100 text-slate-700';
                    }
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

                    setTimeout(() => {
                        el.classList.add('hidden');
                    }, 3000);
                },
            }
        }
    </script>
</x-app-layout>