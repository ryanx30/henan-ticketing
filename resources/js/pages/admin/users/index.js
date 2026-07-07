/**
 * Admin users list page controller.
 * Loads users, applies filters, handles pagination, and triggers user activation/deactivation actions.
 */

import { apiGet, apiPatch } from '../../../utils/apiClient';
import { formatDate as formatSharedDate } from '../../../utils/formatter';
import { showAlert as showSharedAlert } from '../../../utils/toast';

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
        confirmation: {
            open: false,
            row: null,
            changes: [],
            actionLabel: '',
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
                const result = await apiGet(`/api/admin/users?${params.toString()}`);

                this.rows = result.data?.rows || [];
                this.meta = result.data?.meta || this.meta;
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load users', 'error');
            } finally {
                this.loading = false;
            }
        },

        toggleStatus(row) {
            const nextStatus = !row.is_active;

            this.confirmation = {
                open: true,
                row,
                actionLabel: nextStatus ? 'Activate' : 'Deactivate',
                changes: [{
                    key: 'is_active',
                    label: 'Account Status',
                    before: row.is_active ? 'Active' : 'Inactive',
                    after: nextStatus ? 'Active' : 'Inactive',
                }],
            };
        },

        closeConfirmation() {
            this.confirmation = {
                open: false,
                row: null,
                changes: [],
                actionLabel: '',
            };
        },

        async confirmToggleStatus() {
            const row = this.confirmation.row;

            if (!row) {
                this.closeConfirmation();
                return;
            }

            try {
                const result = await apiPatch(`/api/admin/users/${row.id}/status`, {});

                row.is_active = result.data?.is_active ?? row.is_active;
                this.showAlert(result.message || 'Status updated successfully', 'success');
                this.closeConfirmation();
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
                case 'head_cs':
                    return 'bg-cyan-100 text-cyan-700';
                case 'it':
                    return 'bg-amber-100 text-amber-700';
                case 'supervisor':
                    return 'bg-violet-100 text-violet-700';
                default:
                    return 'bg-slate-100 text-slate-700';
            }
        },


        roleLabel(role) {
            switch (role) {
                case 'admin':
                    return 'Admin';
                case 'head_cs':
                    return 'Head CS';
                case 'cs':
                    return 'CS';
                case 'it':
                    return 'IT';
                case 'supervisor':
                    return 'Supervisor';
                default:
                    return role || '-';
            }
        },

        formatDate(value) {
            return formatSharedDate(value, 'en-US');
        },

        showAlert(message, type = 'success') {
            showSharedAlert(message, type);
        },
    }
}

window.adminUsersPage = adminUsersPage;
export default adminUsersPage;
