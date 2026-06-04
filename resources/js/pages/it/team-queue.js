/**
 * IT Team Queue page controller.
 * Renders available team tickets and claim/update actions for resolver workflow.
 */

import { apiGet, apiPost, apiPatch } from '../../utils/apiClient';
import { priorityBadgeClass as buildPriorityBadgeClass, statusBadgeClass as buildStatusBadgeClass, statusLabel as buildStatusLabel } from '../../utils/badges';
import { showPageAlert } from '../../utils/toast';

const TEAM_QUEUE_CURRENT_USER_ID = Number(
    document.getElementById('team-queue-page')?.dataset.userId || 0
);

function teamQueuePage() {
    return {
        loading: false,
        timer: null,
        currentUserId: TEAM_QUEUE_CURRENT_USER_ID,
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
                const result = await apiGet('/api/it/team-queue');
                const data = result.data || {};
                this.newTickets = data.new_tickets || [];
                this.ongoingTickets = data.ongoing_tickets || [];
                this.waitingTickets = data.waiting_tickets || [];
                this.resolvedTickets = data.resolved_tickets || [];
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load team queue', 'error');
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
    }
}

window.teamQueuePage = teamQueuePage;
export default teamQueuePage;
