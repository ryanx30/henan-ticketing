/**
 * Resolver inbox list page controller.
 * Loads messages, filters, pagination, compose metadata, read status, and delete actions.
 */

import { apiGet, apiPost, apiDelete, apiRequest } from '../../utils/apiClient';
import {
    formatDateTimeShort as formatDateTimeShortValue,
    formatTimeShort,
    truncateText,
} from '../../utils/formatter';
import { priorityBadgeClass as buildPriorityBadgeClass, priorityLabel as buildPriorityLabel, statusBadgeClass as buildStatusBadgeClass, statusLabel as buildStatusLabel } from '../../utils/badges';
import { showPageAlert } from '../../utils/toast';

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
                expandedResolverThreads: {},
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
                    attachmentPreview: null,
                    attachmentInputKey: Date.now(),
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

                showAlert(message, type = 'success') {
                    showPageAlert(message, type);
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
                    return formatDateTimeShortValue(value);
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


                resolverInboxThreads(limit = null) {
                    const threads = new Map();

                    for (const message of this.messages || []) {
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

                    const groupedThreads = Array.from(threads.values())
                        .map((thread) => ({
                            ...thread,
                            messages: thread.messages.sort((a, b) => this.messageTimestamp(b) - this.messageTimestamp(a)),
                        }))
                        .sort((a, b) => this.messageTimestamp(b.latestMessage) - this.messageTimestamp(a.latestMessage));

                    return limit ? groupedThreads.slice(0, limit) : groupedThreads;
                },

                resolverConversationCount() {
                    return Number(this.meta?.total || 0) || this.resolverInboxThreads().length;
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

                priorityLabel(priority) {
                    return buildPriorityLabel(priority);
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
                        const result = await apiGet(`/api/resolver-inbox?${params.toString()}`);
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
                    const file = event.target.files?.[0] || null;
                    this.form.attachment = file;

                    if (!file) {
                        this.form.attachmentPreview = null;
                        return;
                    }

                    this.form.attachmentPreview = {
                        name: file.name,
                        size: file.size,
                        status: 'uploading',
                    };

                    window.setTimeout(() => {
                        if (this.form.attachment === file && this.form.attachmentPreview) {
                            this.form.attachmentPreview = {
                                ...this.form.attachmentPreview,
                                status: 'ready',
                            };
                        }
                    }, 650);
                },

                clearAttachment() {
                    this.form.attachment = null;
                    this.form.attachmentPreview = null;
                    this.form.attachmentInputKey = Date.now();
                },

                attachmentFileName() {
                    return this.form.attachmentPreview?.name || this.form.attachment?.name || '';
                },

                attachmentStatusLabel() {
                    if (!this.form.attachmentPreview) {
                        return '';
                    }

                    return this.form.attachmentPreview.status === 'uploading' ? 'Uploading...' : 'Attached';
                },

                attachmentSizeLabel(size = null) {
                    const bytes = Number(size ?? this.form.attachmentPreview?.size ?? this.form.attachment?.size ?? 0);

                    if (!bytes) {
                        return '';
                    }

                    if (bytes < 1024) {
                        return `${bytes} B`;
                    }

                    if (bytes < 1024 * 1024) {
                        return `${(bytes / 1024).toFixed(1)} KB`;
                    }

                    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
                },

                attachmentIconClass(message) {
                    const name = String(message?.attachment_name || this.attachmentFileName() || '').toLowerCase();

                    if (name.endsWith('.pdf')) return 'bg-red-50 text-red-600 ring-red-100';
                    if (name.match(/\.(doc|docx)$/)) return 'bg-blue-50 text-blue-600 ring-blue-100';
                    if (name.match(/\.(xls|xlsx|csv)$/)) return 'bg-emerald-50 text-emerald-600 ring-emerald-100';
                    if (name.match(/\.(jpg|jpeg|png)$/)) return 'bg-purple-50 text-purple-600 ring-purple-100';

                    return 'bg-slate-50 text-slate-600 ring-slate-100';
                },

                attachmentUrl(message) {
                    if (!message?.id || !message?.attachment_name) {
                        return '#';
                    }

                    return `/api/resolver-inbox/${message.id}/attachment/download`;
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
                    this.clearAttachment();
                    this.showCompose = false;
                },

                async submitCompose() {
                    try {
                        if (this.form.attachmentPreview?.status === 'uploading') {
                            this.showAlert('Please wait until the attachment is ready.', 'error');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('ticket_id', this.form.ticket_id || '');
                        formData.append('to_user_id', this.form.to_user_id || '');
                        formData.append('subject', this.form.subject || '');
                        formData.append('body', this.form.body || '');

                        if (this.form.attachment) {
                            formData.append('attachment', this.form.attachment);
                        }

                        const result = await apiPost('/api/resolver-inbox', formData);
                        this.showAlert(result.message || 'Message sent successfully', 'success');

                        const sentMessage = result.data || null;
                        this.discardDraft();

                        if (sentMessage?.id) {
                            this.messages = [sentMessage, ...(this.messages || []).filter((message) => Number(message.id) !== Number(sentMessage.id))];
                        }

                        await this.loadMessages();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to send message', 'error');
                    }
                },
                async markAsRead(messageId) {
                    try {
                        await apiRequest(`/api/resolver-inbox/${messageId}/read`, { method: 'PATCH' });
                        await this.loadMessages();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to mark message as read', 'error');
                    }
                },
                async deleteMessage(messageId) {
                    if (!confirm('Delete this message?')) return;

                    try {
                        const result = await apiDelete(`/api/resolver-inbox/${messageId}`);
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
