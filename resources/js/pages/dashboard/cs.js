/**
 * CS dashboard page controller.
 * Loads operational KPI data, today-focus items, active tickets, and resolver inbox updates.
 */

import { apiGet } from '../../utils/apiClient';
import {
    formatCountdownClock,
    formatDateTime as formatDateTimeValue,
    formatDateTimeShort as formatDateTimeShortValue,
    formatNumber as formatNumberValue,
    formatTimeShort,
    truncateText,
} from '../../utils/formatter';
import { priorityBadgeClass as buildPriorityBadgeClass, statusBadgeClass as buildStatusBadgeClass } from '../../utils/badges';
import { showPageAlert } from '../../utils/toast';

window.dashboardCsPage = function dashboardCsPage() {
    return {
        loading: false,
        timer: null,

        kpi: {
            total: {
                value: 0,
                prev_month: 0,
                prev_year: 0,
                mom: {},
                yoy: {}
            },
            new: {
                value: 0,
                prev_month: 0,
                prev_year: 0,
                mom: {},
                yoy: {}
            },
            in_progress: {
                value: 0,
                prev_month: 0,
                prev_year: 0,
                mom: {},
                yoy: {}
            },
            resolved: {
                value: 0,
                prev_month: 0,
                prev_year: 0,
                mom: {},
                yoy: {}
            },
            sla_risk: {
                value: 0,
                prev_month: 0,
                prev_year: 0,
                mom: {},
                yoy: {}
            },
        },

        focus: {
            sla: 0,
            due_today: 0,
            pending_user: 0,
            reopened: 0,
        },

        myTickets: [],
        activeTickets: [],
        resolverInbox: [],
        expandedResolverThreads: {},
        currentRole: null,
        currentUserId: Number(document.getElementById('dashboard-cs-page')?.dataset.currentUserId || 0),

        filters: {
            priority: 'all',
            status: 'all',
            sla: 'all',
            sort: 'latest',
            inbox_period: 'all',
        },

        init() {
            const params = new URLSearchParams(window.location.search);
            this.filters.priority = params.get('priority') || 'all';
            this.filters.status = params.get('status') || 'all';
            this.filters.sla = params.get('sla') || 'all';
            this.filters.sort = params.get('sort') || 'latest';
            this.filters.inbox_period = params.get('inbox_period') || 'all';

            this.loadDashboard();

            this.timer = setInterval(() => {
                this.myTickets = [...this.myTickets];
                this.activeTickets = [...this.activeTickets];
            }, 1000);
        },

        destroy() {
            if (this.timer) clearInterval(this.timer);
        },

        showAlert(message, type = 'success') {
            showPageAlert(message, type);
        },

        buildQuery() {
            const params = new URLSearchParams();
            params.set('priority', this.filters.priority);
            params.set('status', this.filters.status);
            params.set('sla', this.filters.sla);
            params.set('sort', this.filters.sort);
            params.set('inbox_period', this.filters.inbox_period);
            return params;
        },

        applyFilters() {
            const params = this.buildQuery();
            window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
            this.loadDashboard();
        },

        resetTicketFilters() {
            this.filters.priority = 'all';
            this.filters.status = 'all';
            this.filters.sla = 'all';
            this.filters.sort = 'latest';
            this.applyFilters();
        },

        focusLink(type) {
            switch (type) {
                case 'sla_risk':
                    return '/tickets?focus=sla_risk';
                case 'due_today':
                    return '/tickets?focus=due_today';
                case 'pending_user':
                    return '/tickets?status=waiting_info';
                case 'reopened':
                    return '/tickets?focus=reopened';
                default:
                    return '/tickets';
            }
        },

        async loadDashboard() {
            this.loading = true;

            try {
                const params = this.buildQuery();
                const result = await apiGet(`${window.HenanApp.routes.api.dashboard}?${params.toString()}`);
                const data = result.data || {};

                this.currentRole = data.role || this.currentRole;
                this.kpi = data.kpi || this.kpi;
                this.focus = data.focus || this.focus;
                this.myTickets = data.my_tickets || [];
                this.activeTickets = data.active_tickets || [];
                this.resolverInbox = data.resolver_inbox || [];
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load dashboard', 'error');
            } finally {
                this.loading = false;
            }
        },

        isSupervisor() {
            return String(this.currentRole || '').trim().toLowerCase() === 'supervisor';
        },

        formatNumber(value) {
            return formatNumberValue(value);
        },

        trendText(item) {
            if (!item) return '-';
            if (item.direction === 'new') return item.label || 'New';

            const arrow = item.direction === 'up' ? '▲' : (item.direction === 'down' ? '▼' : '•');
            return `${item.label ?? '-'} ${arrow}`;
        },
        ticketLabel(ticket) {
            return window.HenanApp?.ticketLabel(ticket) ?? '-';
        },

        ticketUrl(ticketId) {
            return window.HenanApp?.routes?.ticketDetail
                ? window.HenanApp.routes.ticketDetail(ticketId)
                : `/tickets/${ticketId}`;
        },

        openTicket(ticket) {
            if (!ticket?.id) return;
            window.location.href = this.ticketUrl(ticket.id);
        },

        formatDateTime(value) {
            return formatDateTimeValue(value);
        },

        ucfirst(value) {
            if (!value) return '-';
            value = String(value);
            return value.charAt(0).toUpperCase() + value.slice(1);
        },

        truncate(value, limit = 70) {
            return truncateText(value, limit);
        },

        formatTime(value) {
            return formatTimeShort(value);
        },

        normalizeStatus(status) {
            return window.HenanApp?.normalizeStatus
                ? window.HenanApp.normalizeStatus(status)
                : String(status || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
        },

        normalizePriority(priority) {
            return window.HenanApp?.normalizePriority
                ? window.HenanApp.normalizePriority(priority)
                : String(priority || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
        },

        statusLabel(status) {
            return window.HenanApp?.statusLabel
                ? window.HenanApp.statusLabel(status)
                : (status || '-');
        },

        priorityLabel(priority) {
            return window.HenanApp?.priorityLabel
                ? window.HenanApp.priorityLabel(priority)
                : this.ucfirst(priority);
        },

        statusBadgeClass(status) {
            return buildStatusBadgeClass(status);
        },

        priorityBadgeClass(priority) {
            return buildPriorityBadgeClass(priority);
        },


        resolverInboxThreads(limit = 5) {
            const threads = new Map();

            for (const message of this.resolverInbox || []) {
                const key = message?.ticket?.id
                    ? `ticket-${message.ticket.id}`
                    : `message-${message?.id || Math.random()}`;

                if (!threads.has(key)) {
                    threads.set(key, {
                        key,
                        ticket: message?.ticket || null,
                        latestMessage: message,
                        messages: [],
                    });
                }

                const thread = threads.get(key);
                thread.messages.push(message);

                if (this.messageTimestamp(message) > this.messageTimestamp(thread.latestMessage)) {
                    thread.latestMessage = message;
                    thread.ticket = message?.ticket || thread.ticket;
                }
            }

            return Array.from(threads.values())
                .map((thread) => ({
                    ...thread,
                    messages: thread.messages.sort((a, b) => this.messageTimestamp(b) - this.messageTimestamp(a)),
                }))
                .sort((a, b) => this.messageTimestamp(b.latestMessage) - this.messageTimestamp(a.latestMessage))
                .slice(0, limit);
        },

        messageTimestamp(message) {
            const value = message?.created_at || message?.updated_at || null;
            const timestamp = value ? Date.parse(value) : 0;
            return Number.isFinite(timestamp) ? timestamp : 0;
        },

        resolverThreadReplies(thread) {
            const latestId = Number(thread?.latestMessage?.id || 0);

            return (thread?.messages || [])
                .filter((message) => Number(message?.id || 0) !== latestId)
                .sort((a, b) => this.messageTimestamp(b) - this.messageTimestamp(a));
        },

        threadReplyCount(thread) {
            return this.resolverThreadReplies(thread).length;
        },

        threadUnreadCount(thread) {
            return (thread?.messages || []).filter((message) => this.isUnreadMessage(message)).length;
        },

        toggleResolverThread(key) {
            this.expandedResolverThreads = {
                ...this.expandedResolverThreads,
                [key]: !this.expandedResolverThreads[key],
            };
        },

        isResolverThreadExpanded(key) {
            return Boolean(this.expandedResolverThreads[key]);
        },

        messageUrl(message) {
            const messageId = message?.id || message;

            return window.HenanApp?.routes?.resolverInboxDetail
                ? window.HenanApp.routes.resolverInboxDetail(messageId)
                : `/resolver-inbox/${messageId}`;
        },

        openResolverMessage(message) {
            if (!message?.id) return;
            window.location.href = this.messageUrl(message);
        },

        isUnreadMessage(message) {
            return window.HenanApp?.isUnreadForUser
                ? window.HenanApp.isUnreadForUser(message, this.currentUserId)
                : Boolean(message && !message.is_read && Number(message.to_user_id) === Number(this.currentUserId));
        },

        messageTitle(message) {
            return window.HenanApp?.messageTitle
                ? window.HenanApp.messageTitle(message)
                : (message?.ticket?.title || message?.subject || 'Resolver update');
        },

        messagePreview(message, limit = 100) {
            return window.HenanApp?.messagePreview
                ? window.HenanApp.messagePreview(message, limit)
                : this.truncate(message?.body || message?.subject || '-', limit);
        },

        participantsLabel(message) {
            return window.HenanApp?.participantsLabel
                ? window.HenanApp.participantsLabel(message, this.currentUserId)
                : `${message?.sender?.name || 'Unknown sender'} → ${message?.recipient?.name || 'Unknown recipient'}`;
        },

        formatDateTimeShort(value) {
            return formatDateTimeShortValue(value);
        },

        slaCountdown(deadline) {
            return formatCountdownClock(deadline);
        },
    }
}
