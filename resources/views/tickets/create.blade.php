<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        x-data="createTicketPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7"
    >
        <div class="mx-auto grid w-full max-w-[1600px] grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            {{-- MAIN FORM --}}
            <div class="rounded bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

                <div class="mb-5 flex items-center justify-between">
                    <h1 class="text-[30px] font-bold text-slate-900">CREATE TICKET</h1>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                            @click="saveDraft()"
                        >
                            Save Draft
                        </button>

                        <button
                            type="button"
                            class="rounded bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitDisabled() || submitting"
                            @click="submitTicket()"
                        >
                            <span x-text="submitting ? 'Submitting...' : 'Submit & Route'"></span>
                        </button>
                    </div>
                </div>

                {{-- CLIENT CONTACT --}}
                <div class="mb-4 overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between bg-slate-900 px-4 py-3 text-left text-sm font-semibold text-white"
                        @click="sections.client = !sections.client"
                    >
                        <span>Client Contact</span>
                        <span x-text="sections.client ? '▾' : '▸'"></span>
                    </button>

                    <div x-show="sections.client" x-collapse class="grid grid-cols-1 gap-4 p-4 md:grid-cols-3">
                        <div id="field-client_name">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Client Name*</label>
                            <input
                                type="text"
                                x-model="form.client_name"
                                placeholder="Client Name"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            />
                        </div>

                        <div id="field-client_contact">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Client Contact*</label>
                            <input
                                type="text"
                                x-model="form.client_contact"
                                placeholder="Client Contact"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Next improvement: auto lookup by email/contact for faster matching.</p>
                        </div>

                        <div id="field-client_email">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Client Email*</label>
                            <input
                                type="email"
                                x-model="form.client_email"
                                placeholder="lorem@gmail.com"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                {{-- ISSUE SUMMARY --}}
                <div class="mb-4 overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between bg-slate-900 px-4 py-3 text-left text-sm font-semibold text-white"
                        @click="sections.summary = !sections.summary"
                    >
                        <span>Issue Summary</span>
                        <span x-text="sections.summary ? '▾' : '▸'"></span>
                    </button>

                    <div x-show="sections.summary" x-collapse class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                        <div id="field-title">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Title*</label>
                            <input
                                type="text"
                                x-model="form.title"
                                placeholder="Title"
                                @input.debounce.500ms="loadSimilarTickets()"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            />
                        </div>

                        <div id="field-description">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Description*</label>
                            <textarea
                                x-model="form.description"
                                placeholder="Description"
                                rows="1"
                                @input.debounce.500ms="loadSimilarTickets()"
                                class="min-h-[40px] w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                {{-- CLASSIFICATION & ROUTING --}}
                <div class="mb-4 overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between bg-slate-900 px-4 py-3 text-left text-sm font-semibold text-white"
                        @click="sections.routing = !sections.routing"
                    >
                        <span>Classification & Routing</span>
                        <span x-text="sections.routing ? '▾' : '▸'"></span>
                    </button>

                    <div x-show="sections.routing" x-collapse class="grid grid-cols-1 gap-4 p-4 md:grid-cols-4">
                        <div id="field-priority">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Priority*</label>
                            <div class="grid grid-cols-2 gap-2 rounded border border-slate-300 p-3 text-sm text-slate-700">
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="critical" x-model="form.priority">
                                    <span>Critical</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="medium" x-model="form.priority">
                                    <span>Medium</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="high" x-model="form.priority">
                                    <span>High</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" value="low" x-model="form.priority">
                                    <span>Low</span>
                                </label>
                            </div>
                        </div>

                        <div id="field-team">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Owner Team*</label>
                            <select
                                x-model="form.team"
                                @change="loadSimilarTickets()"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="it">IT</option>
                                <option value="finance">Finance</option>
                                <option value="compliance">Compliance</option>
                            </select>
                        </div>

                        <div id="field-category">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Category*</label>
                            <select
                                x-model="form.category"
                                @change="onCategoryChange()"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="">Select category</option>
                                <template x-for="cat in categoryOptions" :key="cat.value">
                                    <option :value="cat.value" x-text="cat.label"></option>
                                </template>
                            </select>
                        </div>

                        <div id="field-issue_type">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Issue Type*</label>
                            <select
                                x-model="form.issue_type"
                                :disabled="!form.category"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 disabled:cursor-not-allowed disabled:bg-slate-100 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="" x-text="form.category ? 'Select issue type' : 'Select category first'"></option>
                                <template x-for="item in availableIssueTypes()" :key="item.value">
                                    <option :value="item.value" x-text="item.label"></option>
                                </template>
                            </select>
                        </div>

                        <div id="field-platform" class="md:col-span-1">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Platform Type*</label>
                            <select
                                x-model="form.platform_type"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="">Select platform</option>
                                <option value="web">Web</option>
                                <option value="desktop">Desktop</option>
                                <option value="mobile">Mobile</option>
                                <option value="email">Email</option>
                                <option value="network">Network</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- DETAILS --}}
                <div class="mb-4 overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between bg-slate-900 px-4 py-3 text-left text-sm font-semibold text-white"
                        @click="sections.details = !sections.details"
                    >
                        <span>Details (Dynamic by Category)</span>
                        <span x-text="sections.details ? '▾' : '▸'"></span>
                    </button>

                    <div x-show="sections.details" x-collapse class="grid grid-cols-1 gap-4 p-4 md:grid-cols-3">
                        <template x-if="showField('amount')">
                            <div id="field-amount">
                                <label class="mb-2 block text-xs font-semibold text-slate-700">Amount <span class="text-slate-400">(optional)</span></label>
                                <input
                                    type="text"
                                    x-model="form.amount"
                                    placeholder="10,000,000"
                                    class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                                />
                            </div>
                        </template>

                        <template x-if="showField('flow_type')">
                            <div id="field-flow_type">
                                <label class="mb-2 block text-xs font-semibold text-slate-700">Flow Type <span class="text-slate-400">(optional)</span></label>
                                <select
                                    x-model="form.flow_type"
                                    class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                    <option value="">None</option>
                                    <option value="approval">Approval</option>
                                    <option value="request">Request</option>
                                    <option value="incident">Incident</option>
                                </select>
                            </div>
                        </template>

                        <template x-if="showField('request_time')">
                            <div id="field-request_time">
                                <label class="mb-2 block text-xs font-semibold text-slate-700">Request Time</label>
                                <input
                                    type="datetime-local"
                                    x-model="form.request_time"
                                    class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                                />
                            </div>
                        </template>
                    </div>
                </div>

                {{-- NOTES & ATTACHMENTS --}}
                <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between bg-slate-900 px-4 py-3 text-left text-sm font-semibold text-white"
                        @click="sections.notes = !sections.notes"
                    >
                        <span>Internal Notes & Attachments</span>
                        <span x-text="sections.notes ? '▾' : '▸'"></span>
                    </button>

                    <div x-show="sections.notes" x-collapse class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                        <div id="field-internal_notes">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Internal CS Notes</label>
                            <textarea
                                x-model="form.internal_notes"
                                rows="6"
                                class="w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            ></textarea>
                            <p class="mt-1 text-[11px] text-slate-500">Visible for internal handling only. Do not place client-facing summary here.</p>
                        </div>

                        <div id="field-attachments">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Attachments</label>
                            <div class="rounded border border-slate-300 p-4">
                                <div class="mb-3 text-xs text-slate-500">
                                    Attach screenshot / evidence. Recommended for critical issues or UI-related problems.
                                </div>
                                <div class="mb-2 text-[11px] text-slate-400">
                                    Accepted guidance: image or document evidence, max size 5 MB.
                                </div>
                                <input type="file" @change="onAttachmentChange($event)" class="text-sm text-slate-700" />
                                <template x-if="attachmentName">
                                    <div class="mt-2 text-xs text-slate-600">
                                        Selected: <span class="font-medium" x-text="attachmentName"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT SIDEBAR --}}
            <div class="space-y-4">
                {{-- ROUTING PREVIEW --}}
                <div class="rounded border border-slate-200 bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="mb-3 text-xs font-bold text-slate-900">
                        ROUTING PREVIEW <span class="text-red-500">(Live)</span>
                    </div>

                    <div class="space-y-3 rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <div>
                            <div class="mb-1 text-[11px] font-semibold text-slate-500">Title:</div>
                            <div x-text="form.title || '-'"></div>
                        </div>

                        <div>
                            <div class="mb-1 text-[11px] font-semibold text-slate-500">Route to:</div>
                            <div x-text="routeToLabel()"></div>
                        </div>

                        <div>
                            <div class="mb-1 text-[11px] font-semibold text-slate-500">IT Category:</div>
                            <div x-text="previewCategoryLabel()"></div>
                        </div>

                        <div>
                            <div class="mb-1 text-[11px] font-semibold text-slate-500">Issue Type:</div>
                            <div x-text="previewIssueTypeLabel()"></div>
                        </div>
                    </div>
                </div>

                {{-- SLA PREVIEW --}}
                <div class="rounded border border-slate-200 bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="mb-3 text-xs font-bold text-slate-900">SLA PREVIEW</div>

                    <div class="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <div class="mb-2 flex items-center justify-between">
                            <span>Response:</span>
                            <span class="font-bold text-slate-900" x-text="slaPreview().response"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Resolve:</span>
                            <span class="font-bold text-slate-900" x-text="slaPreview().resolve"></span>
                        </div>
                    </div>
                </div>

                {{-- REQUIRED CHECKLIST --}}
                <div class="rounded border border-slate-200 bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="mb-3 text-xs font-bold text-slate-900">
                        REQUIRED CHECKLIST <span class="text-red-500">(Live)</span>
                    </div>

                    <div class="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <div class="mb-3 flex items-center justify-between">
                            <span>Form status:</span>
                            <span
                                class="rounded px-2 py-1 text-[10px] font-bold"
                                :class="isFormReady() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                x-text="isFormReady() ? 'Ready' : 'Incomplete'"
                            ></span>
                        </div>

                        <template x-if="missingFields().length > 0">
                            <div>
                                <div class="mb-2 text-[11px] font-semibold text-slate-500">Missing:</div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="item in missingFields()" :key="item.key">
                                        <button
                                            type="button"
                                            class="rounded bg-white px-2 py-1 text-[11px] text-slate-700 underline hover:bg-slate-100"
                                            @click="scrollToField(item.key)"
                                            x-text="item.label"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="missingFields().length === 0">
                            <div class="text-[11px] text-green-700">All required fields are completed.</div>
                        </template>
                    </div>
                </div>

                {{-- SIMILAR / DUPLICATE --}}
                <div class="rounded border border-slate-200 bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="mb-3 text-xs font-bold text-slate-900">SIMILAR / DUPLICATE <span class="text-slate-500">(Auto)</span></div>

                    <div class="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <div class="mb-3 text-[11px]">
                            Based on team + category + title keyword.
                        </div>

                        <template x-if="similarLoading">
                            <div class="text-[11px] text-slate-500">Checking similar tickets...</div>
                        </template>

                        <template x-if="!similarLoading && similarTickets.length === 0">
                            <div class="text-[11px] text-slate-500">No similar tickets found.</div>
                        </template>

                        <div class="space-y-2" x-show="!similarLoading && similarTickets.length > 0">
                            <template x-for="item in similarTickets" :key="item.id">
                                <div class="rounded border border-slate-200 bg-white p-2">
                                    <div class="font-semibold text-slate-800" x-text="item.ticket_code || '-'"></div>
                                    <div class="mt-1 text-[11px] text-slate-600" x-text="item.title || '-'"></div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <a
                                            :href="`/tickets/${item.id}`"
                                            class="rounded border border-slate-300 bg-white px-2 py-1 text-[10px] font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            View
                                        </a>
                                        <button
                                            type="button"
                                            class="rounded border border-slate-300 bg-white px-2 py-1 text-[10px] font-medium text-slate-700 hover:bg-slate-50"
                                            @click="continueAnyway()"
                                        >
                                            Continue Anyway
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- CLIENT HISTORY --}}
                <div class="rounded border border-slate-200 bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                    <div class="mb-3 text-xs font-bold text-slate-900">CLIENT HISTORY <span class="text-slate-500">(Mini)</span></div>

                    <div class="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <div class="text-[11px]">
                            Client history endpoint currently supports creator-based lookup from backend.
                        </div>
                        <div class="mt-2 text-[11px] text-slate-500">
                            Next step: add lookup by email/contact to enable client history preview here.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function createTicketPage() {
            return {
                submitting: false,
                similarLoading: false,
                attachmentName: '',
                similarTickets: [],
                sections: {
                    client: true,
                    summary: true,
                    routing: true,
                    details: true,
                    notes: true,
                },

                form: {
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
                    internal_notes: '',
                    attachment: null,
                },

                categoryOptions: [
                    { value: 'access', label: 'Access' },
                    { value: 'incident', label: 'Incident' },
                    { value: 'request', label: 'Request' },
                    { value: 'finance_ops', label: 'Finance Ops' },
                ],

                issueTypeMap: {
                    access: [
                        { value: 'login_issue', label: 'Login Issue' },
                        { value: 'permission_change', label: 'Permission Change' },
                        { value: 'account_unlock', label: 'Account Unlock' },
                    ],
                    incident: [
                        { value: 'system_error', label: 'System Error' },
                        { value: 'slow_performance', label: 'Slow Performance' },
                        { value: 'service_down', label: 'Service Down' },
                    ],
                    request: [
                        { value: 'new_feature', label: 'New Feature' },
                        { value: 'data_request', label: 'Data Request' },
                        { value: 'configuration', label: 'Configuration' },
                    ],
                    finance_ops: [
                        { value: 'payment_issue', label: 'Payment Issue' },
                        { value: 'approval_flow', label: 'Approval Flow' },
                        { value: 'amount_revision', label: 'Amount Revision' },
                    ],
                },

                init() {
                    this.loadSimilarTickets();
                },

                showAlert(message, type = 'success') {
                    const el = document.getElementById('page-alert');
                    if (!el) return;

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

                availableIssueTypes() {
                    return this.issueTypeMap[this.form.category] || [];
                },

                onCategoryChange() {
                    this.form.issue_type = '';
                    this.loadSimilarTickets();
                },

                showField(field) {
                    const category = this.form.category;

                    const visibleMap = {
                        finance_ops: ['amount', 'flow_type', 'request_time'],
                        request: ['flow_type', 'request_time'],
                        incident: ['request_time'],
                        access: ['request_time'],
                    };

                    return (visibleMap[category] || ['request_time']).includes(field);
                },

                routeToLabel() {
                    return this.form.team ? this.form.team.toUpperCase() : '-';
                },

                previewCategoryLabel() {
                    if (!this.form.category) return '-';
                    const found = this.categoryOptions.find(item => item.value === this.form.category);
                    return found ? found.label : this.form.category;
                },

                previewIssueTypeLabel() {
                    if (!this.form.issue_type) return '-';
                    const found = this.availableIssueTypes().find(item => item.value === this.form.issue_type);
                    return found ? found.label : this.form.issue_type;
                },

                slaPreview() {
                    const map = {
                        critical: { response: '30m', resolve: '2hr' },
                        high: { response: '1hr', resolve: '6hr' },
                        medium: { response: '4hr', resolve: '12hr' },
                        low: { response: '8hr', resolve: '24hr' },
                    };

                    return map[this.form.priority] || { response: '-', resolve: '-' };
                },

                requiredFieldMap() {
                    return [
                        { key: 'client_name', label: 'Client Name', selector: 'field-client_name', filled: !!this.form.client_name },
                        { key: 'client_contact', label: 'Client Contact', selector: 'field-client_contact', filled: !!this.form.client_contact },
                        { key: 'client_email', label: 'Client Email', selector: 'field-client_email', filled: !!this.form.client_email },
                        { key: 'title', label: 'Title', selector: 'field-title', filled: !!this.form.title },
                        { key: 'description', label: 'Description', selector: 'field-description', filled: !!this.form.description },
                        { key: 'priority', label: 'Priority', selector: 'field-priority', filled: !!this.form.priority },
                        { key: 'team', label: 'Owner Team', selector: 'field-team', filled: !!this.form.team },
                        { key: 'category', label: 'Category', selector: 'field-category', filled: !!this.form.category },
                        { key: 'issue_type', label: 'Issue Type', selector: 'field-issue_type', filled: !!this.form.issue_type },
                        { key: 'platform_type', label: 'Platform', selector: 'field-platform', filled: !!this.form.platform_type },
                    ];
                },

                missingFields() {
                    return this.requiredFieldMap().filter(item => !item.filled);
                },

                isFormReady() {
                    return this.missingFields().length === 0;
                },

                scrollToField(key) {
                    const item = this.requiredFieldMap().find(entry => entry.key === key);
                    if (!item) return;

                    const target = document.getElementById(item.selector);
                    if (!target) return;

                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    target.classList.add('ring-2', 'ring-[#2f88d8]', 'rounded');
                    setTimeout(() => {
                        target.classList.remove('ring-2', 'ring-[#2f88d8]', 'rounded');
                    }, 1500);
                },

                continueAnyway() {
                    this.showAlert('Continuing ticket creation. Please make sure this is not a duplicate.', 'success');
                },

                onAttachmentChange(event) {
                    const file = event.target.files?.[0] || null;
                    this.form.attachment = file;
                    this.attachmentName = file ? file.name : '';
                },

                submitDisabled() {
                    return !this.isFormReady();
                },

                async loadSimilarTickets() {
                    if (!this.form.title || !this.form.team || !this.form.category) {
                        this.similarTickets = [];
                        return;
                    }

                    this.similarLoading = true;

                    try {
                        const q = encodeURIComponent(this.form.title);
                        const team = encodeURIComponent(this.form.team);
                        const category = encodeURIComponent(this.form.category);

                        const response = await fetch(`/api/tickets-similar?q=${q}&team=${team}&category=${category}`, {
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
                    } catch (error) {
                        console.error(error);
                        this.similarTickets = [];
                    } finally {
                        this.similarLoading = false;
                    }
                },

                async submitTicket() {
                    if (this.submitDisabled() || this.submitting) return;

                    this.submitting = true;

                    try {
                        const formData = new FormData();

                        formData.append('title', this.form.title || '');
                        formData.append('description', this.form.description || '');
                        formData.append('priority', this.form.priority || '');
                        formData.append('team', this.form.team || '');
                        formData.append('category', this.form.category || '');
                        formData.append('issue_type', this.form.issue_type || '');

                        formData.append('client_name', this.form.client_name || '');
                        formData.append('client_contact', this.form.client_contact || '');
                        formData.append('client_email', this.form.client_email || '');
                        formData.append('platform_type', this.form.platform_type || '');
                        formData.append('amount', this.form.amount || '');
                        formData.append('flow_type', this.form.flow_type || '');
                        formData.append('request_time', this.form.request_time || '');
                        formData.append('internal_notes', this.form.internal_notes || '');

                        if (this.form.attachment) {
                            formData.append('attachment', this.form.attachment);
                        }

                        const response = await fetch('/api/tickets', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: formData,
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to create ticket.');
                        }

                        this.showAlert('Ticket created successfully.', 'success');

                        setTimeout(() => {
                            if (result.data?.id) {
                                window.location.href = `/tickets/${result.data.id}`;
                            } else {
                                window.location.href = '/tickets';
                            }
                        }, 700);
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to create ticket.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                saveDraft() {
                    this.showAlert('Draft flow is not connected to backend yet.', 'error');
                },
            }
        }
    </script>
</x-app-layout>