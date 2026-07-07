/**
 * IT My Queue page controller.
 * Renders the authenticated resolver queue with status tabs and handles claim/status actions.
 */

import { apiGet, apiPost, apiPatch } from '../../utils/apiClient';
import { priorityBadgeClass as buildPriorityBadgeClass, statusBadgeClass as buildStatusBadgeClass, statusLabel as buildStatusLabel } from '../../utils/badges';
import { showPageAlert } from '../../utils/toast';

const MY_QUEUE_CURRENT_USER_ID = Number(
    document.getElementById('my-queue-page')?.dataset.userId || 0
);

const QUEUE_TABS = [
    {
        key: 'new',
        label: 'New Ticket',
        description: 'Unclaimed IT tickets that are ready to be claimed.',
    },
    {
        key: 'ongoing',
        label: 'Ongoing',
        description: 'Tickets currently assigned to you and being handled.',
    },
    {
        key: 'waiting',
        label: 'Waiting Info',
        description: 'Tickets assigned to you that are waiting for additional information.',
    },
    {
        key: 'resolved',
        label: 'Resolved/Closed',
        description: 'Your latest resolved or closed tickets.',
    },
];

const QUEUE_TAB_COLLECTIONS = {
    new: 'newTickets',
    ongoing: 'ongoingTickets',
    waiting: 'waitingTickets',
    resolved: 'resolvedTickets',
};

const STATUS_TRANSITIONS = {
    new: ['in_progress', 'waiting_info', 'resolved', 'closed'],
    in_progress: ['waiting_info', 'resolved', 'closed'],
    waiting_info: ['in_progress', 'resolved', 'closed'],
    resolved: ['in_progress', 'waiting_info', 'closed'],
    closed: [],
};

function normalizeStatus(status = '') {
    return String(status || '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_')
        .replace(/^ongoing$/, 'in_progress');
}

function myQueuePage() {
    return {
        loading: false,
        timer: null,
        currentUserId: MY_QUEUE_CURRENT_USER_ID,
        activeTab: 'ongoing',
        newTickets: [],
        ongoingTickets: [],
        waitingTickets: [],
        resolvedTickets: [],

        init() {
            this.loadQueue();

            this.timer = setInterval(() => {
                this.newTickets = [...this.newTickets];
                this.ongoingTickets = [...this.ongoingTickets];
                this.waitingTickets = [...this.waitingTickets];
                this.resolvedTickets = [...this.resolvedTickets];
            }, 1000);
        },

        destroy() {
            if (this.timer) clearInterval(this.timer);
        },

        ticketUrl(ticketId) {
            return `/tickets/${ticketId}`;
        },

        showAlert(message, type = 'success') {
            showPageAlert(message, type);
        },

        async loadQueue() {
            this.loading = true;

            try {
                const result = await apiGet('/api/it/my-queue');
                const data = result.data || {};

                this.newTickets = data.new_tickets || [];
                this.ongoingTickets = data.ongoing_tickets || [];
                this.waitingTickets = data.waiting_tickets || [];
                this.resolvedTickets = data.resolved_tickets || [];
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load my queue', 'error');
            } finally {
                this.loading = false;
            }
        },

        async claimTicket(ticketId) {
            try {
                const result = await apiPost(`/api/it/tickets/${ticketId}/claim`);
                this.showAlert(result.message || 'Ticket claimed successfully', 'success');
                await this.loadQueue();
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to claim ticket', 'error');
                await this.loadQueue();
            }
        },

        async updateStatus(ticketId, status) {
            try {
                const result = await apiPatch(`/api/it/tickets/${ticketId}/status`, { status });
                this.showAlert(result.message || 'Status updated successfully', 'success');
                await this.loadQueue();
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to update status', 'error');
                await this.loadQueue();
            }
        },

        handleStatusChange(ticket, event) {
            const nextStatus = event.target.value;

            if (!nextStatus || nextStatus === this.statusValue(ticket)) {
                event.target.value = this.statusValue(ticket);
                return;
            }

            this.updateStatus(ticket.id, nextStatus);
        },

        tabs() {
            return QUEUE_TABS.map((tab) => ({
                ...tab,
                count: this.ticketsForTab(tab.key).length,
            }));
        },

        setActiveTab(tabKey) {
            if (!QUEUE_TAB_COLLECTIONS[tabKey]) return;

            this.activeTab = tabKey;
        },

        ticketsForTab(tabKey) {
            return this[QUEUE_TAB_COLLECTIONS[tabKey]] || [];
        },

        activeTickets() {
            return this.ticketsForTab(this.activeTab);
        },

        activeTabData() {
            return this.tabs().find((tab) => tab.key === this.activeTab) || this.tabs()[0];
        },

        tabButtonClass(tabKey) {
            return this.activeTab === tabKey
                ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50';
        },

        emptyMessage() {
            const messages = {
                new: 'No new unclaimed tickets.',
                ongoing: 'No ongoing tickets.',
                waiting: 'No waiting info tickets.',
                resolved: 'No resolved or closed tickets.',
            };

            return messages[this.activeTab] || 'No tickets.';
        },

        canClaimTicket(ticket) {
            return this.statusValue(ticket) === 'new' && !Number(ticket?.holder_id || 0);
        },

        canUpdateStatus(ticket) {
            return Number(ticket?.holder_id || 0) === Number(this.currentUserId)
                && this.statusOptionsFor(ticket).length > 0;
        },

        statusOptionsFor(ticket) {
            return (STATUS_TRANSITIONS[this.statusValue(ticket)] || []).map((status) => ({
                value: status,
                label: this.statusLabel(status),
            }));
        },

        statusValue(ticket) {
            return normalizeStatus(ticket?.status || '');
        },

        ticketLabel(ticket) {
            return window.HenanApp?.ticketLabel(ticket) ?? '-';
        },

        ucfirst(value) {
            if (!value) return '-';

            value = String(value);
            return value.charAt(0).toUpperCase() + value.slice(1);
        },

        statusLabel(status) {
            return buildStatusLabel(status);
        },

        priorityBadgeClass(priority) {
            return buildPriorityBadgeClass(priority);
        },

        statusBadgeClass(status) {
            return buildStatusBadgeClass(status);
        },

        slaCountdown(deadline) {
            if (!deadline) return '-';

            const end = new Date(deadline).getTime();
            const now = Date.now();
            const diff = end - now;

            if (diff <= 0) return 'OVERDUE';

            const totalSeconds = Math.floor(diff / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }
    };
}

window.myQueuePage = myQueuePage;
export default myQueuePage;
