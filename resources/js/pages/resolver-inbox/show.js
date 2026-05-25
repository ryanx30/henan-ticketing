function resolverMessageDetailPage() {
    return {
        loading: false,
        submitting: false,
        messageId: Number(document.getElementById('resolver-message-detail-page')?.dataset.messageId || 0),
        currentUserId: Number(document.getElementById('resolver-message-detail-page')?.dataset.currentUserId || 0),
        isIT: document.getElementById('resolver-message-detail-page')?.dataset.isIt === '1',

        message: {},
        ticket: {},
        threadMessages: [],
        activeMenuId: null,
        activeConversationKey: '',
        replyTarget: null,
        replyContextDismissed: true,

        reply: {
            ticket_id: '',
            to_user_id: '',
            to_display: '',
            subject: '',
            body: '',
        },

        init() {
            this.loadMessage();
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        showAlert(message, type = 'success') {
            if (window.HenanApp?.showPageAlert) {
                window.HenanApp.showPageAlert(message, type);
                return;
            }

            const el = document.getElementById('page-alert');
            if (!el) return;

            el.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
            el.textContent = message;

            if (type === 'success') {
                el.classList.add('bg-green-100', 'text-green-800');
            } else {
                el.classList.add('bg-red-100', 'text-red-800');
            }

            setTimeout(() => el.classList.add('hidden'), 3000);
        },

        async loadMessage() {
            this.loading = true;
            const previousConversationKey = this.activeConversationKey;

            try {
                const res = await fetch(`/api/resolver-inbox/${this.messageId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const result = await res.json();

                if (!res.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load message detail');
                }

                const data = result.data || {};

                this.message = data.message || data;
                this.ticket = data.ticket || this.message.ticket || {};
                this.threadMessages = Array.isArray(data.thread_messages)
                    ? data.thread_messages
                    : (Array.isArray(data.thread) ? data.thread : [this.message].filter(Boolean));

                const rooms = this.conversationRooms();
                const messageKey = this.roomKeyFromMessage(this.message);
                this.activeConversationKey = previousConversationKey && rooms.some((room) => room.key === previousConversationKey)
                    ? previousConversationKey
                    : (messageKey || rooms[0]?.key || '');

                this.ensureReplyTarget();

                this.$nextTick(() => this.scrollThreadToBottom());
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to load message detail', 'error');
            } finally {
                this.loading = false;
            }
        },

        roomKeyFromMessage(threadMessage) {
            const fromId = Number(threadMessage?.from_user_id || threadMessage?.sender?.id || 0);
            const toId = Number(threadMessage?.to_user_id || threadMessage?.recipient?.id || 0);

            if (!fromId || !toId) {
                return '';
            }

            return [fromId, toId].sort((a, b) => a - b).join(':');
        },

        conversationRooms() {
            const roomMap = new Map();

            (this.threadMessages || []).forEach((threadMessage) => {
                const key = this.roomKeyFromMessage(threadMessage);
                if (!key) return;

                const sender = threadMessage.sender || {
                    id: threadMessage.from_user_id,
                    name: `User #${threadMessage.from_user_id}`,
                    email: '',
                };

                const recipient = threadMessage.recipient || {
                    id: threadMessage.to_user_id,
                    name: `User #${threadMessage.to_user_id}`,
                    email: '',
                };

                const existing = roomMap.get(key) || {
                    key,
                    participants: [sender, recipient],
                    lastMessage: null,
                    messages: [],
                    unreadCount: 0,
                };

                existing.messages.push(threadMessage);

                if (!existing.lastMessage || new Date(threadMessage.created_at || 0) > new Date(existing.lastMessage.created_at || 0)) {
                    existing.lastMessage = threadMessage;
                }

                if (Number(threadMessage.to_user_id) === Number(this.currentUserId) && !threadMessage.is_read) {
                    existing.unreadCount += 1;
                }

                roomMap.set(key, existing);
            });

            return Array.from(roomMap.values())
                .map((room) => ({
                    ...room,
                    title: this.roomTitle(room),
                    subtitle: this.truncate(room.lastMessage?.body || '-', 58),
                    time: this.formatDateTimeShort(room.lastMessage?.created_at),
                    avatar: this.roomAvatar(room),
                }))
                .sort((a, b) => new Date(b.lastMessage?.created_at || 0) - new Date(a.lastMessage?.created_at || 0));
        },

        roomTitle(room) {
            const currentUserId = Number(this.currentUserId);
            const otherUser = (room.participants || []).find((participant) => Number(participant?.id) !== currentUserId);

            if (otherUser?.name) {
                return otherUser.name;
            }

            return (room.participants || [])
                .map((participant) => participant?.name || 'Unknown')
                .join(' ↔ ');
        },

        roomAvatar(room) {
            const title = this.roomTitle(room);
            return String(title || '?')
                .trim()
                .split(/\s+/)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join('') || '?';
        },

        selectedRoom() {
            return this.conversationRooms().find((room) => room.key === this.activeConversationKey) || null;
        },

        selectConversation(room) {
            if (!room?.key) return;

            this.activeConversationKey = room.key;
            this.activeMenuId = null;
            this.replyContextDismissed = true;
            this.ensureReplyTarget(true);
            this.$nextTick(() => this.scrollThreadToBottom());
        },

        filteredThreadMessages() {
            if (!this.activeConversationKey) {
                return this.threadMessages || [];
            }

            return (this.threadMessages || []).filter((threadMessage) => this.roomKeyFromMessage(threadMessage) === this.activeConversationKey);
        },

        ensureReplyTarget(force = false) {
            if (this.replyTarget?.id && !force) return;

            const activeMessages = this.filteredThreadMessages();
            const targetMessage = [...activeMessages]
                .reverse()
                .find((item) => Number(item.from_user_id) !== Number(this.currentUserId))
                || [...activeMessages].reverse()[0]
                || this.message;

            if (targetMessage?.id) {
                this.setReplyTarget(targetMessage, false, true);
            }
        },

        setReplyTarget(threadMessage, clearBody = true, silent = false) {
            if (!threadMessage?.id) return;

            const isSender = Number(threadMessage.from_user_id) === Number(this.currentUserId);
            const targetUser = isSender ? threadMessage.recipient : threadMessage.sender;

            this.replyTarget = threadMessage;
            this.activeConversationKey = this.roomKeyFromMessage(threadMessage) || this.activeConversationKey;
            this.replyContextDismissed = Boolean(silent);
            this.reply.ticket_id = threadMessage.ticket_id || this.ticket?.id || this.message.ticket_id || '';
            this.reply.to_user_id = targetUser?.id || (isSender ? threadMessage.to_user_id : threadMessage.from_user_id) || '';
            this.reply.to_display = targetUser
                ? `${targetUser.name}${targetUser.email ? ' <' + targetUser.email + '>' : ''}`
                : '-';
            this.reply.subject = this.buildReplySubject();

            if (clearBody) {
                this.reply.body = '';
            }
        },

        openReply(threadMessage = null) {
            this.setReplyTarget(threadMessage || this.replyTarget || this.message, false, false);
            this.activeMenuId = null;
            this.$nextTick(() => {
                const textarea = document.querySelector('textarea[x-model="reply.body"]');
                if (textarea) textarea.focus();
            });
        },

        clearReplyTarget() {
            this.replyContextDismissed = true;
            this.activeMenuId = null;
        },

        buildReplySubject() {
            const ticketTitle = this.ticket?.title || this.message?.ticket?.title || 'Ticket update';
            return `Ticket update: ${ticketTitle}`;
        },

        handleReplyKeydown(event) {
            if (event.shiftKey) {
                return;
            }

            event.preventDefault();
            this.submitReply();
        },

        async submitReply() {
            if (this.submitting) return;
            if (!this.reply.body.trim()) return;

            this.ensureReplyTarget();

            if (!this.reply.ticket_id || !this.reply.to_user_id) {
                this.showAlert('Reply target is missing.', 'error');
                return;
            }

            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('ticket_id', this.reply.ticket_id);
                formData.append('to_user_id', this.reply.to_user_id || '');
                formData.append('subject', this.reply.subject || this.buildReplySubject());
                formData.append('body', this.reply.body || '');

                const res = await fetch('/api/resolver-inbox', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const result = await res.json();

                if (!res.ok || !result.success) {
                    throw new Error(result.message || 'Failed to send reply');
                }

                this.reply.body = '';
                this.replyContextDismissed = true;
                await this.loadMessage();
                this.showAlert(result.message || 'Reply sent successfully', 'success');
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to send reply', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async deleteMessage(threadMessage) {
            if (!threadMessage?.id) return;
            if (!confirm('Delete this message?')) return;

            try {
                const res = await fetch(`/api/resolver-inbox/${threadMessage.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const result = await res.json();

                if (!res.ok || !result.success) {
                    throw new Error(result.message || 'Failed to delete message');
                }

                this.activeMenuId = null;

                if (Number(threadMessage.id) === Number(this.messageId)) {
                    window.location.href = '/resolver-inbox';
                    return;
                }

                await this.loadMessage();
                this.showAlert(result.message || 'Message deleted', 'success');
            } catch (error) {
                console.error(error);
                this.showAlert(error.message || 'Failed to delete message', 'error');
            }
        },

        async copyMessage(threadMessage) {
            const text = threadMessage?.body || '';
            if (!text) return;

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const input = document.createElement('textarea');
                    input.value = text;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    input.remove();
                }

                this.activeMenuId = null;
                this.showAlert('Message copied', 'success');
            } catch (error) {
                console.error(error);
                this.showAlert('Failed to copy message', 'error');
            }
        },

        toggleMessageMenu(messageId) {
            this.activeMenuId = this.activeMenuId === messageId ? null : messageId;
        },

        scrollThreadToBottom() {
            const el = this.$refs.threadScroll;
            if (!el) return;
            el.scrollTop = el.scrollHeight;
        },

        isMine(threadMessage) {
            return Number(threadMessage?.from_user_id) === Number(this.currentUserId);
        },

        participantsLabel(threadMessage) {
            const sender = threadMessage?.sender?.name || 'Unknown';
            const recipient = threadMessage?.recipient?.name || 'Unknown';
            return `${sender} → ${recipient}`;
        },

        conversationTitle() {
            const label = this.ticketLabel(this.ticket || this.message?.ticket);
            const title = this.ticket?.title || this.message?.ticket?.title || 'Resolver Conversation';
            return `${label} — ${title}`;
        },

        replyPlaceholder() {
            return this.reply.to_display
                ? `Write a reply to ${this.reply.to_display}...`
                : 'Write a reply...';
        },

        ticketUrl(ticketId) {
            return window.HenanApp?.routes?.ticketDetail
                ? window.HenanApp.routes.ticketDetail(ticketId)
                : `/tickets/${ticketId}`;
        },

        ticketLabel(ticket) {
            return window.HenanApp?.ticketLabel(ticket) ?? '-';
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

        statusLabel(status) {
            return window.HenanApp?.statusLabel
                ? window.HenanApp.statusLabel(status)
                : (status || '-');
        },

        priorityLabel(priority) {
            return window.HenanApp?.priorityLabel
                ? window.HenanApp.priorityLabel(priority)
                : (priority || '-');
        },

        formatDateTime(value) {
            return window.HenanApp?.formatDateTime
                ? window.HenanApp.formatDateTime(value)
                : (value || '-');
        },

        formatDateTimeShort(value) {
            if (!value) return '-';

            const date = new Date(value);
            const datePart = date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
            });
            const timePart = date.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
            });

            return `${datePart}, ${timePart}`;
        },

        truncate(value, max = 120) {
            const text = String(value || '');
            return text.length > max ? `${text.slice(0, max)}...` : text;
        },
    };
}

window.resolverMessageDetailPage = resolverMessageDetailPage;
export default resolverMessageDetailPage;
