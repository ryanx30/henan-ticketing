<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        id="resolver-message-detail-page"
        data-message-id="{{ $resolverMessage->id }}"
        data-current-user-id="{{ auth()->id() }}"
        data-is-it="{{ auth()->user()->role === 'it' ? '1' : '0' }}"
        x-data="resolverMessageDetailPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] p-6">

        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        <div class="mx-auto max-w-6xl space-y-6">
            {{-- Top bar --}}
            <div class="flex items-center justify-between">
                <div>
                    <a
                        href="{{ route('resolver-inbox.index') }}"
                        class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Inbox
                    </a>

                    <h1 class="mt-2 text-2xl font-bold text-slate-900">Message Detail</h1>
                    <p class="mt-1 text-sm text-slate-500">Detail pesan resolver inbox dan konteks ticket terkait.</p>
                </div>

                <button
                    type="button"
                    @click="openReply()"
                    class="inline-flex items-center gap-2 rounded-full bg-sky-200 px-5 py-3 text-sm font-semibold text-slate-800 shadow-md transition hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10M3 10l4-4M3 10l4 4M21 21a8 8 0 00-8-8H7" />
                    </svg>
                    Reply
                </button>
            </div>

            <template x-if="loading">
                <div class="rounded-xl border border-slate-200 bg-white px-6 py-10 text-center text-slate-500 shadow-sm">
                    Loading message detail...
                </div>
            </template>

            <template x-if="!loading && !message.id">
                <div class="rounded-xl border border-slate-200 bg-white px-6 py-10 text-center text-slate-500 shadow-sm">
                    Message not found.
                </div>
            </template>

            <div x-show="!loading && message.id" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Left content --}}
                <div class="space-y-6 lg:col-span-2">
                    {{-- Main message --}}
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900" x-text="displayMessageTitle()"></h2>

                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                        <span>Ticket:</span>
                                        <a
                                            :href="message.ticket ? ticketUrl(message.ticket.id) : '#'"
                                            class="font-mono font-semibold text-slate-700 hover:text-blue-600 hover:underline"
                                            x-text="ticketLabel(message.ticket)">
                                        </a>

                                        <span>•</span>
                                        <span>From:</span>
                                        <span class="font-medium text-slate-700" x-text="message.sender?.name || '-'"></span>

                                        <span>•</span>
                                        <span>To:</span>
                                        <span class="font-medium text-slate-700" x-text="message.recipient?.name || '-'"></span>
                                    </div>

                                    <div
                                        class="mt-2 text-xs text-slate-400"
                                        x-show="normalizedStoredSubject()">
                                        Stored subject:
                                        <span class="font-medium text-slate-500" x-text="normalizedStoredSubject()"></span>
                                    </div>
                                </div>

                                <div class="text-sm text-slate-500" x-text="formatDateTime(message.created_at)"></div>
                            </div>
                        </div>

                        <div class="px-6 py-6">
                            <div class="whitespace-pre-line text-[15px] leading-7 text-slate-700" x-text="message.body || '-'"></div>

                            <template x-if="message.attachment_name">
                                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-800">Attachment</div>
                                    <div class="mt-1 text-sm text-slate-600" x-text="message.attachment_name"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Ticket context --}}
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-bold text-slate-900">Related Ticket</h2>
                        </div>

                        <div class="px-6 py-5">
                            <template x-if="message.ticket">
                                <div class="space-y-4">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <a
                                            :href="ticketUrl(message.ticket.id)"
                                            class="font-mono text-base font-semibold text-slate-900 hover:text-blue-600 hover:underline"
                                            x-text="ticketLabel(message.ticket)">
                                        </a>

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                            :class="priorityClass(message.ticket.priority)"
                                            x-text="ucfirst(message.ticket.priority || '-')">
                                        </span>

                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 uppercase"
                                            x-text="message.ticket.team || '-'"></span>
                                    </div>

                                    <div>
                                        <div class="text-sm text-slate-500">Title</div>
                                        <div class="mt-1 font-semibold text-slate-900" x-text="message.ticket.title || '-'"></div>
                                    </div>

                                    <div>
                                        <div class="text-sm text-slate-500">Description</div>
                                        <div class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700" x-text="message.ticket.description || '-'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Right sidebar --}}
                <div class="space-y-6">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-bold text-slate-900">Message Info</h2>
                        </div>

                        <div class="space-y-4 px-6 py-5 text-sm">
                            <div>
                                <div class="text-slate-500">Status</div>
                                <div class="mt-1">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                        :class="message.is_read ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-800'"
                                        x-text="message.is_read ? 'Read' : 'Unread'">
                                    </span>
                                </div>
                            </div>

                            <div>
                                <div class="text-slate-500">Created At</div>
                                <div class="mt-1 font-medium text-slate-800" x-text="formatDateTime(message.created_at)"></div>
                            </div>

                            <div>
                                <div class="text-slate-500">Last Updated</div>
                                <div class="mt-1 font-medium text-slate-800" x-text="formatDateTime(message.updated_at)"></div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-bold text-slate-900">Quick Actions</h2>
                        </div>

                        <div class="space-y-3 px-6 py-5">
                            <button
                                type="button"
                                @click="openReply()"
                                class="inline-flex w-full items-center justify-center rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Reply Message
                            </button>

                            <a
                                :href="message.ticket ? ticketUrl(message.ticket.id) : '#'"
                                class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Open Ticket Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reply Modal --}}
        <div
            x-show="showReply"
            x-transition
            class="fixed inset-0 z-50"
            style="display:none;">
            <div class="absolute inset-0 bg-black/20" @click="showReply = false"></div>

            <div class="fixed bottom-0 right-6 z-50 w-full max-w-xl overflow-hidden rounded-t-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-slate-100 px-5 py-3">
                    <h3 class="text-[18px] font-semibold text-slate-900">Reply Message</h3>

                    <button type="button" @click="discardReply()" class="text-slate-500 hover:text-slate-900">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitReply" class="p-5">
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-16 text-sm text-slate-700">Ticket</span>
                            <div class="w-full text-sm text-slate-800" x-text="ticketLabel(message.ticket)"></div>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-16 text-sm text-slate-700">To</span>
                            <div class="w-full text-sm text-slate-800" x-text="reply.to_display || '-'"></div>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-16 text-sm text-slate-700">Subject</span>
                            <input
                                type="text"
                                x-model="reply.subject"
                                class="w-full border-0 bg-transparent text-sm outline-none"
                                placeholder="Message subject">
                        </div>
                    </div>

                    <div class="py-4">
                        <textarea
                            x-model="reply.body"
                            rows="8"
                            class="w-full resize-none border-0 text-sm outline-none"
                            placeholder="Write your reply..."></textarea>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                        <button
                            type="submit"
                            class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                            :disabled="submitting">
                            <span x-text="submitting ? 'Sending...' : 'Send'"></span>
                        </button>

                        <button
                            type="button"
                            @click="discardReply()"
                            class="text-slate-500 hover:text-red-600">
                            Discard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function resolverMessageDetailPage() {
            return {
                loading: false,
                submitting: false,
                showReply: false,

                messageId: Number(document.getElementById('resolver-message-detail-page')?.dataset.messageId || 0),
                currentUserId: Number(document.getElementById('resolver-message-detail-page')?.dataset.currentUserId || 0),
                isIT: document.getElementById('resolver-message-detail-page')?.dataset.isIt === '1',

                message: {},

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

                async loadMessage() {
                    this.loading = true;

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

                        this.message = result.data || {};
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load message detail', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                openReply() {
                    if (!this.message?.id) return;

                    const isSender = Number(this.message.from_user_id) === this.currentUserId;
                    const targetUser = isSender ? this.message.recipient : this.message.sender;

                    this.reply.ticket_id = this.message.ticket_id || '';
                    this.reply.to_user_id = targetUser?.id || (isSender ? this.message.to_user_id : this.message.from_user_id) || '';
                    this.reply.to_display = targetUser ?
                        `${targetUser.name}${targetUser.email ? ' <' + targetUser.email + '>' : ''}` :
                        '-';
                    this.reply.subject = this.buildReplySubject();
                    this.reply.body = '';
                    this.showReply = true;
                },

                buildReplySubject() {
                    const ticketLabel = this.ticketLabel(this.message.ticket);
                    const ticketTitle = this.message?.ticket?.title || this.normalizedStoredSubject() || 'Message';

                    return `Reply for ${ticketLabel} - ${ticketTitle}`;
                },

                discardReply() {
                    this.reply.ticket_id = '';
                    this.reply.to_user_id = '';
                    this.reply.to_display = '';
                    this.reply.subject = '';
                    this.reply.body = '';
                    this.showReply = false;
                },

                async submitReply() {
                    if (this.submitting) return;
                    if (!this.reply.ticket_id || !this.reply.body) return;

                    this.submitting = true;

                    try {
                        const formData = new FormData();
                        formData.append('ticket_id', this.reply.ticket_id);
                        formData.append('subject', this.reply.subject || '');
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

                        this.showAlert(result.message || 'Reply sent successfully', 'success');
                        this.discardReply();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to send reply', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                ticketUrl(ticketId) {
                    return `/tickets/${ticketId}`;
                },

                ticketLabel(ticket) {
                    return window.HenanApp?.ticketLabel(ticket) ?? '-';
                },

                displayMessageTitle() {
                    if (this.message?.ticket?.title) {
                        return this.message.ticket.title;
                    }

                    const cleaned = this.normalizedStoredSubject();
                    return cleaned || 'No subject';
                },

                normalizedStoredSubject() {
                    const raw = (this.message?.subject || '').trim();
                    if (!raw) return '';

                    return raw.replace(/^Reply for\s+#?T-[A-Za-z0-9-]+\s*-\s*/i, '').trim();
                },

                ucfirst(value) {
                    if (!value) return '-';
                    value = String(value);
                    return value.charAt(0).toUpperCase() + value.slice(1);
                },

                formatDateTime(value) {
                    if (!value) return '-';
                    const date = new Date(value);

                    return date.toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'short',
                        day: '2-digit',
                    }) + ' ' + date.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                    });
                },

                priorityClass(priority) {
                    switch (priority) {
                        case 'critical':
                            return 'bg-red-500 text-white';
                        case 'high':
                            return 'bg-orange-400 text-white';
                        case 'medium':
                            return 'bg-yellow-400 text-slate-900';
                        case 'low':
                            return 'bg-slate-300 text-slate-800';
                        default:
                            return 'bg-slate-300 text-slate-800';
                    }
                }
            }
        }
    </script>
</x-app-layout>