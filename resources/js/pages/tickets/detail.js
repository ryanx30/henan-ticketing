/**
 * Ticket detail page controller.
 * Coordinates detail payload loading, status actions, similar tickets, attachments, and resolver message interactions.
 */

import { apiGet, apiPost, apiPatch, apiRequest } from '../../utils/apiClient';
import { formatDateTime as formatDateTimeValue, formatFileSize as formatFileSizeValue, formatLiveDuration as formatLiveDurationValue } from '../../utils/formatter';
import { priorityBadgeClass as buildPriorityBadgeClass, priorityLabel as buildPriorityLabel, statusBadgeClass as buildStatusBadgeClass, statusLabel as buildStatusLabel } from '../../utils/badges';
import { showPageAlert } from '../../utils/toast';

function ticketDetailPage({
                ticketId,
                currentUserId
            }) {
                return {
                    ticketId,
                    currentUserId,
                    loading: true,
                    errorMessage: '',
                    ticket: {},
                    statusHistories: [],
                    updates: [],
                    attachments: [],
                    similarTickets: [],
                    statusSubmitting: false,
                    claimSubmitting: false,
                    escalationSubmitting: false,
                    showEscalationConfirm: false,
                    showCompose: false,
                    messageSubmitting: false,
                    quickMessageSubmitting: false,
                    composeMode: 'new',
                    composeForm: {
                        to_user_id: '',
                        to_display: '',
                        subject: '',
                        body: '',
                        attachment: null,
                        attachmentName: '',
                        attachmentStatus: '',
                        attachmentInputKey: Date.now(),
                    },
                    quickMessage: {
                        to_user_id: '',
                        to_display: '',
                        body: '',
                    },
                    escalationForm: {
                        target_user_id: '',
                        note: '',
                    },
                    statusForm: {
                        status: '',
                        note: '',
                    },
                    master: {
                        teams: [],
                        categories: [],
                        priorities: [],
                    },
                    now: Date.now(),
                    slaTicker: null,

                    async init() {
                        this.startLiveClock();
                        await this.loadAll();
                    },

                    destroy() {
                        if (this.slaTicker) {
                            clearInterval(this.slaTicker);
                            this.slaTicker = null;
                        }
                    },

                    startLiveClock() {
                        this.now = Date.now();

                        if (this.slaTicker) {
                            clearInterval(this.slaTicker);
                        }

                        this.slaTicker = setInterval(() => {
                            this.now = Date.now();
                        }, 1000);
                    },

                    async loadAll() {
                        this.loading = true;
                        this.errorMessage = '';

                        try {
                            await Promise.all([
                                this.loadDetail(),
                                this.loadSimilarTickets(),
                            ]);
                        } catch (error) {
                            console.error(error);
                            this.errorMessage = error.message || 'Failed to load ticket detail.';
                        } finally {
                            this.loading = false;
                        }
                    },

                    showAlert(message, type = 'success') {
                        showPageAlert(message, type, 'page-alert', 3000);
                    },

                    async loadDetail() {
                        const result = await apiGet(`/api/tickets/${this.ticketId}`);

                        this.ticket = this.normalizeTicketPayload(result.data || {});

                        const relations = this.ticket.relations || {};

                        this.statusHistories = relations.history?.data ||
                            this.ticket.status_histories ||
                            this.ticket.statusHistories ||
                            [];

                        const rawUpdates = relations.messages?.data ||
                            this.ticket.resolver_messages ||
                            this.ticket.resolverMessages ||
                            [];

                        this.attachments = relations.attachments?.data ||
                            this.ticket.attachments ||
                            [];

                        this.updates = [...rawUpdates].sort((a, b) => {
                            return new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime();
                        });

                        this.statusForm.status = '';
                        this.syncAutoMessageRecipient();
                        this.syncEscalationTarget();
                    },

                    async loadSimilarTickets() {
                        const result = await apiGet(`/api/tickets/${this.ticketId}/similar`);

                        this.similarTickets = result.data || [];
                    },


                    normalizeTicketPayload(payload) {
                        const code = payload.code || {};
                        const classification = payload.classification || {};
                        const assignment = payload.assignment || {};
                        const timestamps = payload.timestamps || {};
                        const viewer = payload.viewer || {};
                        const client = payload.client || null;
                        const status = payload.status || {};

                        const priority = classification.priority || {};
                        const team = classification.team || {};
                        const category = classification.category || {};
                        const issueType = classification.issue_type || {};

                        return {
                            ...payload,
                            ticket_code: code.raw || payload.ticket_code || '',
                            ticket_label: code.label || payload.ticket_label || '',
                            status: status.code || payload.status || '',
                            status_label: status.label || payload.status_label || '',
                            priority: priority.code || priority.name || payload.priority || '',
                            priority_label: priority.name || payload.priority_label || '',
                            team: team.code || team.name || payload.team || '',
                            team_label: team.name || payload.team_label || '',
                            category: category.name || category.slug || payload.category || '',
                            category_label: category.name || payload.category_label || '',
                            issue_type: issueType.name || issueType.slug || payload.issue_type || '',
                            issue_type_label: issueType.name || payload.issue_type_label || '',
                            client_id: client?.id || payload.client_id || null,
                            client_name: client?.name || payload.client_name || '',
                            client_contact: client?.contact || payload.client_contact || '',
                            client_email: client?.email || payload.client_email || '',
                            creator: assignment.creator || payload.creator || null,
                            holder: assignment.holder || payload.holder || null,
                            created_by: assignment.creator?.id || payload.created_by || null,
                            holder_id: assignment.holder?.id || payload.holder_id || null,
                            request_time: timestamps.request_time || payload.request_time || null,
                            sla_deadline_at: timestamps.sla_deadline_at || payload.sla_deadline_at || null,
                            claimed_at: timestamps.claimed_at || payload.claimed_at || null,
                            resolved_at: timestamps.resolved_at || payload.resolved_at || null,
                            closed_at: timestamps.closed_at || payload.closed_at || null,
                            created_at: timestamps.created_at || payload.created_at || null,
                            created_at_label: timestamps.created_at_label || payload.created_at_label || '',
                            updated_at: timestamps.updated_at || payload.updated_at || null,
                            viewer_id: viewer.id || payload.viewer_id || null,
                            viewer_role: viewer.role || payload.viewer_role || '',
                        };
                    },

                    async claimTicket() {
                        if (this.claimSubmitting) return;

                        this.claimSubmitting = true;

                        try {
                            const result = await apiPost(`/api/it/tickets/${this.ticketId}/claim`, {});

                            this.showAlert(result.message || 'Ticket claimed successfully.', 'success');
                            await this.loadAll();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to claim ticket.', 'error');
                        } finally {
                            this.claimSubmitting = false;
                        }
                    },

                    async submitStatusChange() {
                        if (!this.statusForm.status || this.statusSubmitting) return;

                        this.statusSubmitting = true;

                        try {
                            const result = await apiPatch(`/api/it/tickets/${this.ticketId}/status`, {
                                status: this.statusForm.status,
                                note: this.statusForm.note,
                            });

                            this.statusForm.note = '';
                            this.showAlert(result.message || 'Status updated successfully.', 'success');
                            await this.loadAll();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to update status.', 'error');
                        } finally {
                            this.statusSubmitting = false;
                        }
                    },

                    openCompose(item = null) {
                        this.composeMode = item ? 'reply' : 'new';
                        this.setComposeRecipient(item);
                        this.composeForm.subject = this.buildReplySubject();
                        this.composeForm.body = '';
                        this.clearComposeAttachment();
                        this.showCompose = true;
                    },

                    discardDraft() {
                        this.showCompose = false;
                        this.composeMode = 'new';
                        this.composeForm.to_user_id = '';
                        this.composeForm.to_display = '';
                        this.composeForm.subject = '';
                        this.composeForm.body = '';
                        this.clearComposeAttachment();
                    },

                    handleAttachment(event) {
                        const file = event.target.files?.[0] || null;
                        this.composeForm.attachment = file;
                        this.composeForm.attachmentName = file ? file.name : '';
                        this.composeForm.attachmentStatus = file ? 'uploading' : '';

                        if (file) {
                            window.setTimeout(() => {
                                if (this.composeForm.attachment === file) {
                                    this.composeForm.attachmentStatus = 'ready';
                                }
                            }, 650);
                        }
                    },

                    clearComposeAttachment() {
                        this.composeForm.attachment = null;
                        this.composeForm.attachmentName = '';
                        this.composeForm.attachmentStatus = '';
                        this.composeForm.attachmentInputKey = Date.now();
                    },

                    composeAttachmentStatusLabel() {
                        if (!this.composeForm.attachmentName) {
                            return '';
                        }

                        return this.composeForm.attachmentStatus === 'uploading' ? 'Uploading...' : 'Attached';
                    },

                    async submitMessage() {
                        if (this.messageSubmitting) return;
                        if (!this.composeForm.body.trim()) return;
                        if (!this.canSendResolverMessage()) {
                            this.showAlert('Only the current ticket owner can send resolver messages.', 'error');
                            return;
                        }

                        this.setComposeRecipient();

                        if (!this.composeForm.to_user_id) {
                            this.showAlert('No valid resolver conversation recipient is available.', 'error');
                            return;
                        }

                        if (this.composeForm.attachmentStatus === 'uploading') {
                            this.showAlert('Please wait until the attachment is ready.', 'error');
                            return;
                        }

                        this.messageSubmitting = true;

                        try {
                            const formData = new FormData();
                            formData.append('ticket_id', this.ticketId);
                            formData.append('to_user_id', this.composeForm.to_user_id || '');
                            formData.append('subject', this.composeForm.subject || '');
                            formData.append('body', this.composeForm.body || '');

                            if (this.composeForm.attachment) {
                                formData.append('attachment', this.composeForm.attachment);
                            }

                            const result = await apiRequest('/api/resolver-inbox', {
                                method: 'POST',
                                body: formData,
                            });

                            this.showAlert(result.message || 'Message sent successfully.', 'success');
                            this.discardDraft();
                            await this.loadDetail();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to send message.', 'error');
                        } finally {
                            this.messageSubmitting = false;
                        }
                    },

                    async markUpdateAsRead(item) {
                        if (!item?.id || !this.isUnreadUpdate(item)) return;

                        try {
                            const result = await apiPatch(`/api/resolver-inbox/${item.id}/read`, {});

                            item.is_read = true;
                            item.read_at = result.data?.read_at || item.read_at;
                            this.showAlert(result.message || 'Message marked as read.', 'success');
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to mark message as read.', 'error');
                        }
                    },

                    openMessageDetail(item) {
                        if (!item?.id) return;
                        window.location.href = `/resolver-inbox/${item.id}`;
                    },

                    ticketLabel(ticket = null) {
                        return window.HenanApp?.ticketLabel(ticket) ?? '-';
                    },

                    currentTicketLabel() {
                        return this.ticketLabel(this.ticket?.ticket_code ? this.ticket : {
                            id: this.ticketId
                        });
                    },


                    actions() {
                        return this.ticket?.actions || {};
                    },

                    latestResolverUpdate() {
                        return this.updates?.[0] || null;
                    },

                    openConversation() {
                        const latest = this.latestResolverUpdate();
                        if (latest?.id) {
                            this.openMessageDetail(latest);
                            return;
                        }

                        this.openCompose();
                    },

                    autoConversationRecipient() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();

                        if (role === 'it') {
                            return this.ticket.creator || null;
                        }

                        if (role === 'cs' || role === 'head_cs') {
                            return this.ticket.holder || null;
                        }

                        if (role === 'admin') {
                            return this.ticket.holder || this.ticket.creator || null;
                        }

                        return null;
                    },

                    recipientLabel(recipient) {
                        if (!recipient) return 'No recipient available';

                        return `${recipient.name || 'Unknown'}${recipient.email ? ' <' + recipient.email + '>' : ''}`;
                    },

                    syncAutoMessageRecipient() {
                        const target = this.autoConversationRecipient();
                        this.quickMessage.to_user_id = target?.id || '';
                        this.quickMessage.to_display = this.recipientLabel(target);

                        if (!this.showCompose) {
                            this.composeForm.to_user_id = target?.id || '';
                            this.composeForm.to_display = this.recipientLabel(target);
                        }
                    },

                    conversationRecipients() {
                        const target = this.autoConversationRecipient();
                        return target?.id ? [target] : [];
                    },

                    canSendResolverMessage() {
                        return Boolean(this.actions().can_send_resolver_message);
                    },

                    setComposeRecipient() {
                        const target = this.autoConversationRecipient();

                        this.composeForm.to_user_id = target?.id || '';
                        this.composeForm.to_display = this.recipientLabel(target);
                    },

                    buildReplySubject() {
                        const ticketLabel = this.currentTicketLabel();
                        const ticketTitle = this.ticket?.title || 'Message';
                        return `Ticket update: ${ticketTitle || ticketLabel}`;
                    },

                    composeRecipientLabel() {
                        if (this.composeForm.to_display) {
                            return this.composeForm.to_display;
                        }

                        const target = this.autoConversationRecipient();
                        return this.recipientLabel(target);
                    },

                    handleQuickMessageKeydown(event) {
                        if (event.key === 'Enter' && !event.shiftKey) {
                            event.preventDefault();
                            this.submitQuickMessage();
                        }
                    },

                    async submitQuickMessage() {
                        if (this.quickMessageSubmitting) return;
                        if (!this.quickMessage.body.trim()) return;
                        if (!this.canSendResolverMessage()) {
                            this.showAlert('Only the current ticket owner can send resolver messages.', 'error');
                            return;
                        }

                        const target = this.autoConversationRecipient();
                        if (!target?.id) {
                            this.showAlert('No valid resolver conversation recipient is available.', 'error');
                            return;
                        }

                        this.quickMessageSubmitting = true;

                        try {
                            const formData = new FormData();
                            formData.append('ticket_id', this.ticketId);
                            formData.append('to_user_id', target.id);
                            formData.append('subject', this.buildReplySubject());
                            formData.append('body', this.quickMessage.body || '');

                            const result = await apiRequest('/api/resolver-inbox', {
                                method: 'POST',
                                body: formData,
                            });

                            this.quickMessage.body = '';
                            this.showAlert(result.message || 'Message sent successfully.', 'success');
                            await this.loadDetail();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to send message.', 'error');
                        } finally {
                            this.quickMessageSubmitting = false;
                        }
                    },

                    displayUpdateTitle(item) {
                        if (this.ticket?.title) {
                            return this.ticket.title;
                        }

                        const normalized = this.normalizedUpdateSubject(item);
                        return normalized || 'Resolver Update';
                    },

                    normalizedUpdateSubject(item) {
                        const raw = (item?.subject || '').trim();
                        if (!raw) return '';
                        return raw.replace(/^Reply for\s+#?T-[A-Za-z0-9-]+\s*-\s*/i, '').trim();
                    },

                    updateParticipants(item) {
                        const fromName = item?.sender?.name || 'Unknown sender';
                        const toName = item?.recipient?.name || 'Unknown recipient';
                        return `${fromName} → ${toName}`;
                    },

                    updateBody(item) {
                        return item?.body || item?.message || '-';
                    },

                    isUnreadUpdate(item) {
                        return !!item && !item.is_read && Number(item.to_user_id) === Number(this.currentUserId);
                    },

                    statusOptions() {
                        return Array.isArray(this.ticket?.status_options)
                            ? this.ticket.status_options
                            : [];
                    },

                    hasStatusOptions() {
                        return this.statusOptions().length > 0;
                    },

                    canManageStatus() {
                        return Boolean(this.actions().can_update_status);
                    },

                    canClaimTicket() {
                        return Boolean(this.actions().can_claim)
                            && !Number(this.ticket?.holder_id || 0)
                            && !this.ticket?.holder;
                    },

                    canEscalateTicket() {
                        return Boolean(this.actions().can_escalate);
                    },

                    escalationUsers() {
                        return this.ticket?.handoff?.eligible_users || [];
                    },

                    escalationModeLabel() {
                        const mode = this.ticket?.handoff?.mode;
                        if (mode === 'cs') return 'CS Owner';
                        if (mode === 'it') return 'IT Holder';
                        return 'Owner';
                    },

                    syncEscalationTarget() {
                        const users = this.escalationUsers();
                        this.escalationForm.target_user_id = users[0]?.id ? String(users[0].id) : '';
                    },

                    selectedEscalationUser() {
                        const targetId = String(this.escalationForm.target_user_id || '');
                        return this.escalationUsers().find(user => String(user.id) === targetId) || null;
                    },

                    currentEscalationOwnerLabel() {
                        const mode = this.ticket?.handoff?.mode;
                        const currentOwner = mode === 'cs' ? this.ticket.creator : this.ticket.holder;

                        return this.recipientLabel(currentOwner);
                    },

                    escalationTargetLabel() {
                        return this.recipientLabel(this.selectedEscalationUser());
                    },

                    escalationConfirmTitle() {
                        return `Confirm ${this.escalationModeLabel()} Handoff`;
                    },

                    escalationConfirmMessage() {
                        return `This action will move ticket ${this.currentTicketLabel()} from ${this.currentEscalationOwnerLabel()} to ${this.escalationTargetLabel()}. After handoff, the previous owner can still view the ticket detail but cannot update the status or send resolver messages.`;
                    },

                    openEscalationConfirm() {
                        if (!this.canEscalateTicket() || this.escalationSubmitting || !this.escalationForm.target_user_id) return;

                        this.showEscalationConfirm = true;
                    },

                    closeEscalationConfirm() {
                        if (this.escalationSubmitting) return;

                        this.showEscalationConfirm = false;
                    },

                    async submitEscalation() {
                        if (!this.canEscalateTicket() || this.escalationSubmitting || !this.escalationForm.target_user_id) return;

                        this.escalationSubmitting = true;

                        try {
                            const result = await apiPatch(`/api/tickets/${this.ticketId}/escalate`, {
                                target_user_id: this.escalationForm.target_user_id,
                                note: this.escalationForm.note,
                            });

                            this.showAlert(result.message || 'Ticket handoff completed successfully.', 'success');
                            this.escalationForm.note = '';
                            this.showEscalationConfirm = false;
                            await this.loadAll();
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to handoff ticket.', 'error');
                        } finally {
                            this.escalationSubmitting = false;
                        }
                    },

                    slugify(value) {
                        return String(value || '')
                            .toLowerCase()
                            .trim()
                            .replace(/[^a-z0-9]+/g, '_')
                            .replace(/^_+|_+$/g, '');
                    },

                    masterLabel(collection, value) {
                        const target = this.slugify(value);
                        if (!target) return '-';

                        const found = collection.find(item => {
                            const candidates = [
                                item.name,
                                item.code,
                                item.slug,
                                item.code_num,
                                this.slugify(item.name),
                            ];

                            return candidates.some(candidate => this.slugify(candidate) === target);
                        });

                        return found?.name || this.humanLabel(value);
                    },

                    humanLabel(value) {
                        if (!value) return '-';

                        return String(value)
                            .replaceAll('_', ' ')
                            .replaceAll('-', ' ')
                            .replace(/\b\w/g, c => c.toUpperCase());
                    },

                    formatTeam(value) {
                        return this.masterLabel(this.master.teams, value).toUpperCase();
                    },

                    formatCategory(value) {
                        return this.masterLabel(this.master.categories, value);
                    },

                    formatIssueType(value) {
                        return this.humanLabel(value);
                    },



                    isImageAttachment(attachment) {
                        const type = String(attachment?.file_type || '').toLowerCase();
                        const name = String(attachment?.file_name || '').toLowerCase();

                        return type.startsWith('image/')
                            || /\.(jpg|jpeg|png|gif|webp)$/i.test(name);
                    },

                    attachmentExtension(attachment) {
                        const name = String(attachment?.file_name || 'file');
                        const ext = name.includes('.') ? name.split('.').pop() : 'file';
                        return String(ext || 'file').slice(0, 4).toUpperCase();
                    },

                    formatFileSize(size) {
                        return formatFileSizeValue(size);
                    },

                    formatStatus(value) {
                        return buildStatusLabel(value);
                    },

                    formatPriority(value) {
                        return buildPriorityLabel(value);
                    },

                    formatDateTime(value) {
                        return formatDateTimeValue(value, 'en-GB');
                    },

                    statusBadgeClass(status) {
                        return buildStatusBadgeClass(status);
                    },

                    priorityBadgeClass(priority) {
                        return buildPriorityBadgeClass(priority);
                    },

                    slaLabel() {
                        if (!this.ticket.sla_deadline_at) return 'No SLA';
                        if (this.ticket.status === 'resolved' || this.ticket.status === 'closed') return 'Completed';

                        const deadline = new Date(this.ticket.sla_deadline_at).getTime();
                        const diff = deadline - this.now;

                        if (diff < 0) return 'Breached';
                        if (diff <= 2 * 60 * 60 * 1000) return 'At Risk';
                        return 'Safe';
                    },

                    slaCardClass() {
                        const label = this.slaLabel();
                        if (label === 'Breached') return 'bg-red-600';
                        if (label === 'At Risk') return 'bg-amber-500';
                        if (label === 'Completed') return 'bg-emerald-600';
                        if (label === 'No SLA') return 'bg-slate-500';
                        return 'bg-[#2f88d8]';
                    },

                    formatLiveDuration(diffMs) {
                        return formatLiveDurationValue(diffMs);
                    },

                    remainingSlaText() {
                        if (!this.ticket.sla_deadline_at) return '-';
                        if (this.ticket.status === 'resolved' || this.ticket.status === 'closed') return 'Finished';

                        const deadline = new Date(this.ticket.sla_deadline_at).getTime();
                        const diff = deadline - this.now;
                        const suffix = diff < 0 ? 'overdue' : 'left';

                        return `${this.formatLiveDuration(diff)} ${suffix}`;
                    },

                    ticketAgeText() {
                        if (!this.ticket.created_at) return '-';

                        const created = new Date(this.ticket.created_at).getTime();
                        const diff = this.now - created;

                        return this.formatLiveDuration(diff);
                    },
                }
            }

window.ticketDetailPage = ticketDetailPage;
