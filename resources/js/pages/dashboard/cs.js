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
                const response = await fetch(`${window.HenanApp.routes.api.dashboard}?${params.toString()}`, {
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
            return window.HenanApp?.formatDateTime
                ? window.HenanApp.formatDateTime(value)
                : (value ? new Date(value).toLocaleString('id-ID') : '-');
        },

        ucfirst(value) {
            if (!value) return '-';
            value = String(value);
            return value.charAt(0).toUpperCase() + value.slice(1);
        },

        truncate(value, limit = 70) {
            value = value || '';
            if (value.length <= limit) return value;
            return value.substring(0, limit) + '...';
        },

        formatTime(value) {
            if (!value) return '-';
            return new Date(value).toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
            });
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
            return window.HenanApp?.statusBadgeClass
                ? window.HenanApp.statusBadgeClass(status)
                : 'badge-status-default';
        },

        priorityBadgeClass(priority) {
            return window.HenanApp?.priorityBadgeClass
                ? window.HenanApp.priorityBadgeClass(priority)
                : 'badge-priority-default';
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
                : this.formatDateTime(value);
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
        },

        async copyText(text) {
            try {
                await navigator.clipboard.writeText(text || '');
                this.showAlert('Copied to clipboard', 'success');
            } catch (error) {
                console.error(error);
                this.showAlert('Failed to copy text', 'error');
            }
        }
    }
}
