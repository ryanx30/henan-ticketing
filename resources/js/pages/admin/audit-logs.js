/**
 * Admin audit log page controller.
 * Loads grouped audit log entries, filters, summary cards, pagination, and export actions.
 */

import { apiGet, buildQueryString } from '../../utils/apiClient';
import { formatDateTime } from '../../utils/formatter';
import { showPageAlert } from '../../utils/toast';

function auditLogsPage() {
    return {
        loading: false,
        exporting: false,
        exportOpen: false,
        detailOpen: false,
        selectedLog: null,
        rows: [],
        summary: {},
        tabs: [
            {
                value: 'ticket',
                label: 'Ticket',
                description: 'Ticket creation, claim, status, routing, and lifecycle activity.',
            },
            {
                value: 'user',
                label: 'User',
                description: 'User account creation, update, activation, and deactivation activity.',
            },
            {
                value: 'resolver_inbox',
                label: 'Resolver Inbox',
                description: 'Resolver conversation messages, reads, replies, and attachments.',
            },
            {
                value: 'master_data',
                label: 'Master Data',
                description: 'Category, issue type, team, priority, and SLA rule changes.',
            },
        ],
        options: {
            actions: [],
            master_data_entities: [],
            tabs: [],
        },
        filters: {
            q: '',
            action: 'all',
            entity_group: 'ticket',
            entity_type: 'all',
            date_range: '30d',
            date_from: '',
            date_to: '',
            per_page: '50',
            page: 1,
        },
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0,
            from: 0,
            to: 0,
        },

        init() {
            this.fetchLogs();
        },

        async fetchLogs() {
            this.loading = true;

            try {
                const query = buildQueryString(this.filters);
                const json = await apiGet(`/api/admin/audit-logs?${query}`);

                this.rows = json.data?.rows || [];
                this.meta = json.data?.meta || this.meta;
                this.summary = json.data?.summary || {};
                this.options = json.data?.options || this.options;

                if (this.options.tabs?.length) {
                    this.tabs = this.options.tabs;
                }
            } catch (error) {
                this.showAlert(error.message || 'Failed to load audit logs.', 'error');
            } finally {
                this.loading = false;
            }
        },

        setEntityGroup(value) {
            if (this.filters.entity_group === value) {
                return;
            }

            this.filters.entity_group = value;
            this.filters.action = 'all';
            this.filters.entity_type = 'all';
            this.filters.page = 1;
            this.exportOpen = false;
            this.fetchLogs();
        },

        applyFilters() {
            this.filters.page = 1;
            this.fetchLogs();
        },

        async exportLogs(format = 'csv') {
            this.exporting = true;

            try {
                const query = buildQueryString({
                    ...this.filters,
                    page: null,
                    per_page: null,
                    format,
                });

                await window.HenanExportQueue.queueExport(`/api/admin/audit-logs/export?${query}`, {
                    onQueued: (_payload, message) => this.showAlert(message || 'Audit log export has been queued.', 'success'),
                    onReady: () => this.showAlert('Export is ready. Downloading file...', 'success'),
                    onError: (error) => this.showAlert(error.message || 'Failed to export audit logs.', 'error'),
                });
            } catch (error) {
                // Error is already shown by the shared helper callback.
            } finally {
                this.exporting = false;
            }
        },

        resetFilters() {
            const entityGroup = this.filters.entity_group;
            this.exportOpen = false;

            this.filters = {
                q: '',
                action: 'all',
                entity_group: entityGroup,
                entity_type: 'all',
                date_range: '30d',
                date_from: '',
                date_to: '',
                per_page: '50',
                page: 1,
            };

            this.fetchLogs();
        },

        goToPage(page) {
            if (!page || page < 1 || page > this.meta.last_page || page === this.meta.current_page) {
                return;
            }

            this.filters.page = page;
            this.fetchLogs();
        },

        visiblePages() {
            const current = Number(this.meta.current_page || 1);
            const last = Number(this.meta.last_page || 1);
            const pages = [];

            if (last <= 7) {
                for (let i = 1; i <= last; i++) {
                    pages.push(i);
                }

                return pages;
            }

            pages.push(1);

            if (current > 4) {
                pages.push('...');
            }

            const start = Math.max(2, current - 1);
            const end = Math.min(last - 1, current + 1);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            if (current < last - 3) {
                pages.push('...');
            }

            pages.push(last);

            return pages;
        },

        activeTab() {
            return this.tabs.find(tab => tab.value === this.filters.entity_group) || this.tabs[0] || {};
        },

        activeTabLabel() {
            return this.activeTab().label || 'Audit';
        },

        activeTabDescription() {
            return this.activeTab().description || '';
        },

        isMasterDataTab() {
            return this.filters.entity_group === 'master_data';
        },


        searchPlaceholder() {
            return this.isMasterDataTab()
                ? 'Search actor, action, IP, reason, or description...'
                : 'Search actor, action, ticket, IP, or description...';
        },

        masterDataEntities() {
            return this.options.master_data_entities || [];
        },

        tabButtonClass(value) {
            return this.filters.entity_group === value
                ? 'bg-[#051823] text-white shadow-sm'
                : 'bg-slate-50 text-slate-600 hover:bg-slate-100';
        },

        openDetail(row) {
            this.selectedLog = row;
            this.detailOpen = true;
        },

        prettyJson(value) {
            if (!value) {
                return '-';
            }

            if (typeof value === 'string') {
                try {
                    const parsed = JSON.parse(value);
                    return JSON.stringify(parsed, null, 2);
                } catch (error) {
                    return value;
                }
            }

            if (typeof value === 'object' && Object.keys(value).length === 0) {
                return '-';
            }

            return JSON.stringify(value, null, 2);
        },

        formatDate(value) {
            return formatDateTime(value, 'en-GB');
        },

        actionBadgeClass(action) {
            const classes = {
                created: 'bg-green-100 text-green-700',
                created_status: 'bg-green-100 text-green-700',
                updated: 'bg-blue-100 text-blue-700',
                deleted: 'bg-red-100 text-red-700',
                deactivated: 'bg-red-100 text-red-700',
                activated: 'bg-green-100 text-green-700',
                reactivated: 'bg-green-100 text-green-700',
                status_changed: 'bg-yellow-100 text-yellow-800',
                claimed: 'bg-purple-100 text-purple-700',
                holder_transferred: 'bg-indigo-100 text-indigo-700',
                sent: 'bg-cyan-100 text-cyan-700',
                read: 'bg-slate-200 text-slate-700',
            };

            return classes[action] || 'bg-slate-100 text-slate-700';
        },

        showAlert(message, type = 'success') {
            showPageAlert(message, type);
        },
    };
}

window.auditLogsPage = auditLogsPage;
export default auditLogsPage;
