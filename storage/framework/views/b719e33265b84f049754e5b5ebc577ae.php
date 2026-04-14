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

    <div class="p-6 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <div id="page-alert" class="hidden mb-4 p-3 rounded text-sm"></div>

            <div
                x-data="ticketForm({
                    mode: 'create',
                    ticketId: null,
                    submitUrl: '/api/tickets',
                    submitMethod: 'POST',
                })"
                x-init="init()"
            >
                <div class="grid grid-cols-12 gap-6">
                    
                    <div class="col-span-12 lg:col-span-9 space-y-4">
                        <div class="bg-white rounded shadow p-4">
                            <div class="grid grid-cols-3 items-center mb-6">
                                <div></div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold tracking-wide">CREATE TICKET</div>
                                </div>

                                <div class="flex justify-end items-center gap-2">
                                    <button type="button"
                                            class="h-10 px-4 rounded border bg-white text-sm opacity-60 cursor-not-allowed"
                                            disabled>
                                        Save Draft
                                    </button>

                                    <button type="button"
                                            @click="submitForm"
                                            :disabled="submitting"
                                            class="h-10 px-4 rounded bg-slate-900 text-white text-sm disabled:opacity-60">
                                        <span x-show="!submitting">Submit &amp; Route</span>
                                        <span x-show="submitting">Submitting...</span>
                                    </button>
                                </div>
                            </div>

                            
                            <details open class="bg-white rounded shadow overflow-hidden mb-4">
                                <summary class="cursor-pointer select-none px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
                                    <span class="font-semibold">Client Contact</span>
                                    <span class="text-white/80">▾</span>
                                </summary>

                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="text-sm font-medium">Client Name<span class="text-red-500">*</span></label>
                                            <input x-model="client_name" type="text" class="mt-1 w-full border rounded px-3 py-2" placeholder="Client Name">
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium">Client Contact<span class="text-red-500">*</span></label>
                                            <input x-model="client_contact" type="text" class="mt-1 w-full border rounded px-3 py-2" placeholder="Client Contact">
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium">Client Email<span class="text-red-500">*</span></label>
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
                                            <div class="text-sm font-medium mb-2">Priority<span class="text-red-500">*</span></div>
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
                                            <label class="text-sm font-medium">Owner Team<span class="text-red-500">*</span></label>
                                            <select x-model="team" class="mt-1 w-full border rounded px-3 py-2">
                                                <option value="it">IT</option>
                                                <option value="finance">Finance</option>
                                                <option value="compliance">Compliance</option>
                                            </select>
                                        </div>

                                        <div class="lg:col-span-4">
                                            <label class="text-sm font-medium">Category<span class="text-red-500">*</span></label>
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

                                        <div class="lg:col-span-3">
                                            <label class="text-sm font-medium">Issue Type<span class="text-red-500">*</span></label>
                                            <select x-model="issue_type" class="mt-1 w-full border rounded px-3 py-2" :disabled="!category">
                                                <option value="" disabled hidden x-text="category ? 'Select issue type' : 'Select category first'"></option>
                                                <template x-for="it in issueTypes" :key="it.v">
                                                    <option :value="it.v" x-text="it.t"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="lg:col-span-3">
                                            <label class="text-sm font-medium">Platform Type<span class="text-red-500">*</span></label>
                                            <select x-model="platform_type" class="mt-1 w-full border rounded px-3 py-2">
                                                <option value="" disabled hidden>Select platform</option>
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
                                    <span class="font-semibold">Details (Dynamic by Category)</span>
                                    <span class="text-white/80">▾</span>
                                </summary>

                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="text-sm font-medium">Amount <span class="text-xs text-gray-500">[optional]</span></label>
                                            <input x-model="amount" type="text" class="mt-1 w-full border rounded px-3 py-2" placeholder="10,000,000">
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium">Flow Type <span class="text-xs text-gray-500">[optional]</span></label>
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
                                    <span class="font-semibold">Internal Notes &amp; Attachments</span>
                                    <span class="text-white/80">▾</span>
                                </summary>

                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-sm font-medium">Internal CS Notes</label>
                                            <textarea x-model="notes" rows="6" class="mt-1 w-full border rounded px-3 py-2"></textarea>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium">Attachments</label>
                                            <div class="mt-1 border rounded p-3 min-h-[152px] flex flex-col justify-between">
                                                <div class="text-xs text-gray-500">Attach screenshot / evidence</div>
                                                <div class="flex justify-end">
                                                    <input x-ref="attachments" type="file" multiple class="text-sm" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    
                    <div class="col-span-12 lg:col-span-3 space-y-4">
                        <div class="sticky top-4 space-y-4">
                            <div class="bg-white rounded shadow overflow-hidden">
                                <div class="px-4 py-2 bg-slate-100 text-xs font-semibold flex items-center justify-between">
                                    <span>ROUTING PREVIEW <span class="text-red-600">(Live)</span></span>
                                </div>

                                <div class="p-3 text-sm space-y-2">
                                    <div class="bg-white border rounded p-3">
                                        <div class="text-xs text-gray-500">Title:</div>
                                        <div class="font-semibold" x-text="title || '-'"></div>

                                        <div class="mt-2 text-xs text-gray-500">Route to:</div>
                                        <div class="font-semibold uppercase" x-text="team || '-'"></div>

                                        <div class="mt-2 text-xs text-gray-500">Category:</div>
                                        <div class="font-semibold" x-text="category || '-'"></div>

                                        <div class="mt-2 text-xs text-gray-500">Issue Type:</div>
                                        <div class="font-semibold" x-text="issue_type || '-'"></div>
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
                                <div class="px-4 py-2 bg-slate-100 text-xs font-semibold">REQUIRED CHECKLIST (Live)</div>
                                <div class="p-3 text-sm space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">Form status:</span>
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                              :class="isReady ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                              x-text="isReady ? 'Ready to Submit' : 'Incomplete'"></span>
                                    </div>

                                    <template x-if="!isReady">
                                        <div class="text-xs text-gray-600">
                                            Missing:
                                            <span class="font-semibold" x-text="missingFields.join(', ')"></span>
                                        </div>
                                    </template>

                                    <template x-if="isReady">
                                        <div class="text-xs text-gray-600">All required fields are filled ✅</div>
                                    </template>

                                    <div class="pt-2 border-t text-xs text-gray-500">
                                        Attachment is optional (recommended for Critical).
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded shadow overflow-hidden">
                                <div class="px-4 py-2 bg-slate-100 text-xs font-semibold">SIMILAR / DUPLICATE (Auto)</div>
                                <div class="p-3 text-sm">
                                    <div class="text-xs text-gray-500 mb-2">Based on team + category + issue type + title keyword.</div>

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
                                                    <div class="text-xs text-gray-500">
                                                        <span class="uppercase" x-text="tk.team"></span> •
                                                        <span x-text="tk.status"></span>
                                                    </div>
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

                            <div class="bg-white rounded shadow overflow-hidden">
                                <div class="px-4 py-2 bg-slate-100 text-xs font-semibold">CLIENT HISTORY (Mini)</div>
                                <div class="p-3 text-sm space-y-2">
                                    <template x-if="clientLoading">
                                        <div class="text-xs text-gray-500">Loading history…</div>
                                    </template>

                                    <template x-if="!clientLoading">
                                        <div>
                                            <template x-if="clientHistory && clientHistory.supported">
                                                <div class="space-y-2">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Total tickets:</span>
                                                        <b x-text="clientHistory.total"></b>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Last ticket:</span>
                                                        <b x-text="clientHistory.last_ticket_at ? clientHistory.last_ticket_at : '-'"></b>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Last category:</span>
                                                        <b x-text="clientHistory.last_category ? clientHistory.last_category : '-'"></b>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="!clientHistory || !clientHistory.supported">
                                                <div class="text-xs text-gray-500">
                                                    <span x-text="clientHistory?.note ?? 'Client history will appear here.'"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <div class="pt-2 border-t text-xs text-gray-500">
                                        Tip: fill email/contact to enable history lookup.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function ticketForm(config) {
                return {
                    mode: config.mode,
                    ticketId: config.ticketId,
                    submitUrl: config.submitUrl,
                    submitMethod: config.submitMethod,

                    submitting: false,

                    client_name: '',
                    client_contact: '',
                    client_email: '',

                    title: '',
                    description: '',
                    priority: 'medium',
                    team: 'it',
                    category: '',
                    issue_type: '',
                    platform_type: '',

                    amount: '',
                    flow_type: '',
                    request_time: '',
                    notes: '',

                    errors: {},

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

                    get missingFields() {
                        const miss = [];
                        if (!this.title) miss.push('Title');
                        if (!this.description) miss.push('Description');
                        if (!this.priority) miss.push('Priority');
                        if (!this.team) miss.push('Owner Team');
                        if (!this.category) miss.push('Category');
                        if (!this.issue_type) miss.push('Issue Type');
                        if (!this.platform_type) miss.push('Platform');
                        return miss;
                    },

                    get isReady() {
                        return this.missingFields.length === 0;
                    },

                    similarTickets: [],
                    similarLoading: false,

                    clientHistory: { supported:false, total:0, last_ticket_at:null, last_category:null, note:null },
                    clientLoading: false,

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

                    buildPayload() {
                        return {
                            title: this.title,
                            description: this.description,
                            priority: this.priority,
                            team: this.team,
                            category: this.category,
                            issue_type: this.issue_type,
                        };
                    },

                    async submitForm() {
                        this.submitting = true;
                        this.errors = {};

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
                                body: JSON.stringify(this.buildPayload()),
                            });

                            const result = await response.json();

                            if (!response.ok) {
                                if (result.errors) {
                                    this.errors = result.errors;
                                }
                                throw new Error(result.message || 'Failed to save ticket');
                            }

                            this.showAlert(result.message || 'Ticket saved successfully', 'success');

                            setTimeout(() => {
                                window.location.href = '/tickets';
                            }, 800);
                        } catch (error) {
                            console.error(error);
                            this.showAlert(error.message || 'Failed to save ticket', 'error');
                        } finally {
                            this.submitting = false;
                        }
                    },

                    async fetchSimilar() {
                        this.similarLoading = true;

                        const params = new URLSearchParams({
                            q: this.title || '',
                        });

                        try {
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

                    async fetchClientHistory() {
                        this.clientLoading = false;
                        this.clientHistory = {
                            supported: false,
                            total: 0,
                            last_ticket_at: null,
                            last_category: null,
                            note: 'Client history endpoint belum support email/contact di backend sekarang.',
                        };
                    },

                    init() {
                        this.$watch('category', () => {
                            this.issue_type = '';
                            this.fetchSimilar();
                        });

                        let t;
                        this.$watch('title', () => {
                            clearTimeout(t);
                            t = setTimeout(() => this.fetchSimilar(), 300);
                        });

                        this.$watch('team', () => this.fetchSimilar());
                        this.$watch('issue_type', () => this.fetchSimilar());

                        this.fetchSimilar();
                        this.fetchClientHistory();
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
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/tickets/create.blade.php ENDPATH**/ ?>