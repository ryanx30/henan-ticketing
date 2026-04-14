<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $ticketId = $ticket->id ?? request()->route('ticket');
    @endphp

    <div class="p-6 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <div id="page-alert" class="hidden mb-4 p-3 rounded text-sm"></div>

            <div
                x-data="ticketEditForm({
                    ticketId: {{ (int) $ticketId }},
                    loadUrl: '/api/tickets/{{ (int) $ticketId }}',
                    submitUrl: '/api/tickets/{{ (int) $ticketId }}',
                    submitMethod: 'PATCH',
                })"
                x-init="init()"
            >
                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-12 lg:col-span-9 space-y-4">
                        <div class="bg-white rounded shadow p-4">
                            <div class="grid grid-cols-3 items-center mb-6">
                                <div>
                                    <a href="/tickets"
                                       class="inline-flex items-center px-4 py-2 rounded border bg-white text-sm hover:bg-slate-50">
                                        ← Back
                                    </a>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold tracking-wide">OPEN TICKET</div>
                                    <div class="text-xs text-gray-500 mt-1" x-text="ticket_code ? '#T-' + ticket_code : 'Loading...'"></div>
                                </div>

                                <div class="flex justify-end items-center gap-2">
                                    <button type="button"
                                            @click="submitForm"
                                            :disabled="submitting || loading"
                                            class="h-10 px-4 rounded bg-slate-900 text-white text-sm disabled:opacity-60">
                                        <span x-show="!submitting">Update Ticket</span>
                                        <span x-show="submitting">Updating...</span>
                                    </button>
                                </div>
                            </div>

                            <template x-if="loading">
                                <div class="p-6 text-center text-gray-500">Loading ticket...</div>
                            </template>

                            <template x-if="!loading">
                                <div>
                                    <details open class="bg-white rounded shadow overflow-hidden mb-4">
                                        <summary class="cursor-pointer select-none px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                                            <span class="font-semibold">Client Contact</span>
                                            <span class="text-white/80">▾</span>
                                        </summary>

                                        <div class="p-4">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="text-sm font-medium">Client Name</label>
                                                    <input x-model="client_name" type="text" class="mt-1 w-full border rounded px-3 py-2" placeholder="Client Name">
                                                </div>

                                                <div>
                                                    <label class="text-sm font-medium">Client Contact</label>
                                                    <input x-model="client_contact" type="text" class="mt-1 w-full border rounded px-3 py-2" placeholder="Client Contact">
                                                </div>

                                                <div>
                                                    <label class="text-sm font-medium">Client Email</label>
                                                    <input x-model="client_email" type="email" class="mt-1 w-full border rounded px-3 py-2" placeholder="lorem@gmail.com">
                                                </div>
                                            </div>
                                        </div>
                                    </details>

                                    <details open class="bg-white rounded shadow overflow-hidden mb-4">
                                        <summary class="cursor-pointer select-none px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                                            <span class="font-semibold">Issue Summary</span>
                                            <span class="text-white/80">▾</span>
                                        </summary>

                                        <div class="p-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="text-sm font-medium">Title</label>
                                                    <input x-model="title" class="mt-1 w-full border rounded px-3 py-2" placeholder="Title" />
                                                </div>

                                                <div>
                                                    <label class="text-sm font-medium">Description</label>
                                                    <input x-model="description" class="mt-1 w-full border rounded px-3 py-2" placeholder="Description" />
                                                </div>
                                            </div>
                                        </div>
                                    </details>

                                    <details open class="bg-white rounded shadow overflow-hidden mb-4">
                                        <summary class="cursor-pointer select-none px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                                            <span class="font-semibold">Classification &amp; Routing</span>
                                            <span class="text-white/80">▾</span>
                                        </summary>

                                        <div class="p-4">
                                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                                                <div class="lg:col-span-3">
                                                    <div class="text-sm font-medium mb-2">Priority</div>
                                                    <div class="border rounded p-3">
                                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                                            <label class="flex items-center gap-2">
                                                                <input type="radio" value="critical" x-model="priority">
                                                                <span>Critical</span>
                                                            </label>
                                                            <label class="flex items-center gap-2">
                                                                <input type="radio" value="medium" x-model="priority">
                                                                <span>Medium</span>
                                                            </label>
                                                            <label class="flex items-center gap-2">
                                                                <input type="radio" value="high" x-model="priority">
                                                                <span>High</span>
                                                            </label>
                                                            <label class="flex items-center gap-2">
                                                                <input type="radio" value="low" x-model="priority">
                                                                <span>Low</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label class="text-sm font-medium">Owner Team</label>
                                                    <select x-model="team" class="mt-1 w-full border rounded px-3 py-2">
                                                        <option value="it">IT</option>
                                                        <option value="finance">Finance</option>
                                                        <option value="compliance">Compliance</option>
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label class="text-sm font-medium">Status</label>
                                                    <select x-model="status" class="mt-1 w-full border rounded px-3 py-2">
                                                        <option value="new">New</option>
                                                        <option value="in_progress">On Going</option>
                                                        <option value="waiting_info">Waiting Info</option>
                                                        <option value="resolved">Resolved</option>
                                                        <option value="closed">Closed</option>
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-3">
                                                    <label class="text-sm font-medium">Category</label>
                                                    <select x-model="category" class="mt-1 w-full border rounded px-3 py-2">
                                                        <option value="" disabled hidden>Select category</option>
                                                        <option value="account_access">Account &amp; Access</option>
                                                        <option value="kyc_compliance">KYC &amp; Compliance</option>
                                                        <option value="trading_orders">Trading &amp; Orders</option>
                                                        <option value="funds">Funds (Deposit/Withdraw)</option>
                                                        <option value="portfolio_reports">Portfolio &amp; Reports</option>
                                                        <option value="app_technical">App &amp; Technical</option>
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label class="text-sm font-medium">Issue Type</label>
                                                    <select x-model="issue_type" class="mt-1 w-full border rounded px-3 py-2" :disabled="!category">
                                                        <option value="" disabled hidden x-text="category ? 'Select issue type' : 'Select category first'"></option>
                                                        <template x-for="it in issueTypes" :key="it.v">
                                                            <option :value="it.v" x-text="it.t"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-3">
                                                    <label class="text-sm font-medium">Platform Type</label>
                                                    <select x-model="platform_type" class="mt-1 w-full border rounded px-3 py-2">
                                                        <option value="">Select platform</option>
                                                        <option value="web">Web</option>
                                                        <option value="windows">Windows</option>
                                                        <option value="macos">macOS</option>
                                                        <option value="android">Android</option>
                                                        <option value="ios">iOS</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </details>

                                    <details open class="bg-white rounded shadow overflow-hidden mb-4">
                                        <summary class="cursor-pointer select-none px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                                            <span class="font-semibold">Details</span>
                                            <span class="text-white/80">▾</span>
                                        </summary>

                                        <div class="p-4">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="text-sm font-medium">Amount</label>
                                                    <input x-model="amount" type="text" class="mt-1 w-full border rounded px-3 py-2">
                                                </div>

                                                <div>
                                                    <label class="text-sm font-medium">Flow Type</label>
                                                    <select x-model="flow_type" class="mt-1 w-full border rounded px-3 py-2">
                                                        <option value="">None</option>
                                                        <option value="withdraw">Withdraw</option>
                                                        <option value="deposit">Deposit</option>
                                                        <option value="transfer">Transfer</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="text-sm font-medium">Request Time</label>
                                                    <input x-model="request_time" type="datetime-local" class="mt-1 w-full border rounded px-3 py-2">
                                                </div>
                                            </div>
                                        </div>
                                    </details>

                                    <details open class="bg-white rounded shadow overflow-hidden mb-4">
                                        <summary class="cursor-pointer select-none px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                                            <span class="font-semibold">Internal Notes</span>
                                            <span class="text-white/80">▾</span>
                                        </summary>

                                        <div class="p-4">
                                            <label class="text-sm font-medium">Internal CS Notes</label>
                                            <textarea x-model="notes" rows="6" class="mt-1 w-full border rounded px-3 py-2"></textarea>
                                        </div>
                                    </details>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="col-span-12 lg:col-span-3 space-y-4">
                        <div class="sticky top-4 space-y-4">
                            <div class="bg-white rounded shadow overflow-hidden">
                                <div class="px-4 py-2 bg-slate-100 text-xs font-semibold">TICKET INFO</div>
                                <div class="p-3 text-sm space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Ticket Code:</span>
                                        <b x-text="ticket_code ? '#T-' + ticket_code : '-'"></b>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Status:</span>
                                        <b x-text="status || '-'"></b>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Created By:</span>
                                        <b x-text="creator_name || '-'"></b>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Holder:</span>
                                        <b x-text="holder_name || '-'"></b>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded shadow overflow-hidden">
                                <div class="px-4 py-2 bg-slate-100 text-xs font-semibold">SLA PREVIEW</div>
                                <div class="p-3 text-sm space-y-2">
                                    <div class="bg-white border rounded p-3">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Response:</span>
                                            <b x-text="sla.response"></b>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Resolve:</span>
                                            <b x-text="sla.resolve"></b>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded shadow overflow-hidden">
                                <div class="px-4 py-2 bg-slate-100 text-xs font-semibold">SIMILAR / DUPLICATE</div>
                                <div class="p-3 text-sm">
                                    <template x-if="similarLoading">
                                        <div class="text-xs text-gray-500">Searching…</div>
                                    </template>

                                    <template x-if="!similarLoading && (!similarTickets || similarTickets.length === 0)">
                                        <div class="text-xs text-gray-500">No similar tickets found.</div>
                                    </template>

                                    <div class="space-y-2" x-show="!similarLoading && similarTickets && similarTickets.length">
                                        <template x-for="tk in similarTickets" :key="tk.id">
                                            <div class="border rounded p-2 flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="text-xs font-mono text-slate-700">
                                                        <span x-text="'#T-' + (tk.ticket_code ?? tk.id)"></span>
                                                    </div>
                                                    <div class="text-sm font-semibold truncate" x-text="tk.title"></div>
                                                </div>

                                                <a class="shrink-0 px-3 py-1 rounded border text-xs bg-white hover:bg-slate-50"
                                                   :href="`/tickets/${tk.id}/edit`">
                                                    Open
                                                </a>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function ticketEditForm(config) {
                return {
                    ticketId: config.ticketId,
                    loadUrl: config.loadUrl,
                    submitUrl: config.submitUrl,
                    submitMethod: config.submitMethod,

                    loading: true,
                    submitting: false,

                    ticket_code: '',
                    creator_name: '',
                    holder_name: '',

                    client_name: '',
                    client_contact: '',
                    client_email: '',

                    title: '',
                    description: '',
                    priority: 'medium',
                    team: 'it',
                    status: 'new',
                    category: '',
                    issue_type: '',
                    platform_type: '',
                    amount: '',
                    flow_type: '',
                    request_time: '',
                    notes: '',

                    slaMap: {
                        critical: { response: '1hr', resolve: '2hr' },
                        high:     { response: '2hr', resolve: '6hr' },
                        medium:   { response: '4hr', resolve: '12hr' },
                        low:      { response: '8hr', resolve: '24hr' },
                    },

                    issueMap: {
                        account_access: [
                            {v:'login_auth', t:'Login / Auth'},
                            {v:'otp_verification', t:'OTP / Verification'},
                            {v:'reset_password', t:'Reset Password / Account Locked'},
                            {v:'session_device', t:'Device / Session Issue'},
                        ],
                        kyc_compliance: [
                            {v:'kyc_pending', t:'KYC Pending'},
                            {v:'kyc_rejected', t:'KYC Rejected'},
                            {v:'profile_update', t:'Update Identity/Profile'},
                            {v:'rdn_setup', t:'RDN Setup / Activation'},
                            {v:'bank_account_change', t:'Bank Account / Withdrawal Bank Change'},
                        ],
                        trading_orders: [
                            {v:'order_rejected', t:'Order Rejected / Failed'},
                            {v:'order_pending', t:'Order Pending / Stuck'},
                            {v:'order_status_mismatch', t:'Order Status Mismatch'},
                            {v:'cancel_amend_issue', t:'Cancel / Amend Issue'},
                            {v:'trading_hours', t:'Trading Hours / Market'},
                        ],
                        funds: [
                            {v:'deposit_not_reflected', t:'Deposit Not Reflected'},
                            {v:'withdraw_pending', t:'Withdraw Pending / Failed'},
                            {v:'rdn_transfer_delay', t:'RDN Transfer Delay'},
                            {v:'fee_dispute', t:'Fee / Charges Dispute'},
                        ],
                        portfolio_reports: [
                            {v:'portfolio_mismatch', t:'Portfolio / Holdings Mismatch'},
                            {v:'pnl_wrong', t:'Avg Price / P&L Wrong'},
                            {v:'corporate_action', t:'Corporate Action'},
                            {v:'statement_report', t:'Statement / Report Download'},
                        ],
                        app_technical: [
                            {v:'app_crash', t:'App Crash'},
                            {v:'performance_slow', t:'Performance / Slow'},
                            {v:'notification_issue', t:'Notification Issue'},
                            {v:'ui_bug', t:'UI Bug / Display Wrong'},
                        ],
                    },

                    get sla() {
                        return this.slaMap[this.priority] || { response:'-', resolve:'-' };
                    },

                    get issueTypes() {
                        return this.issueMap[this.category] || [];
                    },

                    similarTickets: [],
                    similarLoading: false,

                    showAlert(message, type = 'success') {
                        const el = document.getElementById('page-alert');
                        el.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                        el.textContent = message;
                        if (type === 'success') {
                            el.classList.add('bg-green-100', 'text-green-800');
                        } else {
                            el.classList.add('bg-red-100', 'text-red-800');
                        }
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },

                    async loadTicket() {
                        this.loading = true;
                        try {
                            const response = await fetch(this.loadUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();

                            if (!response.ok) {
                                throw new Error(result.message || 'Failed to load ticket');
                            }

                            const t = result.data;

                            this.ticket_code = t.ticket_code || '';
                            this.title = t.title || '';
                            this.description = t.description || '';
                            this.priority = t.priority || 'medium';
                            this.team = t.team || 'it';
                            this.status = t.status || 'new';
                            this.category = t.category || '';
                            this.issue_type = t.issue_type || '';

                            this.creator_name = t.creator?.name || '-';
                            this.holder_name = t.holder?.name || '-';
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to load ticket', 'error');
                        } finally {
                            this.loading = false;
                        }
                    },

                    async submitForm() {
                        this.submitting = true;

                        try {
                            const response = await fetch(this.submitUrl, {
                                method: this.submitMethod,
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    title: this.title,
                                    description: this.description,
                                    priority: this.priority,
                                    team: this.team,
                                    status: this.status,
                                    category: this.category,
                                    issue_type: this.issue_type,
                                }),
                            });

                            const result = await response.json();

                            if (!response.ok) {
                                throw new Error(result.message || 'Failed to update ticket');
                            }

                            this.showAlert(result.message || 'Ticket updated successfully', 'success');

                            setTimeout(() => {
                                window.location.href = '/tickets';
                            }, 800);
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to update ticket', 'error');
                        } finally {
                            this.submitting = false;
                        }
                    },

                    async fetchSimilar() {
                        this.similarLoading = true;
                        try {
                            const params = new URLSearchParams({ q: this.title || '' });
                            const res = await fetch(`/api/tickets-similar?${params.toString()}`, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin'
                            });
                            const result = await res.json();
                            this.similarTickets = result.data || [];
                        } catch (e) {
                            this.similarTickets = [];
                        } finally {
                            this.similarLoading = false;
                        }
                    },

                    init() {
                        this.loadTicket();

                        this.$watch('category', () => {
                            this.issue_type = '';
                            this.fetchSimilar();
                        });

                        let t;
                        this.$watch('title', () => {
                            clearTimeout(t);
                            t = setTimeout(() => this.fetchSimilar(), 300);
                        });
                    }
                }
            }
        </script>
</x-app-layout>