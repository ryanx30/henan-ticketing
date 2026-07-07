/**
 * Admin master data page controller.
 * Manages master data tables, filters, modal state, CRUD actions, confirmation flow, and validation feedback.
 */

import { apiGet, apiPatch, apiPost } from '../../utils/apiClient';
import { formatDate as formatSharedDate } from '../../utils/formatter';
import { showAlert as showSharedAlert } from '../../utils/toast';

function masterDataPage() {
    return {
        allTabs: [{
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
        tabs: [],
        activeTab: 'categories',
        loading: false,
        saving: false,
        rows: [],
        options: {
            categories: [],
            teams: [],
            priorities: [],
            permissions: {
                can_create: false,
                can_update: false,
                can_toggle_status: false,
            },
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
        confirmation: {
            open: false,
            action: null,
            title: '',
            message: '',
            warning: '',
            changes: [],
            reason: '',
            errors: {},
            row: null,
            payload: null,
        },
        errors: {},
        form: {},
        originalForm: {},

        init() {
            this.tabs = this.allTabs;
            const params = new URLSearchParams(window.location.search);
            this.activeTab = params.get('tab') || 'categories';
            this.filters.q = params.get('q') || '';
            this.filters.per_page = params.get('per_page') || '10';
            this.filters.sort_by = params.get('sort_by') || '';
            this.filters.sort_dir = params.get('sort_dir') === 'desc' ? 'desc' : 'asc';
            this.meta.current_page = Number(params.get('page') || 1);
            this.loadRows();
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

        permissions() {
            return this.options.permissions || {};
        },

        canCreateCurrent() {
            return !!this.permissions().can_create;
        },

        canUpdateCurrent() {
            return !!this.permissions().can_update;
        },

        canToggleStatusCurrent() {
            return !!this.permissions().can_toggle_status;
        },

        isViewOnly() {
            return !this.canCreateCurrent() && !this.canUpdateCurrent() && !this.canToggleStatusCurrent();
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
            if (!this.tabs.some(item => item.key === tab)) return;

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

                const result = await apiGet(`/api/admin/master-data?${params.toString()}`);

                this.rows = result.data?.rows || [];
                this.meta = result.data?.meta || this.meta;
                this.options = result.data?.options || this.options;

                if (Array.isArray(this.options.allowed_types) && this.options.allowed_types.length > 0) {
                    this.tabs = this.allTabs.filter(tab => this.options.allowed_types.includes(tab.key));

                    if (!this.tabs.some(tab => tab.key === this.activeTab)) {
                        this.activeTab = this.tabs[0]?.key || 'categories';
                    }
                }
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
            } [this.activeTab] || {};
        },

        openCreate() {
            if (!this.canCreateCurrent()) {
                this.showAlert('You only have view access to Master Data.', 'error');
                return;
            }

            this.errors = {};
            this.modal.open = true;
            this.modal.mode = 'create';
            this.modal.id = null;
            this.form = this.blankForm();
            this.originalForm = {};
        },

        openEdit(row) {
            if (!this.canUpdateCurrent()) {
                this.showAlert('You only have view access to Master Data.', 'error');
                return;
            }

            this.errors = {};
            this.modal.open = true;
            this.modal.mode = 'edit';
            this.modal.id = row.id;
            this.form = {
                ...this.blankForm(),
                ...row,
            };
            this.originalForm = { ...this.form };
        },

        closeModal() {
            this.modal.open = false;
            this.modal.mode = 'create';
            this.modal.id = null;
            this.errors = {};
            this.form = {};
            this.originalForm = {};
            this.closeConfirmation();
        },

        fieldLabelMap() {
            return {
                'categories': {
                    name: 'Category Name',
                    slug: 'Category Slug',
                    is_active: 'Status',
                },
                'issue-types': {
                    category_id: 'Linked Category',
                    name: 'Issue Type Name',
                    slug: 'Issue Type Slug',
                    is_active: 'Status',
                },
                'teams': {
                    name: 'Team Name',
                    code: 'Team System Key',
                    is_active: 'Status',
                },
                'priorities': {
                    name: 'Priority Name',
                    code: 'Priority System Key',
                    sort_order: 'Priority Sort Order',
                    is_active: 'Status',
                },
                'sla-rules': {
                    team_id: 'SLA Team',
                    priority_id: 'SLA Priority',
                    hours: 'SLA Hours',
                    is_active: 'Status',
                },
            } [this.activeTab] || {};
        },

        sensitiveFieldMap() {
            return this.fieldLabelMap();
        },

        fieldKeysForCurrentTab() {
            return Object.keys(this.fieldLabelMap());
        },

        normalizeForCompare(value) {
            if (value === null || value === undefined) {
                return '';
            }

            if (typeof value === 'boolean') {
                return value ? '1' : '0';
            }

            return String(value).trim();
        },

        referenceLabel(key, value) {
            const optionMap = {
                category_id: this.options.categories || [],
                team_id: this.options.teams || [],
                priority_id: this.options.priorities || [],
            };

            const item = (optionMap[key] || [])
                .find(option => String(option.id) === String(value));

            return item ? `${item.name}${item.code_num ? ` (${item.code_num})` : ''}` : value;
        },

        formatFieldValue(key, value) {
            if (key === 'is_active') {
                return this.normalizeForCompare(value) === '1' ? 'Active' : 'Inactive';
            }

            if (['category_id', 'team_id', 'priority_id'].includes(key)) {
                return this.referenceLabel(key, value) || '-';
            }

            return this.normalizeForCompare(value) || '-';
        },

        formChanges() {
            const labels = this.fieldLabelMap();

            if (this.modal.mode === 'create') {
                return this.fieldKeysForCurrentTab()
                    .map(key => ({
                        key,
                        label: labels[key] || key,
                        before: '-',
                        after: this.formatFieldValue(key, this.form[key]),
                    }))
                    .filter(change => change.after !== '-');
            }

            return this.fieldKeysForCurrentTab()
                .filter(key => this.normalizeForCompare(this.form[key]) !== this.normalizeForCompare(this.originalForm[key]))
                .map(key => ({
                    key,
                    label: labels[key] || key,
                    before: this.formatFieldValue(key, this.originalForm[key]),
                    after: this.formatFieldValue(key, this.form[key]),
                }));
        },

        sensitiveChanges() {
            return this.formChanges();
        },

        hasSensitiveChanges() {
            return this.modal.mode === 'edit' && this.sensitiveChanges().length > 0;
        },

        sensitiveWarningText() {
            return 'This change can affect ticket routing, SLA calculation, ticket code generation, reports, and case analytics. Please review it carefully before saving.';
        },

        masterDataPayload() {
            const payload = { ...this.form };

            if (['categories', 'issue-types', 'teams', 'priorities'].includes(this.activeTab)) {
                delete payload.code_num;
            }

            return payload;
        },

        openSaveConfirmation() {
            this.errors = {};
            const isEdit = this.modal.mode === 'edit';

            if (isEdit && !this.canUpdateCurrent()) {
                this.showAlert('You only have view access to Master Data.', 'error');
                return;
            }

            if (!isEdit && !this.canCreateCurrent()) {
                this.showAlert('You only have view access to Master Data.', 'error');
                return;
            }

            const actionLabel = isEdit ? 'update' : 'create';

            this.confirmation = {
                open: true,
                action: 'save',
                title: `${isEdit ? 'Confirm Update' : 'Confirm Create'} ${this.currentLabelSingle()}`,
                message: `You are about to ${actionLabel} this ${this.currentLabelSingle().toLowerCase()}. Please review the summary before continuing.`,
                warning: isEdit ? this.sensitiveWarningText() : 'New Master Data can affect ticket routing, SLA calculation, and ticket code generation.',
                changes: this.formChanges(),
                reason: '',
                errors: {},
                row: null,
                payload: this.masterDataPayload(),
            };
        },

        submit() {
            this.openSaveConfirmation();
        },

        closeConfirmation() {
            this.confirmation = {
                open: false,
                action: null,
                title: '',
                message: '',
                warning: '',
                changes: [],
                reason: '',
                errors: {},
                row: null,
                payload: null,
            };
        },

        async confirmAction() {
            const reason = String(this.confirmation.reason || '').trim();

            if (!reason) {
                this.confirmation.errors = {
                    reason: 'Reason is required.',
                };
                return;
            }

            if (this.confirmation.action === 'toggle-status') {
                await this.executeToggleStatus(reason);
                return;
            }

            await this.executeSave(reason);
        },

        async executeSave(reason) {
            const isEdit = this.modal.mode === 'edit';
            this.saving = true;
            this.errors = {};

            try {
                const url = isEdit ?
                    `/api/admin/master-data/${this.activeTab}/${this.modal.id}` :
                    `/api/admin/master-data/${this.activeTab}`;

                const payload = {
                    ...(this.confirmation.payload || this.masterDataPayload()),
                    change_reason: reason,
                };

                const result = isEdit
                    ? await apiPatch(url, payload)
                    : await apiPost(url, payload);

                this.showAlert(result.message || 'Saved successfully.', 'success');
                this.closeConfirmation();
                this.closeModal();
                this.loadRows();
            } catch (error) {
                if (error.status === 422) {
                    const mapped = this.mapErrors(error.payload?.errors || {});

                    if (mapped.change_reason) {
                        this.confirmation.errors = {
                            reason: mapped.change_reason,
                        };
                        delete mapped.change_reason;
                    }

                    this.errors = mapped;

                    if (Object.keys(mapped).length > 0) {
                        this.closeConfirmation();
                    }
                }

                if (!Object.keys(this.errors).length && !this.confirmation.errors.reason) {
                    this.showAlert(error.message || 'Failed to save data', 'error');
                }
            } finally {
                this.saving = false;
            }
        },

        toggleStatus(row) {
            if (!this.canToggleStatusCurrent()) {
                this.showAlert('You only have view access to Master Data.', 'error');
                return;
            }

            const label = row.name || row.code || `${row.team_name || ''} - ${row.priority_name || ''}`.trim();
            const nextStatus = !row.is_active;
            const nextAction = nextStatus ? 'activate' : 'deactivate';
            const warning = row.is_active
                ? 'After deactivation, this data will be hidden from new ticket forms, but existing ticket relations will remain available.'
                : 'After activation, this data will be available again for new ticket forms.';

            this.confirmation = {
                open: true,
                action: 'toggle-status',
                title: `${nextStatus ? 'Confirm Activate' : 'Confirm Deactivate'} ${this.currentLabelSingle()}`,
                message: `You are about to ${nextAction} this ${this.currentLabelSingle().toLowerCase()}${label ? `: ${label}` : ''}.`,
                warning,
                changes: [{
                    key: 'is_active',
                    label: 'Status',
                    before: row.is_active ? 'Active' : 'Inactive',
                    after: nextStatus ? 'Active' : 'Inactive',
                }],
                reason: '',
                errors: {},
                row,
                payload: null,
            };
        },

        async executeToggleStatus(reason) {
            const row = this.confirmation.row;

            if (!row) {
                this.closeConfirmation();
                return;
            }

            this.saving = true;

            try {
                const result = await apiPatch(`/api/admin/master-data/${this.activeTab}/${row.id}/status`, {
                    change_reason: reason,
                });

                this.showAlert(result.message || 'Master Data status updated successfully.', 'success');
                this.closeConfirmation();
                this.loadRows();
            } catch (error) {
                if (error.status === 422) {
                    const mapped = this.mapErrors(error.payload?.errors || {});

                    if (mapped.change_reason) {
                        this.confirmation.errors = {
                            reason: mapped.change_reason,
                        };
                    }
                }

                if (!this.confirmation.errors.reason) {
                    this.showAlert(error.message || 'Failed to update Master Data status', 'error');
                }
            } finally {
                this.saving = false;
            }
        },

        statusLabel(row) {
            return row?.is_active ? 'Active' : 'Inactive';
        },

        statusActionLabel(row) {
            return row?.is_active ? 'Deactivate' : 'Activate';
        },

        statusActionClass(row) {
            return row?.is_active
                ? 'rounded bg-amber-50 px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-100'
                : 'rounded bg-green-50 px-3 py-1.5 text-xs text-green-700 hover:bg-green-100';
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
            return formatSharedDate(value, 'en-US');
        },

        showAlert(message, type = 'success') {
            showSharedAlert(message, type);
        },
    }
}

window.masterDataPage = masterDataPage;
export default masterDataPage;
