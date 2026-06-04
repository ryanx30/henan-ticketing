{{-- ========= TICKET EDIT SHELL ========= --}}
{{-- Ticket edit/open layout; ticket data and update actions are handled through the internal API. --}}

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
                                    <div class="text-xs text-gray-500 mt-1" x-text="ticketCodeLabel() || 'Loading...'"></div>
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
                                                            <template x-if="optionsLoading">
                                                                <div class="col-span-2 text-xs text-slate-400">Loading priorities...</div>
                                                            </template>

                                                            <template x-for="priority in master.priorities" :key="priority.id">
                                                                <label class="flex items-center gap-2">
                                                                    <input
                                                                        type="radio"
                                                                        :value="String(priority.id)"
                                                                        x-model="priority_id"
                                                                        @change="fetchSimilar()"
                                                                    >
                                                                    <span x-text="priority.name"></span>
                                                                </label>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label class="text-sm font-medium">Owner Team</label>
                                                    <select
                                                        x-model="team_id"
                                                        @change="fetchSimilar()"
                                                        :disabled="optionsLoading"
                                                        class="mt-1 w-full border rounded px-3 py-2 disabled:bg-slate-100 disabled:cursor-not-allowed"
                                                    >
                                                        <option value="" x-text="optionsLoading ? 'Loading teams...' : 'Select team'"></option>
                                                        <template x-for="team in master.teams" :key="team.id">
                                                            <option :value="String(team.id)" x-text="masterLabel(team)"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label class="text-sm font-medium">Status</label>
                                                    <select x-model="status" class="mt-1 w-full border rounded px-3 py-2">
                                                        <option value="new">New</option>
                                                        <option value="in_progress">Ongoing</option>
                                                        <option value="waiting_info">Waiting Info</option>
                                                        <option value="resolved">Resolved</option>
                                                        <option value="closed">Closed</option>
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-3">
                                                    <label class="text-sm font-medium">Category</label>
                                                    <select
                                                        x-model="category_id"
                                                        @change="onCategoryChange()"
                                                        :disabled="optionsLoading"
                                                        class="mt-1 w-full border rounded px-3 py-2 disabled:bg-slate-100 disabled:cursor-not-allowed"
                                                    >
                                                        <option value="" x-text="optionsLoading ? 'Loading categories...' : 'Select category'"></option>
                                                        <template x-for="category in master.categories" :key="category.id">
                                                            <option :value="String(category.id)" x-text="masterLabel(category)"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label class="text-sm font-medium">Issue Type</label>
                                                    <select
                                                        x-model="issue_type_id"
                                                        class="mt-1 w-full border rounded px-3 py-2 disabled:bg-slate-100 disabled:cursor-not-allowed"
                                                        :disabled="!category_id || issueTypesLoading"
                                                        @change="fetchSimilar()"
                                                    >
                                                        <option value="" x-text="issueTypePlaceholder()"></option>
                                                        <template x-for="item in issueTypes" :key="item.id">
                                                            <option :value="String(item.id)" x-text="masterLabel(item)"></option>
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
                                        <b x-text="ticketCodeLabel()"></b>
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
                                <div class="p-3">
                                    <div class="bg-white border rounded p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-semibold text-gray-600">Resolve Within:</span>
                                            <b class="text-right text-xl font-extrabold text-slate-950" x-text="sla.resolve"></b>
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
                                                        <span x-text="ticketLabel(tk)"></span>
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

</x-app-layout>