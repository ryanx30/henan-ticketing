function dashboardItPage() {
    return {
        loading: false,
        timer: null,
        chart: null,

        kpi: {
            total: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
            new: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
            in_progress: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
            resolved: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
            sla_risk: { value: 0, prev_month: 0, prev_year: 0, mom: {}, yoy: {} },
        },

        itMyQueue: [],
        itTeamNew: [],
        resolverInbox: [],
        currentUserId: Number(document.getElementById('dashboard-it-page')?.dataset.currentUserId || 0),
        topCases: [],
        trend: {
            labels: [],
            it: [],
            finance: [],
            compliance: [],
        },

        init() {
            this.loadDashboard();

            this.timer = setInterval(() => {
                this.itMyQueue = [...this.itMyQueue];
                this.itTeamNew = [...this.itTeamNew];
            }, 1000);

            window.addEventListener('resize', () => {
                if (this.chart) {
                    this.chart.resize();
                }
            });
        },

        destroy() {
            if (this.timer) clearInterval(this.timer);
            if (this.chart) this.chart.destroy();
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        ticketUrl(ticketId) {
            return window.HenanApp.routes.ticketDetail(ticketId);
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

        async loadDashboard() {
            this.loading = true;

            try {
                const response = await fetch(window.HenanApp.routes.api.dashboard, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load dashboard');
                }

                const data = result.data || {};

                this.kpi = data.kpi || this.kpi;
                this.itMyQueue = data.it_my_queue || [];
                this.itTeamNew = data.it_team_new || [];
                this.resolverInbox = data.resolver_inbox || [];
                this.topCases = data.top_cases || [];
                this.trend = data.trend || this.trend;
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load dashboard', 'error');
            } finally {
                this.loading = false;

                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        this.renderChart();
                    });
                });
            }
        },

        renderChart(retry = 0) {
            const el = document.getElementById('trendChart');
            if (!el) return;

            if (typeof Chart === 'undefined') {
                if (retry < 10) {
                    setTimeout(() => this.renderChart(retry + 1), 200);
                }
                return;
            }

            const labels = this.trend.labels || [];
            if (!labels.length) {
                if (this.chart) {
                    this.chart.destroy();
                    this.chart = null;
                }
                return;
            }

            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }

            this.chart = new Chart(el, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'IT',
                            data: this.trend.it || [],
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2,
                            borderColor: '#0f172a',
                            backgroundColor: 'rgba(15, 23, 42, 0.12)',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#0f172a',
                            pointBorderColor: '#0f172a',
                            pointHoverBackgroundColor: '#0f172a',
                            pointHoverBorderColor: '#0f172a',
                        },
                        {
                            label: 'Finance',
                            data: this.trend.finance || [],
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.12)',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#f59e0b',
                            pointHoverBackgroundColor: '#f59e0b',
                            pointHoverBorderColor: '#f59e0b',
                        },
                        {
                            label: 'Compliance',
                            data: this.trend.compliance || [],
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.12)',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#16a34a',
                            pointBorderColor: '#16a34a',
                            pointHoverBackgroundColor: '#16a34a',
                            pointHoverBorderColor: '#16a34a',
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
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
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to claim ticket');
                }

                this.showAlert(result.message || 'Ticket claimed successfully', 'success');
                await this.loadDashboard();
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to claim ticket', 'error');
            }
        },

        formatNumber(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value || 0));
        },

        trendText(item) {
            if (!item) return '-';
            const arrow = item.direction === 'up' ? '▲' : (item.direction === 'down' ? '▼' : '•');
            return `${item.label ?? '-'} ${arrow}`;
        },
        ticketLabel(ticket) {
            return window.HenanApp?.ticketLabel(ticket) ?? '-';
        },


        ucfirst(value) {
            if (!value) return '-';
            value = String(value);
            return value.charAt(0).toUpperCase() + value.slice(1);
        },

        truncate(value, limit = 80) {
            value = String(value || '');
            if (value.length <= limit) return value;
            return value.substring(0, limit) + '...';
        },

        formatTime(value) {
            if (!value) return '-';

            return new Date(value).toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        messageRecipientLabel(message) {
            return message?.recipient?.name || message?.recipient?.email || 'Recipient';
        },

        messageSenderLabel(message) {
            return message?.sender?.name || message?.sender?.email || 'System';
        },

        messageSnippet(message) {
            return this.truncate(message?.subject || message?.body || '-', 100);
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
            return window.HenanApp?.formatDateTimeShort
                ? window.HenanApp.formatDateTimeShort(value)
                : this.formatTime(value);
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
            return window.HenanApp?.statusBadgeClass
                ? window.HenanApp.statusBadgeClass(status)
                : 'badge-status-default';
        },

        priorityBadgeClass(priority) {
            return window.HenanApp?.priorityBadgeClass
                ? window.HenanApp.priorityBadgeClass(priority)
                : 'badge-priority-default';
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

window.dashboardItPage = dashboardItPage;
