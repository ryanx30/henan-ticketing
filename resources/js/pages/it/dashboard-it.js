/**
 * IT dashboard page controller.
 * Loads resolver KPI data, top cases, trend chart data, and queue previews for IT users.
 */

import { apiGet, apiPost } from '../../utils/apiClient';
import {
    formatCountdownClock,
    formatDateTimeCompact,
    formatDateTimeShort as formatDateTimeShortValue,
    formatNumber as formatNumberValue,
    truncateText,
} from '../../utils/formatter';
import { priorityBadgeClass as buildPriorityBadgeClass, statusBadgeClass as buildStatusBadgeClass } from '../../utils/badges';
import { showPageAlert } from '../../utils/toast';

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
        expandedResolverThreads: {},
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

        ticketUrl(ticketId) {
            return window.HenanApp.routes.ticketDetail(ticketId);
        },

        showAlert(message, type = 'success') {
            showPageAlert(message, type);
        },

        async loadDashboard() {
            this.loading = true;

            try {
                const result = await apiGet(window.HenanApp.routes.api.dashboard);
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
                const result = await apiPost(`/api/it/tickets/${ticketId}/claim`);
                this.showAlert(result.message || 'Ticket claimed successfully', 'success');
                await this.loadDashboard();
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to claim ticket', 'error');
            }
        },
        formatNumber(value) {
            return formatNumberValue(value);
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
            return truncateText(value, limit);
        },

        formatTime(value) {
            return formatDateTimeCompact(value);
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

        slaCountdown(deadline) {
            return formatCountdownClock(deadline);
        }
    }
}

window.dashboardItPage = dashboardItPage;
