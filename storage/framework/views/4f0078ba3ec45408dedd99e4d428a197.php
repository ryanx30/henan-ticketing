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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />

    <div
        id="it-history-page"
        x-data="historyPage()"
        x-init="init()"
        class="rounded bg-white mx-8 my-7 p-8 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        
        <form @submit.prevent="applyFilters()" id="historyFilterForm" class="mb-6">
            <div class="flex items-center gap-2">

                
                <div class="flex-1">
                    <input
                        type="text"
                        x-model="filters.q"
                        placeholder="Search by Ticket ID or Keyword..."
                        class="h-8 w-full rounded-md border border-slate-300 bg-white px-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-0"
                        @keydown.enter.prevent="applyFilters()" />
                </div>

                
                <input type="hidden" id="date_from" x-model="filters.date_from">
                <input type="hidden" id="date_to" x-model="filters.date_to">

                
                <div id="dateRangeTrigger"
                    class="relative flex h-8 w-60 shrink-0 cursor-pointer items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-3 hover:border-slate-400">
                    <span id="dateRangeLabel" class="truncate text-sm text-slate-700" x-text="dateLabel()"></span>
                    <svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 10h18"></path>
                    </svg>
                </div>

                
                <button
                    type="button"
                    style="display:inline-flex;height:32px;flex-shrink:0;align-items:center;justify-content:center;gap:8px;border-radius:6px;background-color:#2f88d8;padding:0 16px;font-size:14px;font-weight:500;color:#ffffff;white-space:nowrap;border:none;cursor:pointer;"
                    onmouseover="this.style.backgroundColor='#2878c3'"
                    onmouseout="this.style.backgroundColor='#2f88d8'">
                    Export Data
                    <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </form>

        
        <div class="overflow-hidden rounded-lg border border-slate-300 shadow-[0_4px_12px_rgba(15,23,42,0.08)]">
            <div class="bg-[#051823] px-7 py-2">
                <h2 class="text-2xl px-4 font-semibold leading-none text-white">
                    Ticket History Repository
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-slate-800">
                    <thead class="bg-[#d5e0e7] text-[#051823]">
                        <tr class="text-left">
                            <th class="px-7 py-3 font-semibold">
                                <button type="button" @click="sort('ticket_code')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                                    Ticket <span x-html="sortIcon('ticket_code')"></span>
                                </button>
                            </th>
                            <th class="px-7 py-3 font-semibold">
                                <button type="button" @click="sort('resolved_at')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                                    Resolved Date <span x-html="sortIcon('resolved_at')"></span>
                                </button>
                            </th>
                            <th class="px-7 py-3 font-semibold">
                                <button type="button" @click="sort('category')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                                    Category <span x-html="sortIcon('category')"></span>
                                </button>
                            </th>
                            <th class="px-7 py-3 font-semibold">
                                <button type="button" @click="sort('team')" class="inline-flex items-center gap-1 hover:text-[#2f88d8] transition-colors">
                                    Team <span x-html="sortIcon('team')"></span>
                                </button>
                            </th>
                            <th class="px-7 py-3 font-semibold">Resolution Note</th>
                            <th class="px-7 py-3 font-semibold">Duration (SLA)</th>
                            <th class="w-[82px] px-6 py-3 text-right"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="7" class="px-8 py-10 text-center text-slate-500">Loading history...</td>
                            </tr>
                        </template>

                        <template x-if="!loading && tickets.length === 0">
                            <tr>
                                <td colspan="7" class="px-8 py-10 text-center text-slate-500">No history found.</td>
                            </tr>
                        </template>

                        <template x-for="(t, index) in tickets" :key="t.id">
                            <tr :class="index % 2 === 0 ? 'border-t border-slate-200 bg-white' : 'border-t border-slate-200 bg-[#dfe8ee]'">
                                <td class="px-7 py-2 whitespace-nowrap font-medium" x-text="ticketLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap" x-text="resolvedLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap" x-text="categoryLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap uppercase" x-text="t.team ?? '-'"></td>
                                <td class="px-7 py-2 whitespace-nowrap" x-text="resolutionLabel(t)"></td>
                                <td class="px-7 py-2 whitespace-nowrap">
                                    <span x-text="durationText(t)"></span>
                                    <span
                                        class="ml-1 text-[13px] font-medium"
                                        :class="slaBadge(t) === 'Met' ? 'text-green-600' : 'text-slate-500'"
                                        x-show="slaBadge(t) !== ''"
                                        x-text="'(' + slaBadge(t) + ')'"></span>
                                </td>
                                <td class="px-6 py-2">
                                    <div class="flex items-center justify-end gap-3 text-slate-500">
                                        <a href="<?php echo e(route('it.team-queue')); ?>" class="hover:text-slate-800 transition-colors" title="View">
                                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </a>
                                        <button type="button" class="hover:text-slate-800 transition-colors" title="Download">
                                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l4-4m-4 4l-4-4" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 17v3h16v-3" />
                                            </svg>
                                        </button>
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
                    style="height:32px;border-radius:6px;border:1px solid #cbd5e1;background:white url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2364748b\' stroke-width=\'2.5\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M19 9l-7 7-7-7\'/></svg>') no-repeat right 8px center;appearance:none;-webkit-appearance:none;padding:0 28px 0 8px;font-size:13px;line-height:32px;color:#334155;cursor:pointer;outline:none;"
                    @change="applyFilters()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div id="history-pagination" class="flex items-center gap-1"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    <script>
        function historyPage() {
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
                    q: '',
                    date_from: '',
                    date_to: '',
                    sort_by: 'resolved_at',
                    sort_dir: 'desc',
                    per_page: '10',
                    page: 1,
                },

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.filters.q = params.get('q') || '';
                    this.filters.date_from = params.get('date_from') || '';
                    this.filters.date_to = params.get('date_to') || '';
                    this.filters.sort_by = params.get('sort_by') || 'resolved_at';
                    this.filters.sort_dir = params.get('sort_dir') || 'desc';
                    this.filters.per_page = params.get('per_page') || '10';
                    this.filters.page = Number(params.get('page') || 1);

                    this.initDatePicker();
                    this.loadHistory();
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
                    setTimeout(() => el.classList.add('hidden'), 3000);
                },

                buildQuery() {
                    const params = new URLSearchParams();
                    if (this.filters.q) params.set('q', this.filters.q);
                    if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                    if (this.filters.date_to) params.set('date_to', this.filters.date_to);
                    params.set('sort_by', this.filters.sort_by);
                    params.set('sort_dir', this.filters.sort_dir);
                    params.set('per_page', this.filters.per_page);
                    params.set('page', this.filters.page);
                    return params;
                },

                applyFilters() {
                    this.filters.page = 1;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadHistory();
                },

                goToPage(page) {
                    this.filters.page = page;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadHistory();
                },

                sort(column) {
                    if (this.filters.sort_by === column) {
                        this.filters.sort_dir = this.filters.sort_dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.filters.sort_by = column;
                        this.filters.sort_dir = 'asc';
                    }
                    this.applyFilters();
                },

                async loadHistory() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const response = await fetch(`/api/it/history?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load history');
                        }

                        this.tickets = result.data || [];
                        this.meta = result.meta || this.meta;
                        this.renderPagination();
                    } catch (error) {
                        console.error(error);
                        this.tickets = [];
                        this.renderPagination();
                        this.showAlert(error.message || 'Failed to load history', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                renderPagination() {
                    const container = document.getElementById('history-pagination');
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
                                onclick="document.getElementById('it-history-page').__x.$data.goToPage(${i})"
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

                    const picker = new Litepicker({
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
                            picker.on('render', (ui) => {
                                if (ui.querySelector('.lp-shortcuts')) return;

                                const shortcuts = document.createElement('div');
                                shortcuts.className = 'lp-shortcuts';

                                const today = new Date();
                                const items = [{
                                        label: 'Today',
                                        from: today,
                                        to: today
                                    },
                                    {
                                        label: 'Last 7 Days',
                                        from: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6),
                                        to: today
                                    },
                                    {
                                        label: 'Last 30 Days',
                                        from: new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29),
                                        to: today
                                    },
                                    {
                                        label: 'Last 1 Year',
                                        from: new Date(today.getFullYear() - 1, today.getMonth(), today.getDate()),
                                        to: today
                                    },
                                ];

                                items.forEach(item => {
                                    const btn = document.createElement('button');
                                    btn.type = 'button';
                                    btn.textContent = item.label;
                                    btn.className = 'lp-shortcut-btn';
                                    btn.addEventListener('click', () => {
                                        picker.setDateRange(item.from, item.to);
                                        self.filters.date_from = self.toYmd(item.from);
                                        self.filters.date_to = self.toYmd(item.to);
                                        picker.hide();
                                        self.applyFilters();
                                    });
                                    shortcuts.appendChild(btn);
                                });

                                ui.appendChild(shortcuts);
                            });

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

                sortIcon(column) {
                    if (this.filters.sort_by !== column) {
                        return `<span style="display:inline-flex;flex-direction:column;margin-left:4px;opacity:0.35;line-height:1;">
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 4l-8 8h16z" />
                            </svg>
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M12 20l8-8H4z" />
                            </svg>
                        </span>`;
                    }

                    if (this.filters.sort_dir === 'asc') {
                        return `<span style="display:inline-flex;flex-direction:column;margin-left:4px;line-height:1;">
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#2f88d8" stroke-width="3">
                                <path d="M12 4l-8 8h16z" />
                            </svg>
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="opacity:0.25">
                                <path d="M12 20l8-8H4z" />
                            </svg>
                        </span>`;
                    }

                    return `<span style="display:inline-flex;flex-direction:column;margin-left:4px;line-height:1;">
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="opacity:0.25">
                            <path d="M12 4l-8 8h16z" />
                        </svg>
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#2f88d8" stroke-width="3">
                            <path d="M12 20l8-8H4z" />
                        </svg>
                    </span>`;
                },

                ticketLabel(t) {
                    let ticketNumber = t.ticket_code || t.id;
                    ticketNumber = String(ticketNumber).replace('#', '').replace(/^T-?/i, '');
                    return `#T-${ticketNumber}`;
                },

                resolvedLabel(t) {
                    const value = t.resolved_at || t.closed_at || t.updated_at || t.created_at;
                    if (!value) return '-';

                    const date = new Date(value);

                    return date.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: 'short'
                    }) + ', ' + date.toLocaleTimeString('en-GB', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                categoryLabel(t) {
                    if (!t.category) return '-';
                    return String(t.category).replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                },

                resolutionLabel(t) {
                    if (t.issue_type) {
                        return String(t.issue_type).replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                    }
                    return t.title || '-';
                },

                durationText(t) {
                    const start = t.created_at ? new Date(t.created_at) : null;
                    const end = t.resolved_at ?
                        new Date(t.resolved_at) :
                        t.closed_at ?
                        new Date(t.closed_at) :
                        t.updated_at ?
                        new Date(t.updated_at) :
                        null;

                    if (!start || !end) return '-';

                    const seconds = Math.floor(Math.abs(end - start) / 1000);
                    const mins = Math.floor(seconds / 60);
                    const hours = Math.floor(mins / 60);
                    const days = Math.floor(hours / 24);

                    if (days > 0) return `${days}d ${hours % 24}h ${mins % 60}m`;
                    if (hours > 0) return `${hours}h ${mins % 60}m`;
                    return `${mins}m`;
                },

                slaBadge(t) {
                    if (!t.sla_deadline_at || !t.resolved_at) return '';
                    return new Date(t.resolved_at) <= new Date(t.sla_deadline_at) ? 'Met' : 'Breached';
                }
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

        .litepicker .month-item-header div>.month-item-name,
        .litepicker .month-item-header div>.month-item-year {
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

        .lp-shortcuts {
            display: flex;
            gap: 4px;
            padding: 10px 16px 14px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .lp-shortcut-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #2f88d8;
            font-size: 13px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 6px;
            transition: background 0.15s;
        }

        .lp-shortcut-btn:hover {
            background: #eff6ff;
        }

        thead button {
            color: inherit;
            text-decoration: none;
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        thead button:hover {
            color: #2f88d8;
        }
    </style>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/it/history.blade.php ENDPATH**/ ?>