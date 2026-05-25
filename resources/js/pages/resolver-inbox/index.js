const RESOLVER_INBOX_ROOT = document.getElementById('resolver-inbox-page');
        const RESOLVER_IS_IT = RESOLVER_INBOX_ROOT?.dataset.isIt === '1';
        const RESOLVER_CURRENT_USER_ID = Number(RESOLVER_INBOX_ROOT?.dataset.userId || 0);

        function resolverInboxPage() {
            return {
                isIT: RESOLVER_IS_IT,
                currentUserId: RESOLVER_CURRENT_USER_ID,

                loading: false,
                showCompose: false,

                messages: [],
                composeTickets: [],
                composeRecipients: [],
                meta: {
                    current_page: 1,
                    last_page: 1,
                    total: 0,
                },

                filters: {
                    unread: 'all',
                    priority: 'all',
                    team: 'all',
                    date: 'all',
                    page: 1,
                },

                form: {
                    ticket_id: '',
                    to_user_id: '',
                    to_display: '',
                    subject: '',
                    body: '',
                    attachment: null,
                },

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.filters.unread = params.get('unread') || 'all';
                    this.filters.priority = params.get('priority') || 'all';
                    this.filters.team = params.get('team') || 'all';
                    this.filters.date = params.get('date') || 'all';
                    this.filters.page = Number(params.get('page') || 1);

                    this.loadMessages();
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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
                ticketLabel(ticket) {
                    return window.HenanApp?.ticketLabel(ticket) ?? '-';
                },


                displaySubject(message) {
                    const raw = (message.subject || '').trim();

                    if (!raw) {
                        return 'No subject';
                    }

                    return raw.replace(/^Reply for\s+#?T-[A-Za-z0-9-]+\s*-\s*/i, '').trim();
                },

                previewText(message) {
                    const body = (message.body || '').replace(/\s+/g, ' ').trim();

                    if (!body) {
                        return '-';
                    }

                    return this.truncate(body, 110);
                },

                participantsLabel(message) {
                    return window.HenanApp?.participantsLabel
                        ? window.HenanApp.participantsLabel(message, this.currentUserId)
                        : `${message.sender?.name || 'Unknown sender'} → ${message.recipient?.name || 'Unknown recipient'}`;
                },

                formatDateTimeShort(value) {
                    return window.HenanApp?.formatDateTimeShort
                        ? window.HenanApp.formatDateTimeShort(value)
                        : (value ? new Date(value).toLocaleString('id-ID') : '-');
                },

                messageUrl(message) {
                    const messageId = message?.id || message;

                    return window.HenanApp?.routes?.resolverInboxDetail
                        ? window.HenanApp.routes.resolverInboxDetail(messageId)
                        : `/resolver-inbox/${messageId}`;
                },

                openMessage(message) {
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
                        : this.displaySubject(message);
                },

                messagePreview(message, limit = 100) {
                    return window.HenanApp?.messagePreview
                        ? window.HenanApp.messagePreview(message, limit)
                        : this.previewText(message);
                },

                priorityLabel(priority) {
                    return window.HenanApp?.priorityLabel
                        ? window.HenanApp.priorityLabel(priority)
                        : this.ucfirst(priority);
                },

                statusLabel(status) {
                    return window.HenanApp?.statusLabel
                        ? window.HenanApp.statusLabel(status)
                        : (status || '-');
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

                buildQuery() {
                    const params = new URLSearchParams();
                    params.set('unread', this.filters.unread);
                    params.set('priority', this.filters.priority);
                    params.set('team', this.filters.team);
                    params.set('date', this.filters.date);
                    params.set('page', this.filters.page);
                    return params;
                },

                applyFilters() {
                    this.filters.page = 1;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadMessages();
                },

                goToPage(page) {
                    this.filters.page = page;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadMessages();
                },

                async loadMessages() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const res = await fetch(`/api/resolver-inbox?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            throw new Error(result.message || 'Failed to load resolver inbox');
                        }

                        this.messages = result.data || [];
                        this.meta = result.meta || {
                            current_page: 1,
                            last_page: 1,
                            total: 0
                        };
                        this.composeTickets = result.extra?.compose_tickets || [];
                        this.composeRecipients = result.extra?.compose_recipients || [];
                    } catch (error) {
                        console.error(error);
                        this.messages = [];
                        this.composeTickets = [];
                        this.showAlert(error.message || 'Failed to load resolver inbox', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                openCompose() {
                    this.showCompose = true;
                },

                handleAttachment(event) {
                    this.form.attachment = event.target.files[0] || null;
                },

                syncTicketMeta() {
                    const ticket = this.composeTickets.find(t => String(t.id) === String(this.form.ticket_id));

                    if (!ticket) {
                        this.form.to_user_id = '';
                        this.form.to_display = '';
                        this.form.subject = '';
                        return;
                    }

                    let target = null;

                    if (this.isIT) {
                        target = ticket.creator || null;
                    } else {
                        target = ticket.holder || null;
                    }

                    this.form.to_user_id = target?.id || '';
                    this.form.to_display = target ?
                        `${target.name}${target.email ? ' <' + target.email + '>' : ''}` :
                        '';

                    this.form.subject = `Ticket update: ${ticket.title || this.ticketLabel(ticket)}`;
                },

                replyMessage(message) {
                    this.showCompose = true;
                    this.form.ticket_id = message.ticket_id || '';
                    this.form.subject = '';
                    this.syncTicketMeta();

                    const isSender = Number(message.from_user_id) === Number(this.currentUserId);
                    const target = isSender ? message.recipient : message.sender;

                    if (target?.id) {
                        this.form.to_user_id = target.id;
                        this.form.to_display = `${target.name}${target.email ? ' <' + target.email + '>' : ''}`;
                    }
                },

                discardDraft() {
                    this.form.ticket_id = '';
                    this.form.to_user_id = '';
                    this.form.to_display = '';
                    this.form.subject = '';
                    this.form.body = '';
                    this.form.attachment = null;
                    this.showCompose = false;
                },

                async submitCompose() {
                    try {
                        const formData = new FormData();
                        formData.append('ticket_id', this.form.ticket_id || '');
                        formData.append('to_user_id', this.form.to_user_id || '');
                        formData.append('subject', this.form.subject || '');
                        formData.append('body', this.form.body || '');

                        if (this.form.attachment) {
                            formData.append('attachment', this.form.attachment);
                        }

                        const res = await fetch('/api/resolver-inbox', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: formData
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            throw new Error(result.message || 'Failed to send message');
                        }

                        this.showAlert(result.message || 'Message sent successfully', 'success');
                        this.discardDraft();
                        await this.loadMessages();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to send message', 'error');
                    }
                },

                async markAsRead(messageId) {
                    try {
                        const res = await fetch(`/api/resolver-inbox/${messageId}/read`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            throw new Error(result.message || 'Failed to mark message as read');
                        }

                        await this.loadMessages();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to mark message as read', 'error');
                    }
                },

                async deleteMessage(messageId) {
                    if (!confirm('Delete this message?')) return;

                    try {
                        const res = await fetch(`/api/resolver-inbox/${messageId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            throw new Error(result.message || 'Failed to delete message');
                        }

                        this.showAlert(result.message || 'Message deleted', 'success');
                        await this.loadMessages();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to delete message', 'error');
                    }
                }
            }
        }

window.resolverInboxPage = resolverInboxPage;
