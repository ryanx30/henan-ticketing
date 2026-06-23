/**
 * IT History page controller.
 * Loads resolved/closed ticket history, handles filters, sorting, pagination, and export actions.
 */

import { apiGet } from '../../utils/apiClient';
import { formatDateTimeShort as formatDateTimeShortValue, formatHumanDate as formatHumanDateValue, titleCase, toYmd as toYmdValue } from '../../utils/formatter';
import { showPageAlert } from '../../utils/toast';

const historyExportUrl = "/api/it/history/export";

        function historyPage() {
            return {
                loading: false,
                exportOpen: false,
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
                    status: '',
                    sort_by: 'resolved_at',
                    sort_dir: 'desc',
                    per_page: '10',
                    page: 1,
                },

                init() {
                    window.historyPageRef = this;

                    const params = new URLSearchParams(window.location.search);
                    this.filters.q = params.get('q') || '';
                    this.filters.date_from = params.get('date_from') || '';
                    this.filters.date_to = params.get('date_to') || '';
                    this.filters.status = params.get('status') || '';
                    this.filters.sort_by = params.get('sort_by') || 'resolved_at';
                    this.filters.sort_dir = params.get('sort_dir') || 'desc';
                    this.filters.per_page = params.get('per_page') || '10';
                    this.filters.page = Number(params.get('page') || 1);

                    this.initDatePicker();
                    this.loadHistory();
                },

                showAlert(message, type = 'success') {
                    showPageAlert(message, type);
                },

                buildQuery() {
                    const params = new URLSearchParams();

                    if (this.filters.q) params.set('q', this.filters.q);
                    if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                    if (this.filters.date_to) params.set('date_to', this.filters.date_to);
                    if (this.filters.status) params.set('status', this.filters.status);

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

                openTicket(ticketId) {
                    if (!ticketId) return;
                    window.location.href = `/tickets/${ticketId}`;
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

                async exportData(format) {
                    this.exportOpen = false;

                    const params = this.buildQuery();
                    params.set('format', format);

                    try {
                        await window.HenanExportQueue.queueExport(`${historyExportUrl}?${params.toString()}`, {
                            onQueued: (_payload, message) => this.showAlert(message || 'Ticket history export has been queued.', 'success'),
                            onReady: () => this.showAlert('Export is ready. Downloading file...', 'success'),
                            onError: (error) => this.showAlert(error.message || 'Failed to export ticket history.', 'error'),
                        });
                    } catch (error) {
                        // Error is already shown by the shared helper callback.
                    }
                },

                async loadHistory() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const result = await apiGet(`/api/it/history?${params.toString()}`);
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

                paginationItems() {
                    const current = Number(this.meta.current_page || 1);
                    const last = Number(this.meta.last_page || 1);

                    if (last <= 7) {
                        return Array.from({
                            length: last
                        }, (_, i) => i + 1);
                    }

                    const items = [1];

                    if (current > 4) {
                        items.push('...');
                    }

                    const start = Math.max(2, current - 1);
                    const end = Math.min(last - 1, current + 1);

                    for (let i = start; i <= end; i++) {
                        items.push(i);
                    }

                    if (current < last - 3) {
                        items.push('...');
                    }

                    items.push(last);

                    return items;
                },

                renderPagination() {
                    const container = document.getElementById('history-pagination');
                    if (!container) return;

                    if (!this.meta.last_page || this.meta.last_page <= 1) {
                        container.innerHTML = '';
                        return;
                    }

                    const items = this.paginationItems();
                    let html = '';

                    if (this.meta.current_page > 1) {
                        html += `
            <button
                type="button"
                onclick="window.historyPageRef.goToPage(${this.meta.current_page - 1})"
                class="px-3 py-1 border rounded text-sm bg-white text-slate-700 hover:bg-slate-50">
                ‹
            </button>
        `;
                    }

                    items.forEach((item) => {
                        if (item === '...') {
                            html += `
                <span class="px-2 py-1 text-sm text-slate-500 select-none">...</span>
            `;
                            return;
                        }

                        html += `
            <button
                type="button"
                onclick="window.historyPageRef.goToPage(${item})"
                class="px-3 py-1 border rounded text-sm ${item === this.meta.current_page ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50'}">
                ${item}
            </button>
        `;
                    });

                    if (this.meta.current_page < this.meta.last_page) {
                        html += `
            <button
                type="button"
                onclick="window.historyPageRef.goToPage(${this.meta.current_page + 1})"
                class="px-3 py-1 border rounded text-sm bg-white text-slate-700 hover:bg-slate-50">
                ›
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
                    return formatHumanDateValue(value, 'en-US');
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
                    return toYmdValue(d);
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
                    return window.HenanApp?.ticketLabel(t) ?? '-';
                },

                resolvedLabel(t) {
                    const value = t.resolved_at || t.closed_at || t.updated_at || t.created_at;
                    return formatDateTimeShortValue(value, 'en-GB');
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
                    const finishedAt = t.resolved_at || t.closed_at;
                    if (!t.sla_deadline_at || !finishedAt) return '';

                    return new Date(finishedAt) <= new Date(t.sla_deadline_at) ? 'Met' : 'Breached';
                }
            }
        }

window.historyPage = historyPage;
