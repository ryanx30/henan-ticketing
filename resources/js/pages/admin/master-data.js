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
                    sort_by: '',
                    sort_dir: 'asc',
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
                    this.filters.sort_by = params.get('sort_by') || '';
                    this.filters.sort_dir = params.get('sort_dir') === 'desc' ? 'desc' : 'asc';
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

                    if (this.filters.sort_by) {
                        params.set('sort_by', this.filters.sort_by);
                        params.set('sort_dir', this.filters.sort_dir || 'asc');
                    }

                    return params;
                },

                switchTab(tab) {
                    this.activeTab = tab;
                    this.filters.q = '';
                    this.filters.per_page = '10';
                    this.filters.sort_by = '';
                    this.filters.sort_dir = 'asc';
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
                    this.filters.sort_by = '';
                    this.filters.sort_dir = 'asc';
                    this.meta.current_page = 1;
                    this.loadRows();
                },

                sortBy(column) {
                    if (this.filters.sort_by === column) {
                        this.filters.sort_dir = this.filters.sort_dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.filters.sort_by = column;
                        this.filters.sort_dir = 'asc';
                    }

                    this.meta.current_page = 1;
                    this.loadRows();
                },

                sortIcon(column) {
                    if (this.filters.sort_by !== column) {
                        return '↕';
                    }

                    return this.filters.sort_dir === 'asc' ? '↑' : '↓';
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

window.masterDataPage = masterDataPage;
