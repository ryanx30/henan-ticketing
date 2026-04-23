@php
    $q = request('q', '');
    $status = request('status', 'all');
    $priority = request('priority', 'all');
    $dateFrom = request('date_from', '');
    $dateTo = request('date_to', '');
    $focus = request('focus', '');
    $userRole = auth()->user()->role ?? null;
@endphp

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />

    <div
        x-data="ticketsIndexPage({
            initialFilters: {
                q: @js($q),
                status: @js($status),
                priority: @js($priority),
                date_from: @js($dateFrom),
                date_to: @js($dateTo),
                focus: @js($focus),
            }
        })"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7"
    >
        <div class="mx-auto w-full max-w-[1400px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-[30px] font-bold leading-tight text-slate-900">Tickets</h1>
                    <p class="mt-1 text-sm text-slate-500">Track, filter, and manage tickets.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('tickets.create') }}"
                        class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-slate-800"
                    >
                        + Create Ticket
                    </a>
                </div>
            </div>

            @if($focus)
                <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="font-semibold">Focus Filter Active:</span>
                            @switch($focus)
                                @case('sla_risk')
                                    SLA Risk Tickets
                                    @break
                                @case('due_today')
                                    Tickets Due Today
                                    @break
                                @case('reopened')
                                    Reopened Tickets
                                    @break
                                @default
                                    {{ $focus }}
                            @endswitch
                        </div>

                        <a href="{{ route('tickets.index') }}"
                           class="rounded border border-sky-300 bg-white px-3 py-1 text-xs font-medium text-sky-700 hover:bg-sky-100">
                            Clear Focus
                        </a>
                    </div>
                </div>
            @endif

            <div class="rounded bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <form @submit.prevent="applyFilters()" class="mb-6">
                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_170px_170px_240px_auto]">
                        <div>
                            <input
                                type="text"
                                x-model="filters.q"
                                placeholder="Search by Ticket ID or Keyword..."
                                class="h-10 w-full rounded-md border border-slate-300 bg-white px-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-0"
                                @keydown.enter.prevent="applyFilters()"
                            />
                        </div>

                        <div>
                            <select
                                x-model="filters.status"
                                class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-0"
                            >
                                <option value="all">All Status</option>
                                <option value="new">New</option>
                                <option value="in_progress">On Going</option>
                                <option value="waiting_info">Waiting</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <div>
                            <select
                                x-model="filters.priority"
                                class="h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-0"
                            >
                                <option value="all">All Priority</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="hidden" id="date_from" x-model="filters.date_from">
                            <input type="hidden" id="date_to" x-model="filters.date_to">

                            <div id="dateRangeTrigger"
                                class="relative flex h-10 w-full shrink-0 cursor-pointer items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 hover:border-slate-400">
                                <span id="dateRangeLabel" class="truncate text-sm text-slate-700" x-text="dateLabel()"></span>
                                <svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="submit"
                                class="inline-flex h-10 items-center justify-center rounded-md bg-[#2f88d8] px-4 text-sm font-semibold text-white transition hover:bg-[#2878c3]"
                            >
                                Apply
                            </button>

                            <button
                                type="button"
                                @click="resetFilters()"
                                class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </form>

                <div class="overflow-hidden rounded-lg border border-slate-300 shadow-[0_4px_12px_rgba(15,23,42,0.08)]">
                    <div class="bg-[#051823] px-7 py-3">
                        <h2 class="text-xl font-semibold leading-none text-white">Ticket Repository</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-slate-800">
                            <thead class="bg-[#d5e0e7] text-[#051823]">
                                <tr class="text-left">
                                    <th class="px-7 py-3 font-semibold">Ticket</th>
                                    <th class="px-7 py-3 font-semibold">Title</th>
                                    <th class="px-7 py-3 font-semibold">Priority</th>
                                    <th class="px-7 py-3 font-semibold">Category</th>
                                    <th class="px-7 py-3 font-semibold">Team</th>
                                    <th class="px-7 py-3 font-semibold">Status</th>
                                    <th class="px-7 py-3 font-semibold">Created</th>
                                    <th class="w-[82px] px-6 py-3 text-right"></th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="loading">
                                    <tr>
                                        <td colspan="8" class="px-8 py-10 text-center text-slate-500">Loading tickets...</td>
                                    </tr>
                                </template>

                                <template x-if="!loading && tickets.length === 0">
                                    <tr>
                                        <td colspan="8" class="px-8 py-10 text-center text-slate-500">No tickets found.</td>
                                    </tr>
                                </template>

                                <template x-for="(t, index) in tickets" :key="t.id">
                                    <tr
                                        :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'"
                                    >
                                        <td class="px-7 py-3 whitespace-nowrap font-medium" x-text="ticketLabel(t)"></td>
                                        <td class="px-7 py-3 max-w-[340px] truncate" x-text="t.title || '-'"></td>
                                        <td class="px-7 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                                :class="priorityBadgeClass(t.priority)"
                                                x-text="ucfirst(t.priority)"
                                            ></span>
                                        </td>
                                        <td class="px-7 py-3 whitespace-nowrap" x-text="categoryLabel(t)"></td>
                                        <td class="px-7 py-3 whitespace-nowrap uppercase" x-text="t.team ?? '-'"></td>
                                        <td class="px-7 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                                :class="statusBadgeClass(t.status)"
                                                x-text="statusLabel(t.status)"
                                            ></span>
                                        </td>
                                        <td class="px-7 py-3 whitespace-nowrap" x-text="createdLabel(t)"></td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center justify-end gap-3 text-slate-500">
                                                <a
                                                    :href="`/tickets/${t.id}`"
                                                    class="hover:text-slate-800 transition-colors"
                                                    title="View Detail"
                                                >
                                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                                        <circle cx="12" cy="12" r="3" />
                                                    </svg>
                                                </a>

                                                <a
                                                    :href="`/tickets/${t.id}/edit`"
                                                    class="hover:text-slate-800 transition-colors"
                                                    title="Edit Ticket"
                                                >
                                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-3 text-sm text-slate-700">
                    <div class="flex items-center gap-2">
                        <span>Items per page:</span>

                        <select
                            x-model="filters.per_page"
                            @change="applyFilters()"
                            class="h-9 w-16 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700"
                        >
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    <div id="tickets-pagination" class="flex items-center gap-1"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script>
        function ticketsIndexPage({ initialFilters }) {
            return {
                loading: false,
                tickets: [],
                meta: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 10,
                    total: 0,
                },
                filters: {
                    q: initialFilters.q || '',
                    status: initialFilters.status || 'all',
                    priority: initialFilters.priority || 'all',
                    date_from: initialFilters.date_from || '',
                    date_to: initialFilters.date_to || '',
                    focus: initialFilters.focus || '',
                    per_page: '10',
                    page: 1,
                },

                init() {
                    const params = new URLSearchParams(window.location.search);

                    this.filters.q = params.get('q') || this.filters.q;
                    this.filters.status = params.get('status') || this.filters.status;
                    this.filters.priority = params.get('priority') || this.filters.priority;
                    this.filters.date_from = params.get('date_from') || this.filters.date_from;
                    this.filters.date_to = params.get('date_to') || this.filters.date_to;
                    this.filters.focus = params.get('focus') || this.filters.focus;
                    this.filters.per_page = params.get('per_page') || '10';
                    this.filters.page = Number(params.get('page') || 1);

                    this.initDatePicker();
                    this.loadTickets();
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

                buildQuery() {
                    const params = new URLSearchParams();

                    if (this.filters.q) params.set('q', this.filters.q);
                    if (this.filters.status) params.set('status', this.filters.status);
                    if (this.filters.priority) params.set('priority', this.filters.priority);
                    if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                    if (this.filters.date_to) params.set('date_to', this.filters.date_to);
                    if (this.filters.focus) params.set('focus', this.filters.focus);

                    params.set('per_page', this.filters.per_page);
                    params.set('page', this.filters.page);

                    return params;
                },

                applyFilters() {
                    this.filters.page = 1;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadTickets();
                },

                goToPage(page) {
                    this.filters.page = page;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadTickets();
                },

                resetFilters() {
                    this.filters.q = '';
                    this.filters.status = 'all';
                    this.filters.priority = 'all';
                    this.filters.date_from = '';
                    this.filters.date_to = '';
                    this.filters.focus = '';
                    this.filters.per_page = '10';
                    this.filters.page = 1;

                    window.history.replaceState({}, '', window.location.pathname);
                    this.loadTickets();
                },

                async loadTickets() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const response = await fetch(`/api/tickets?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load tickets');
                        }

                        this.tickets = result.data || [];
                        this.meta = result.meta || this.meta;
                        this.renderPagination();
                    } catch (error) {
                        console.error(error);
                        this.tickets = [];
                        this.renderPagination();
                        this.showAlert(error.message || 'Failed to load tickets', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                renderPagination() {
                    const container = document.getElementById('tickets-pagination');
                    if (!container) return;

                    if (!this.meta.last_page || this.meta.last_page <= 1) {
                        container.innerHTML = '';
                        return;
                    }

                    let html = '';

                    for (let i = 1; i <= this.meta.last_page; i++) {
                        html += `
                            <button
                                type="button"
                                onclick="document.querySelector('[x-data^=\\'ticketsIndexPage\\']').__x.$data.goToPage(${i})"
                                class="px-3 py-1 border rounded text-sm ${i === this.meta.current_page ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50'}">
                                ${i}
                            </button>
                        `;
                    }

                    container.innerHTML = html;
                },

                dateLabel() {
                    if (this.filters.date_from && this.filters.date_to) {
                        return `${this.formatHumanDate(this.filters.date_from)} → ${this.formatHumanDate(this.filters.date_to)}`;
                    }
                    return 'dd/mm/yyyy → dd/mm/yyyy';
                },

                formatHumanDate(value) {
                    const date = new Date(value);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },

                initDatePicker() {
                    const self = this;

                    new Litepicker({
                        element: document.getElementById('dateRangeTrigger'),
                        singleMode: false,
                        numberOfMonths: 2,
                        numberOfColumns: 2,
                        format: 'YYYY-MM-DD',
                        autoApply: false,
                        showTooltip: true,
                        tooltipText: {
                            one: 'day',
                            other: 'days'
                        },
                        buttonText: {
                            apply: 'Apply',
                            cancel: 'Cancel',
                            reset: 'Reset'
                        },

                        setup(picker) {
                            picker.on('selected', (date1, date2) => {
                                if (!date1 || !date2) return;

                                self.filters.date_from = self.toYmd(new Date(date1.dateInstance));
                                self.filters.date_to = self.toYmd(new Date(date2.dateInstance));
                                self.applyFilters();
                            });
                        },
                    });
                },

                toYmd(d) {
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                },

                ucfirst(value) {
                    if (!value) return '-';
                    value = String(value);
                    return value.charAt(0).toUpperCase() + value.slice(1);
                },

                ticketLabel(t) {
                    let ticketNumber = t.ticket_code || t.id;
                    ticketNumber = String(ticketNumber).replace('#', '').replace(/^T-?/i, '');
                    return `#T-${ticketNumber}`;
                },

                categoryLabel(t) {
                    if (!t.category) return '-';
                    return String(t.category).replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                },

                createdLabel(t) {
                    const value = t.created_at;
                    if (!value) return '-';

                    const date = new Date(value);

                    return date.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                    });
                },

                statusLabel(status) {
                    const map = {
                        new: 'New',
                        in_progress: 'On Going',
                        waiting_info: 'Waiting',
                        resolved: 'Resolved',
                        closed: 'Closed',
                    };

                    return map[status] || status || '-';
                },

                statusBadgeClass(status) {
                    switch (status) {
                        case 'new':
                            return 'bg-gray-200 text-gray-800';
                        case 'in_progress':
                            return 'bg-amber-100 text-amber-700';
                        case 'waiting_info':
                            return 'bg-orange-100 text-orange-700';
                        case 'resolved':
                            return 'bg-green-100 text-green-700';
                        case 'closed':
                            return 'bg-sky-100 text-sky-700';
                        default:
                            return 'bg-slate-100 text-slate-700';
                    }
                },

                priorityBadgeClass(priority) {
                    switch (priority) {
                        case 'critical':
                            return 'bg-red-100 text-red-700';
                        case 'high':
                            return 'bg-pink-100 text-pink-700';
                        case 'medium':
                            return 'bg-amber-100 text-amber-700';
                        case 'low':
                            return 'bg-green-100 text-green-700';
                        default:
                            return 'bg-slate-100 text-slate-700';
                    }
                },
            }
        }
    </script>

    <style>
        .litepicker {
            font-family: inherit;
            font-size: 13px;
            --litepicker-container-months-color-bg: #ffffff;
            --litepicker-month-header-color: #0f172a;
            --litepicker-button-prev-month-color: #64748b;
            --litepicker-button-next-month-color: #64748b;
            --litepicker-button-prev-month-color-hover: #0f172a;
            --litepicker-button-next-month-color-hover: #0f172a;
            --litepicker-month-week-day-color: #94a3b8;
            --litepicker-day-color: #1e293b;
            --litepicker-day-color-hover: #0f172a;
            --litepicker-is-today-color: #2f88d8;
            --litepicker-is-in-range-color: #1e293b;
            --litepicker-is-in-range-color-bg: #dbeafe;
            --litepicker-is-start-color: #ffffff;
            --litepicker-is-start-color-bg: #2f88d8;
            --litepicker-is-end-color: #ffffff;
            --litepicker-is-end-color-bg: #2f88d8;
            --litepicker-button-apply-color-bg: #2f88d8;
            --litepicker-button-cancel-color-bg: #e2e8f0;
            --litepicker-button-cancel-color: #475569;
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.15);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .litepicker .container__months {
            background: #ffffff;
        }

        .litepicker .container__footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 8px 16px;
        }

        .litepicker .month-item-header div > .month-item-name,
        .litepicker .month-item-header div > .month-item-year {
            color: #0f172a;
            font-weight: 600;
        }

        .litepicker .day-item {
            color: #1e293b;
            width: 32px !important;
            height: 32px !important;
            line-height: 32px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .litepicker .day-item:hover {
            background-color: #e2e8f0;
            color: #0f172a;
            border-radius: 50% !important;
        }

        .litepicker .day-item.is-today {
            color: #2f88d8;
            font-weight: 700;
            border: 1.5px solid #2f88d8;
            border-radius: 50% !important;
            background: transparent;
        }

        .litepicker .day-item.is-start-date,
        .litepicker .day-item.is-end-date {
            background-color: #2f88d8 !important;
            color: #fff !important;
            border-radius: 50% !important;
            border: none !important;
        }

        .litepicker .day-item.is-in-range {
            background-color: #bfdbfe !important;
            color: #1e40af !important;
            border-radius: 0 !important;
        }
    </style>
</x-app-layout>