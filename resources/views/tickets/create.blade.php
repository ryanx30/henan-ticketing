<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        [x-cloak] { display: none !important; }
    </style>

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
                <div class="mb-4 overflow-visible rounded-md border border-slate-200 bg-white shadow-sm">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between bg-slate-900 px-4 py-3 text-left text-sm font-semibold text-white"
                        @click="sections.client = !sections.client"
                    >
                        <span>Client Contact</span>
                        <span x-text="sections.client ? '▾' : '▸'"></span>
                    </button>

<div x-show="sections.client" x-collapse class="relative overflow-visible grid grid-cols-1 gap-4 p-4 md:grid-cols-3">                        <div id="field-client_name" class="relative">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Client Name*</label>
                            <input
                                type="text"
                                x-model="form.client_name"
                                @input.debounce.350ms="loadClientSuggestions()"
                                @focus="loadClientSuggestions()"
                                @keydown.escape="clientSuggestOpen = false"
                                placeholder="Client Name"
                                autocomplete="off"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            />

<div
    x-show="clientSuggestOpen"
    x-cloak
    @click.outside="clientSuggestOpen = false"
    class="absolute left-0 right-0 z-[9999] mt-2 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
>
                                <template x-if="clientSuggestLoading">
                                    <div class="px-3 py-3 text-xs text-slate-500">Searching clients...</div>
                                </template>

                                <template x-if="!clientSuggestLoading && clientSuggestions.length === 0">
                                    <div class="px-3 py-3 text-xs text-slate-500">No existing client found. New client will be saved automatically.</div>
                                </template>

                                <template x-for="client in clientSuggestions" :key="client.id">
                                    <button
                                        type="button"
                                        @click="selectClientSuggestion(client)"
                                        class="block w-full border-b border-slate-100 px-3 py-3 text-left text-xs hover:bg-slate-50"
                                    >
                                        <div class="font-semibold text-slate-900" x-text="client.name || '-' "></div>
                                        <div class="mt-1 text-slate-500">
                                            <span x-text="client.email || '-'"></span>
                                            <span> • </span>
                                            <span x-text="client.contact || '-'"></span>
                                        </div>
                                        <div class="mt-1 text-[10px] text-slate-400" x-text="`${client.ticket_count || 0} previous ticket(s)`"></div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div id="field-client_contact">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Client Contact*</label>
                            <input
                                type="text"
                                x-model="form.client_contact"
                                @input.debounce.350ms="loadClientSuggestions()"
                                @focus="loadClientSuggestions()"
                                placeholder="Client Contact"
                                autocomplete="off"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            />
                        </div>

                        <div id="field-client_email">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Client Email*</label>
                            <input
                                type="email"
                                x-model="form.client_email"
                                @input.debounce.350ms="loadClientSuggestions()"
                                @focus="loadClientSuggestions()"
                                placeholder="lorem@gmail.com"
                                autocomplete="off"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            />
                        </div>

                        <template x-if="selectedClient">
                            <div class="md:col-span-3 rounded border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-800">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        Linked to existing client:
                                        <span class="font-semibold" x-text="selectedClient.name"></span>
                                        <span class="text-blue-500" x-text="selectedClient.email ? ` • ${selectedClient.email}` : ''"></span>
                                    </div>
                                    <button type="button" class="text-[11px] font-semibold underline" @click="clearSelectedClient()">
                                        Use as new client
                                    </button>
                                </div>
                            </div>
                        </template>
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
                                <template x-if="optionsLoading">
                                    <div class="col-span-2 text-xs text-slate-400">Loading priorities...</div>
                                </template>

                                <template x-for="priority in master.priorities" :key="priority.id">
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            :value="String(priority.id)"
                                            x-model="form.priority_id"
                                            @change="loadSimilarTickets()"
                                        >
                                        <span x-text="priority.name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div id="field-team">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Owner Team*</label>
                            <select
                                x-model="form.team_id"
                                @change="loadSimilarTickets()"
                                :disabled="optionsLoading"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 disabled:cursor-not-allowed disabled:bg-slate-100 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="" x-text="optionsLoading ? 'Loading teams...' : 'Select team'"></option>
                                <template x-for="team in master.teams" :key="team.id">
                                    <option :value="String(team.id)" x-text="masterLabel(team)"></option>
                                </template>
                            </select>
                        </div>

                        <div id="field-category">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Category*</label>
                            <select
                                x-model="form.category_id"
                                @change="onCategoryChange()"
                                :disabled="optionsLoading"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 disabled:cursor-not-allowed disabled:bg-slate-100 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="" x-text="optionsLoading ? 'Loading categories...' : 'Select category'"></option>
                                <template x-for="cat in master.categories" :key="cat.id">
                                    <option :value="String(cat.id)" x-text="masterLabel(cat)"></option>
                                </template>
                            </select>
                        </div>

                        <div id="field-issue_type">
                            <label class="mb-2 block text-xs font-semibold text-slate-700">Issue Type*</label>
                            <select
                                x-model="form.issue_type_id"
                                :disabled="!form.category_id || issueTypesLoading"
                                @change="loadSimilarTickets()"
                                class="h-10 w-full rounded border border-slate-300 px-3 text-sm text-slate-700 disabled:cursor-not-allowed disabled:bg-slate-100 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="" x-text="issueTypePlaceholder()"></option>
                                <template x-for="item in issueTypes" :key="item.id">
                                    <option :value="String(item.id)" x-text="masterLabel(item)"></option>
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

                        <div>
                            <div class="mb-1 text-[11px] font-semibold text-slate-500">Ticket Code Preview:</div>
                            <div class="font-semibold text-slate-900" x-text="ticketCodePreview()"></div>
                            <div class="mt-1 text-[10px] text-slate-400">Final sequence is generated by backend.</div>
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
                    <div class="mb-3 text-xs font-bold text-slate-900">CLIENT HISTORY <span class="text-slate-500">(Auto)</span></div>

                    <div class="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <template x-if="!selectedClient && !clientHistoryLoading">
                            <div>
                                <div class="text-[11px]">
                                    Type a client name, email, or contact, then select an existing client to preview history.
                                </div>
                                <div class="mt-2 text-[11px] text-slate-500">
                                    New clients are saved automatically after ticket submission.
                                </div>
                            </div>
                        </template>

                        <template x-if="clientHistoryLoading">
                            <div class="text-[11px] text-slate-500">Loading client history...</div>
                        </template>

                        <template x-if="selectedClient && !clientHistoryLoading">
                            <div>
                                <div class="mb-3 rounded border border-slate-200 bg-white p-2">
                                    <div class="font-semibold text-slate-900" x-text="selectedClient.name || '-'"></div>
                                    <div class="mt-1 text-[11px] text-slate-500" x-text="selectedClient.email || '-'"></div>
                                    <div class="text-[11px] text-slate-500" x-text="selectedClient.contact || '-'"></div>
                                </div>

                                <template x-if="clientHistory.length === 0">
                                    <div class="text-[11px] text-slate-500">No previous ticket found for this client.</div>
                                </template>

                                <div class="space-y-2" x-show="clientHistory.length > 0">
                                    <template x-for="ticket in clientHistory" :key="ticket.id">
                                        <div class="rounded border border-slate-200 bg-white p-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="font-semibold text-slate-800" x-text="ticketLabel(ticket)"></div>
                                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600" x-text="ticket.status || '-'"></span>
                                            </div>
                                            <div class="mt-1 text-[11px] text-slate-600" x-text="ticket.title || '-'"></div>
                                            <div class="mt-1 text-[10px] text-slate-400" x-text="formatDate(ticket.created_at)"></div>
                                            <a
                                                :href="`/tickets/${ticket.id}`"
                                                class="mt-2 inline-flex rounded border border-slate-300 bg-white px-2 py-1 text-[10px] font-medium text-slate-700 hover:bg-slate-50"
                                            >
                                                View Ticket
                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>