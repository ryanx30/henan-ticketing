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

window.adminUsersPage = adminUsersPage;
export default adminUsersPage;
