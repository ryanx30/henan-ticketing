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
                    showCompose: false,
                    messageSubmitting: false,
                    composeMode: 'new',
                    composeForm: {
                        to_user_id: '',
                        to_display: '',
                        subject: '',
                        body: '',
                        attachment: null,
                        attachmentName: '',
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

                    csrf() {
                        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    },

                    showAlert(message, type = 'success') {
                        const el = document.getElementById('page-alert');
                        if (!el) return;

                        el.className = 'mb-6 rounded-lg border px-4 py-3 text-sm shadow-sm';
                        el.classList.remove('hidden');

                        if (type === 'success') {
                            el.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
                        } else {
                            el.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
                        }

                        el.textContent = message;

                        setTimeout(() => {
                            el.classList.add('hidden');
                        }, 3000);
                    },

                    async loadDetail() {
                        const response = await fetch(`/api/tickets/${this.ticketId}`, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load ticket detail.');
                        }

                        this.ticket = result.data || {};

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

                        this.statusForm.status = this.ticket.status || '';
                    },

                    async loadSimilarTickets() {
                        const response = await fetch(`/api/tickets/${this.ticketId}/similar`, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load similar tickets.');
                        }

                        this.similarTickets = result.data || [];
                    },

                    async claimTicket() {
                        if (this.claimSubmitting) return;

                        this.claimSubmitting = true;

                        try {
                            const response = await fetch(`/api/it/tickets/${this.ticketId}/claim`, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to claim ticket.');
                            }

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
                            const response = await fetch(`/api/it/tickets/${this.ticketId}/status`, {
                                method: 'PATCH',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                                body: JSON.stringify({
                                    status: this.statusForm.status,
                                    note: this.statusForm.note,
                                }),
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to update status.');
                            }

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
                        this.composeForm.attachment = null;
                        this.composeForm.attachmentName = '';
                        this.showCompose = true;
                    },

                    discardDraft() {
                        this.showCompose = false;
                        this.composeMode = 'new';
                        this.composeForm.to_user_id = '';
                        this.composeForm.to_display = '';
                        this.composeForm.subject = '';
                        this.composeForm.body = '';
                        this.composeForm.attachment = null;
                        this.composeForm.attachmentName = '';
                    },

                    handleAttachment(event) {
                        const file = event.target.files?.[0] || null;
                        this.composeForm.attachment = file;
                        this.composeForm.attachmentName = file ? file.name : '';
                    },

                    async submitMessage() {
                        if (this.messageSubmitting) return;
                        if (!this.composeForm.body.trim()) return;

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

                            const response = await fetch('/api/resolver-inbox', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                                body: formData,
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to send message.');
                            }

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
                            const response = await fetch(`/api/resolver-inbox/${item.id}/read`, {
                                method: 'PATCH',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrf(),
                                },
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to mark message as read.');
                            }

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


                    setComposeRecipient(item = null) {
                        let target = null;

                        if (item?.id) {
                            const isSender = Number(item.from_user_id) === Number(this.currentUserId);
                            target = isSender ? item.recipient : item.sender;
                        }

                        if (!target?.id) {
                            const role = (this.ticket.viewer_role || '').toLowerCase();
                            target = (role === 'it' || role === 'admin')
                                ? this.ticket.creator
                                : this.ticket.holder;
                        }

                        this.composeForm.to_user_id = target?.id || '';
                        this.composeForm.to_display = target
                            ? `${target.name}${target.email ? ' <' + target.email + '>' : ''}`
                            : '';
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

                        const role = (this.ticket.viewer_role || '').toLowerCase();

                        if (role === 'it' || role === 'admin') {
                            return this.ticket.creator ?
                                `${this.ticket.creator.name}${this.ticket.creator.email ? ' <' + this.ticket.creator.email + '>' : ''}` :
                                'Ticket creator';
                        }

                        return this.ticket.holder ?
                            `${this.ticket.holder.name}${this.ticket.holder.email ? ' <' + this.ticket.holder.email + '>' : ''}` :
                            'Ticket holder';
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

                    canManageStatus() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();
                        return role === 'it' || role === 'admin';
                    },

                    canClaimTicket() {
                        const role = (this.ticket.viewer_role || '').toLowerCase();
                        if (!(role === 'it' || role === 'admin')) return false;
                        if ((this.ticket.team || '').toLowerCase() !== 'it') return false;
                        return !this.ticket.holder_id;
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
                        const bytes = Number(size || 0);
                        if (!bytes) return 'Unknown size';

                        if (bytes < 1024) return `${bytes} B`;
                        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;

                        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
                    },

                    formatStatus(value) {
                        return window.HenanApp?.statusLabel
                            ? window.HenanApp.statusLabel(value)
                            : this.humanLabel(value);
                    },

                    formatPriority(value) {
                        return window.HenanApp?.priorityLabel
                            ? window.HenanApp.priorityLabel(value)
                            : this.humanLabel(value);
                    },

                    formatDateTime(value) {
                        if (!value) return '-';

                        try {
                            return new Intl.DateTimeFormat('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                            }).format(new Date(value));
                        } catch {
                            return value;
                        }
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
                        const safeDiff = Math.max(0, Math.abs(Number(diffMs) || 0));
                        const totalSeconds = Math.floor(safeDiff / 1000);
                        const days = Math.floor(totalSeconds / 86400);
                        const hours = Math.floor((totalSeconds % 86400) / 3600);
                        const minutes = Math.floor((totalSeconds % 3600) / 60);
                        const seconds = totalSeconds % 60;
                        const pad = (value) => String(value).padStart(2, '0');

                        if (days > 0) {
                            return `${days}d ${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
                        }

                        return `${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
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
