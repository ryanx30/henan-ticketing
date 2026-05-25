function myQueuePage() {
    return {
        loading: false,
        timer: null,
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

        csrf() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        ticketUrl(ticketId) {
            return `/tickets/${ticketId}`;
        },

        showAlert(message, type = 'success') {
            const el = document.getElementById('page-alert');
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

        async loadQueue() {
            this.loading = true;

            try {
                const response = await fetch('/api/it/my-queue', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load my queue');
                }

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
                const response = await fetch(`/api/it/tickets/${ticketId}/claim`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to claim ticket');
                }

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
                const response = await fetch(`/api/it/tickets/${ticketId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ status })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to update status');
                }

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
            const map = {
                new: 'New',
                in_progress: 'Ongoing',
                waiting_info: 'Waiting Info',
                resolved: 'Resolved',
                closed: 'Closed',
            };
            return map[status] || status || '-';
        },

        priorityBadgeClass(priority) {
            return window.HenanApp?.priorityBadgeClass
                ? window.HenanApp.priorityBadgeClass(priority)
                : 'badge-priority-default';
        },

        statusBadgeClass(status) {
            return window.HenanApp?.statusBadgeClass
                ? window.HenanApp.statusBadgeClass(status)
                : 'badge-status-default';
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

window.myQueuePage = myQueuePage;
export default myQueuePage;
