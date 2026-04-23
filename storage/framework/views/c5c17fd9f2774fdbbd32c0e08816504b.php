<?php
$isIT = auth()->user()->role === 'it';
?>

<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <div
        id="resolver-inbox-page"
        data-is-it="<?php echo e(auth()->user()->role === 'it' ? '1' : '0'); ?>"
        data-user-id="<?php echo e(auth()->id()); ?>"
        x-data="resolverInboxPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] p-6">
        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">

            
            <div class="mb-5 flex items-center justify-between">
                <div class="mb-5">
                    <button
                        type="button"
                        @click="openCompose()"
                        class="inline-flex items-center gap-3 rounded-[22px] bg-sky-200 px-6 py-4 text-[16px] font-medium text-slate-800 shadow-md transition hover:shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.1 2.1 0 113.03 2.906L9.5 17l-4 1 1-4 10.362-10.513z" />
                        </svg>
                        Compose
                    </button>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <span class="text-slate-600">Filters:</span>

                    <select x-model="filters.unread" @change="applyFilters()" class="rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="all">All</option>
                        <option value="unread">Unread</option>
                    </select>

                    <select x-model="filters.priority" @change="applyFilters()" class=" w-[100px] rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="all">Priority</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>

                    <select x-model="filters.team" @change="applyFilters()" class="rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="all">Team</option>
                        <option value="it">IT</option>
                        <option value="finance">Finance</option>
                        <option value="compliance">Compliance</option>
                    </select>

                    <select x-model="filters.date" @change="applyFilters()" class="rounded-md border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm">
                        <option value="all">Date</option>
                        <option value="today">Today</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                </div>
            </div>

            
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md">
                <div class="bg-[#001a2c] px-6 py-4 text-2xl font-bold text-white">
                    Messages
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-sm">
                        <thead class="bg-slate-200 text-slate-700">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">Ticket</th>
                                <th class="px-4 py-3 text-left font-semibold"></th>
                                <th class="px-4 py-3 text-left font-semibold">Priority</th>
                                <th class="px-4 py-3 text-left font-semibold">Description</th>
                                <th class="px-4 py-3 text-right font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                        Loading messages...
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && messages.length === 0">
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                        No messages found.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="message in messages" :key="message.id">
                                <tr
                                    class="group cursor-pointer border-t border-slate-200 odd:bg-white even:bg-slate-100 hover:bg-slate-50"
                                    @click="window.location = `/resolver-inbox/${message.id}`">
                                    <td class="px-5 py-4 font-medium text-slate-800">
                                        <span x-text="'#T-' + (message.ticket?.ticket_code ?? message.ticket?.id ?? '-')"></span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <template x-if="!message.is_read && message.to_user_id === currentUserId">
                                            <span class="inline-flex rounded-md bg-slate-300 px-3 py-1 text-xs font-bold text-white">
                                                NEW
                                            </span>
                                        </template>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                            :class="priorityClass(message.ticket?.priority)"
                                            x-text="ucfirst(message.ticket?.priority ?? '-')">
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 text-base text-slate-800">
                                        <span x-text="truncate(message.subject || message.body || '-', 70)"></span>
                                    </td>

                                    <td class="px-4 py-4 text-right">
                                        <div class="relative flex justify-end">
                                            <div class="text-slate-600 group-hover:hidden" x-text="formatTime(message.created_at)"></div>

                                            <div class="hidden items-center gap-3 group-hover:flex">
                                                
                                                <button
                                                    type="button"
                                                    @click.stop="replyMessage(message)"
                                                    class="text-slate-500 hover:text-slate-800"
                                                    title="Reply">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10M3 10l4-4M3 10l4 4M21 21a8 8 0 00-8-8H7" />
                                                    </svg>
                                                </button>

                                                
                                                <template x-if="!message.is_read && message.to_user_id === currentUserId">
                                                    <button
                                                        type="button"
                                                        @click.stop="markAsRead(message.id)"
                                                        class="text-slate-500 hover:text-slate-800"
                                                        title="Mark as Read">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                </template>

                                                
                                                <button
                                                    type="button"
                                                    @click.stop="deleteMessage(message.id)"
                                                    class="text-slate-500 hover:text-red-600"
                                                    title="Delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 3v7m4-7v7m4-7v7M7 7l1 12h8l1-12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-5 py-4" x-show="meta.last_page > 1">
                    <div class="flex flex-wrap items-center gap-2">
                        <template x-for="page in meta.last_page" :key="page">
                            <button
                                type="button"
                                @click="goToPage(page)"
                                class="rounded border px-3 py-1 text-sm"
                                :class="page === meta.current_page ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50'">
                                <span x-text="page"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        
        <div
            x-show="showCompose"
            x-transition
            class="fixed inset-0 z-50"
            style="display: none;">
            <div class="pointer-events-none absolute inset-0 bg-transparent"></div>

            <div class="pointer-events-auto fixed bottom-0 right-6 z-50 w-full max-w-xl overflow-hidden rounded-t-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-slate-100 px-5 py-3">
                    <h3 class="text-[18px] font-semibold text-slate-900">New Message</h3>

                    <div class="flex items-center gap-4 text-slate-500">
                        <button type="button" @click="showCompose = false">—</button>
                        <button type="button" @click="discardDraft()">✕</button>
                    </div>
                </div>

                <form @submit.prevent="submitCompose" class="p-5">
                    <input type="hidden" x-model="form.to_user_id">

                    
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Ticket</span>

                            <select
                                x-model="form.ticket_id"
                                @change="syncTicketMeta()"
                                class="w-full border-0 bg-transparent text-sm outline-none">
                                <option value="">Choose Ticket</option>
                                <template x-for="ticket in composeTickets" :key="ticket.id">
                                    <option
                                        :value="ticket.id"
                                        x-text="`#T-${ticket.ticket_code ?? ticket.id} - ${ticket.title}`">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>

                    
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">To</span>

                            <input
                                type="text"
                                x-model="form.to_display"
                                readonly
                                class="w-full border-0 bg-transparent text-sm text-slate-800 outline-none"
                                placeholder="<?php echo e($isIT ? 'Auto-filled from ticket creator' : 'Auto-filled from ticket holder'); ?>">
                        </div>
                    </div>

                    
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Subject</span>
                            <input
                                type="text"
                                x-model="form.subject"
                                class="w-full border-0 bg-transparent text-sm outline-none"
                                placeholder="Message subject">
                        </div>
                    </div>

                    
                    <div class="py-4">
                        <textarea
                            x-model="form.body"
                            rows="8"
                            class="w-full resize-none border-0 text-sm outline-none"
                            placeholder="Write your message..."></textarea>
                    </div>

                    
                    <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                        <div class="flex items-center gap-3">
                            <button type="submit" class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                Send
                            </button>

                            <div class="group relative">
                                <label class="cursor-pointer text-slate-600 hover:text-slate-900">
                                    <input type="file" @change="handleAttachment" class="hidden">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                    </svg>
                                </label>

                                <div class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
                                    Attach file
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <button
                                type="button"
                                @click="discardDraft()"
                                class="text-slate-500 hover:text-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 3v7m4-7v7m4-7v7M7 7l1 12h8l1-12" />
                                </svg>
                            </button>

                            <div class="pointer-events-none absolute -top-9 right-0 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
                                Discard draft
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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

                    if (!this.form.subject) {
                        this.form.subject = `Reply for #T-${ticket.ticket_code ?? ticket.id} - ${ticket.title}`;
                    }
                },

                replyMessage(message) {
                    this.showCompose = true;
                    this.form.ticket_id = message.ticket_id || '';
                    this.form.subject = message.subject || `Reply for #T-${message.ticket?.ticket_code ?? message.ticket?.id ?? ''}`;
                    this.syncTicketMeta();
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
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/resolver-inbox/index.blade.php ENDPATH**/ ?>