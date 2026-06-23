/**
 * Ticket listing page controller.
 * Manages filters, summary cards, API loading, pagination, row rendering, and delete actions.
 */

import { apiGet, buildQueryString } from '../../utils/apiClient';
import { paginationItems as buildPaginationItems } from '../../utils/pagination';
import { formatDateTime, formatHumanDate, titleCase, toYmd } from '../../utils/formatter';
import { priorityBadgeClass, priorityLabel as buildPriorityLabel, statusBadgeClass, statusLabel as buildStatusLabel, ticketLabel as buildTicketLabel } from '../../utils/badges';
import { showAlert as showPageAlert } from '../../utils/toast';

// Ticket index Alpine component. Keeps interactive behavior out of Blade.
function ticketsIndexPage(config = {}) {
    return {
        loading: false,
        tickets: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        },
        datePicker: null,
        priorityOptions: [],
        filters: {
            q: '',
            status: 'all',
            priority: 'all',
            date_from: '',
            date_to: '',
            focus: '',
            mine: '',
            sort_by: 'created_at',
            sort_dir: 'desc',
            per_page: '10',
            page: 1,
            ...(config.initialFilters || {}),
        },

        async init() {
            this.hydrateFiltersFromQuery();
            await this.loadPriorityOptions();
            this.initDatePicker();
            await this.loadTickets();
        },

        hydrateFiltersFromQuery() {
            const params = new URLSearchParams(window.location.search);

            Object.keys(this.filters).forEach((key) => {
                if (params.has(key)) this.filters[key] = params.get(key);
            });

            this.filters.page = Number(this.filters.page || 1);
        },

        slugify(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        },

        async loadPriorityOptions() {
            try {
                const result = await apiGet('/api/ticket-form/options');
                this.priorityOptions = result.data?.priorities || [];
            } catch (error) {
                console.error(error);
                this.priorityOptions = [
                    { id: 'critical', code: 'critical', name: 'Critical' },
                    { id: 'high', code: 'high', name: 'High' },
                    { id: 'medium', code: 'medium', name: 'Medium' },
                    { id: 'low', code: 'low', name: 'Low' },
                ];
            }
        },

        showAlert(message, type = 'success') {
            showPageAlert(message, type);
        },

        buildQuery() {
            const params = new URLSearchParams(buildQueryString({
                q: this.filters.q,
                status: this.filters.status,
                priority: this.filters.priority,
                date_from: this.filters.date_from,
                date_to: this.filters.date_to,
                focus: this.filters.focus,
                mine: this.filters.mine,
                sort_by: this.filters.sort_by,
                sort_dir: this.filters.sort_dir,
                per_page: this.filters.per_page,
            }));

            params.set('page', Number(this.filters.page || 1));

            return params;
        },

        async loadTickets() {
            this.loading = true;

            try {
                const params = this.buildQuery();
                const result = await apiGet(`/api/tickets?${params.toString()}`);

                this.tickets = result.data || [];
                this.meta = result.meta || this.meta;
            } catch (error) {
                console.error(error);
                this.tickets = [];
                this.showAlert(error.message || 'Failed to load tickets', 'error');
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.filters.page = 1;
            this.syncUrl();
            this.loadTickets();
        },

        resetFilters() {
            const focus = this.filters.focus || '';

            this.filters = {
                q: '',
                status: 'all',
                priority: 'all',
                date_from: '',
                date_to: '',
                focus,
                mine: '',
                sort_by: 'created_at',
                sort_dir: 'desc',
                per_page: '10',
                page: 1,
            };

            if (this.datePicker) {
                this.datePicker.clearSelection();
            }

            this.syncUrl();
            this.loadTickets();
        },

        clearFocus() {
            this.filters.focus = '';
            this.filters.page = 1;
            this.syncUrl();
            this.loadTickets();
        },

        goToPage(page) {
            page = Number(page || 1);

            if (page < 1 || page > Number(this.meta.last_page || 1)) return;

            this.filters.page = page;
            this.syncUrl();
            this.loadTickets();
        },

        syncUrl() {
            const params = this.buildQuery();
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
        },

        paginationItems() {
            return buildPaginationItems(this.meta);
        },

        openTicket(ticketId) {
            if (!ticketId) return;
            window.location.href = `/tickets/${ticketId}`;
        },

        sort(column) {
            if (this.filters.sort_by === column) {
                this.filters.sort_dir = this.filters.sort_dir === 'asc' ? 'desc' : 'asc';
            } else {
                this.filters.sort_by = column;
                this.filters.sort_dir = column === 'created_at' ? 'desc' : 'asc';
            }

            this.applyFilters();
        },

        sortIcon(column) {
            if (this.filters.sort_by !== column) {
                return `<span style="display:inline-flex;flex-direction:column;margin-left:4px;opacity:0.35;line-height:1;">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 4l-8 8h16z" /></svg>
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 20l8-8H4z" /></svg>
                </span>`;
            }

            if (this.filters.sort_dir === 'asc') {
                return `<span style="display:inline-flex;flex-direction:column;margin-left:4px;line-height:1;">
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#2f88d8" stroke-width="3"><path d="M12 4l-8 8h16z" /></svg>
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="opacity:0.25"><path d="M12 20l8-8H4z" /></svg>
                </span>`;
            }

            return `<span style="display:inline-flex;flex-direction:column;margin-left:4px;line-height:1;">
                <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="opacity:0.25"><path d="M12 4l-8 8h16z" /></svg>
                <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#2f88d8" stroke-width="3"><path d="M12 20l8-8H4z" /></svg>
            </span>`;
        },

        initDatePicker() {
            const trigger = document.getElementById('dateRangeTrigger');
            if (!trigger || typeof window.Litepicker === 'undefined') return;

            this.datePicker = new window.Litepicker({
                element: trigger,
                singleMode: false,
                numberOfMonths: 2,
                numberOfColumns: 2,
                format: 'YYYY-MM-DD',
                autoApply: false,
                showTooltip: true,
                tooltipText: { one: 'day', other: 'days' },
                buttonText: { apply: 'Apply', cancel: 'Cancel', reset: 'Reset' },
                setup: (picker) => {
                    picker.on('render', (ui) => this.addDateShortcuts(ui, picker));
                    picker.on('selected', (date1, date2) => {
                        if (!date1 || !date2) return;
                        this.filters.date_from = this.toYmd(new Date(date1.dateInstance));
                        this.filters.date_to = this.toYmd(new Date(date2.dateInstance));
                    });
                },
            });
        },

        addDateShortcuts(ui, picker) {
            if (ui.querySelector('.lp-shortcuts')) return;

            const shortcuts = document.createElement('div');
            shortcuts.className = 'lp-shortcuts';

            const today = new Date();
            const items = [
                { label: 'Today', from: today, to: today },
                { label: 'Last 7 Days', from: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6), to: today },
                { label: 'Last 30 Days', from: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29), to: today },
                { label: 'Last 1 Year', from: new Date(today.getFullYear() - 1, today.getMonth(), today.getDate()), to: today },
            ];

            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = item.label;
                btn.className = 'lp-shortcut-btn';
                btn.addEventListener('click', () => {
                    picker.setDateRange(item.from, item.to);
                    this.filters.date_from = this.toYmd(item.from);
                    this.filters.date_to = this.toYmd(item.to);
                    picker.hide();
                    this.applyFilters();
                });
                shortcuts.appendChild(btn);
            });

            ui.appendChild(shortcuts);
        },

        openDatePicker() {
            if (this.datePicker) {
                this.datePicker.show();
            }
        },

        dateLabel() {
            if (this.filters.date_from && this.filters.date_to) {
                return `${this.formatHumanDate(this.filters.date_from)} → ${this.formatHumanDate(this.filters.date_to)}`;
            }

            return 'dd/mm/yyyy → dd/mm/yyyy';
        },

        focusLabel(value) {
            const labels = {
                sla_risk: 'SLA Risk Tickets',
                due_today: 'Tickets Due Today',
                reopened: 'Reopened Tickets',
                pending_user: 'Pending User Tickets',
            };

            return labels[value] || value || '-';
        },

        formatHumanDate(value) {
            return formatHumanDate(value);
        },

        toYmd(date) {
            return toYmd(date);
        },

        ticketLabel(ticket) {
            return ticket.ticket_label || ticket.ticket_code || buildTicketLabel(ticket);
        },

        categoryLabel(ticket) {
            return ticket.category_label || titleCase(ticket.category) || '-';
        },

        teamLabel(ticket) {
            return ticket.team_label || ticket.team || '-';
        },

        priorityLabel(ticket) {
            return ticket.priority_label || buildPriorityLabel(ticket.priority);
        },

        statusLabel(status) {
            return buildStatusLabel(status);
        },

        createdLabel(ticket) {
            return formatDateTime(ticket.created_at);
        },

        titleCase(value) {
            return titleCase(value);
        },

        priorityBadgeClass(priority) {
            return priorityBadgeClass(priority);
        },

        statusBadgeClass(status) {
            return statusBadgeClass(status);
        },
    };
}

window.ticketsIndexPage = ticketsIndexPage;
window.HenanTicketIndexPage = ticketsIndexPage;

export default ticketsIndexPage;
