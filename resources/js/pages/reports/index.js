/**
 * Reports page controller.
 * Loads report cards, trend chart, SLA table rows, pagination, filters, and export actions.
 */

import { apiGet } from '../../utils/apiClient';
import { formatHumanDate, toYmd } from '../../utils/formatter';
import { statusBadgeClass as buildStatusBadgeClass, statusLabel as buildStatusLabel, slaResultBadgeClass, slaTimeBadgeClass as buildSlaTimeBadgeClass } from '../../utils/badges';
import { showAlert as showPageAlert } from '../../utils/toast';

function reportsPage() {
            return {
                loading: false,

                filters: {
                    range: '1w',
                    date_from: '',
                    date_to: '',
                    scope: 'my',
                    per_page: '10',
                },

                cards: {
                    resolved: 0,
                    avg_response_seconds: 0,
                    avg_response_label: '0m',
                    reopen_rate: 0,
                    sla_risk: 0,
                },

                // API-driven KPI cards. Labels change per role: IT, CS, Admin, or Supervisor.
                cardItems: [
                    { key: 'loading-1', label: 'Total Tickets', value: '0', description: '' },
                    { key: 'loading-2', label: 'Active Tickets', value: '0', description: '' },
                    { key: 'loading-3', label: 'Completed Tickets', value: '0', description: '' },
                    { key: 'loading-4', label: 'SLA Breach Rate', value: '0%', description: '' },
                ],

                trend: {
                    labels: [],
                    values: [],
                },

                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 10,
                    total: 0,
                    from: null,
                    to: null,
                },

                meta: {
                    table_labels: {
                        sla_time: 'SLA Remaining / Outcome',
                        result: 'SLA Result',
                    },
                },

                rows: [],
                picker: null,
                trendChart: null,

                init() {
                    const params = new URLSearchParams(window.location.search);
                    this.filters.range = params.get('range') || '1w';
                    this.filters.date_from = params.get('date_from') || '';
                    this.filters.date_to = params.get('date_to') || '';
                    this.filters.scope = params.get('scope') || 'my';
                    this.filters.per_page = params.get('per_page') || '10';
                    this.pagination.current_page = Number(params.get('page') || 1);

                    this.$nextTick(() => {
                        this.initDatePicker();
                    });

                    this.loadReports();

                    this._resizeHandler = () => {
                        if (this.trendChart) {
                            this.trendChart.resize();
                        }
                    };

                    window.addEventListener('resize', this._resizeHandler);
                },

                destroy() {
                    if (this.trendChart) {
                        this.trendChart.destroy();
                        this.trendChart = null;
                    }

                    if (this._resizeHandler) {
                        window.removeEventListener('resize', this._resizeHandler);
                    }
                },

                onRangeChange() {
                    if (this.filters.range !== 'custom') {
                        this.filters.date_from = '';
                        this.filters.date_to = '';

                        if (this.picker) {
                            this.picker.destroy();
                            this.picker = null;
                        }
                    }

                    this.$nextTick(() => {
                        this.initDatePicker();
                    });

                    this.applyFilters();
                },

                buildQuery(includePage = true) {
                    const params = new URLSearchParams();
                    params.set('range', this.filters.range);
                    params.set('scope', this.filters.scope);
                    params.set('per_page', this.filters.per_page);

                    if (this.filters.range === 'custom') {
                        if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                        if (this.filters.date_to) params.set('date_to', this.filters.date_to);
                    }

                    if (includePage) {
                        params.set('page', this.pagination.current_page || 1);
                    }

                    return params;
                },

                applyFilters() {
                    this.pagination.current_page = 1;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadReports();
                },

                async loadReports() {
                    this.loading = true;

                    try {
                        const params = this.buildQuery();
                        const result = await apiGet(`/api/reports?${params.toString()}`);

                        const data = result.data || {};
                        this.cards = data.cards || this.cards;
                        this.cardItems = Array.isArray(data.card_items) && data.card_items.length
                            ? data.card_items
                            : this.cardItems;
                        this.trend = data.trend || this.trend;
                        this.rows = data.rows || [];
                        this.pagination = data.pagination || this.pagination;
                        this.meta = data.meta || this.meta;
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load reports', 'error');
                    } finally {
                        this.loading = false;

                        this.$nextTick(() => {
                            requestAnimationFrame(() => {
                                this.renderTrendChart(0);
                            });
                        });
                    }
                },

                goToPage(page) {
                    if (page < 1 || page > this.pagination.last_page) return;

                    this.pagination.current_page = page;
                    const params = this.buildQuery();
                    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                    this.loadReports();
                },

                visiblePages() {
                    const current = this.pagination.current_page || 1;
                    const last = this.pagination.last_page || 1;

                    if (last <= 7) {
                        return Array.from({ length: last }, (_, i) => i + 1);
                    }

                    const pages = [1];

                    if (current > 3) {
                        pages.push('...');
                    }

                    const start = Math.max(2, current - 1);
                    const end = Math.min(last - 1, current + 1);

                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }

                    if (current < last - 2) {
                        pages.push('...');
                    }

                    pages.push(last);

                    return pages;
                },

                async exportCsv() {
                    const params = this.buildQuery(false);

                    try {
                        await window.HenanExportQueue.queueExport(`/api/reports/export?${params.toString()}`, {
                            onQueued: (_payload, message) => this.showAlert(message || 'Report export has been queued.', 'success'),
                            onReady: () => this.showAlert('Export is ready. Downloading file...', 'success'),
                            onError: (error) => this.showAlert(error.message || 'Failed to export report.', 'error'),
                        });
                    } catch (error) {
                        // Error is already shown by the shared helper callback.
                    }
                },

                showAlert(message, type = 'success') {
                    showPageAlert(message, type);
                },

                initDatePicker() {
                    if (this.filters.range !== 'custom') return;

                    const trigger = document.getElementById('dateRangeTrigger');
                    if (!trigger) return;

                    if (this.picker) {
                        this.picker.destroy();
                        this.picker = null;
                    }

                    const self = this;

                    this.picker = new Litepicker({
                        element: trigger,
                        singleMode: false,
                        numberOfMonths: 2,
                        numberOfColumns: 2,
                        format: 'YYYY-MM-DD',
                        autoApply: false,
                        showTooltip: true,
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

                toYmd(date) {
                    return toYmd(date);
                },

                dateLabel() {
                    if (this.filters.date_from && this.filters.date_to) {
                        return `${this.formatHumanDate(this.filters.date_from)} → ${this.formatHumanDate(this.filters.date_to)}`;
                    }
                    return 'Select date range';
                },

                formatHumanDate(value) {
                    return formatHumanDate(value, 'en-US');
                },

                statusLabel(status) {
                    return buildStatusLabel(status);
                },

                statusBadgeClass(status) {
                    return buildStatusBadgeClass(status);
                },

                // REPORT SLA BADGES: Use shared utility classes so JavaScript-rendered badges stay consistent.
                resultBadgeClass(result) {
                    return slaResultBadgeClass(result);
                },

                slaTimeBadgeClass(slaTime, result) {
                    return buildSlaTimeBadgeClass(slaTime, result);
                },

                computedYMax() {
                    const values = Array.isArray(this.trend.values) ? this.trend.values : [];
                    const maxValue = Math.max(...values, 0);

                    if (maxValue <= 10) return 10;
                    return Math.ceil(maxValue / 10) * 10;
                },

                renderTrendChart(retry = 0) {
                    const canvas = document.getElementById('reportsTrendChart');

                    if (!canvas) {
                        if (retry < 30) {
                            setTimeout(() => this.renderTrendChart(retry + 1), 200);
                        }
                        return;
                    }

                    if (typeof Chart === 'undefined') {
                        if (retry < 30) {
                            setTimeout(() => this.renderTrendChart(retry + 1), 200);
                        }
                        return;
                    }

                    if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
                        if (retry < 30) {
                            setTimeout(() => this.renderTrendChart(retry + 1), 200);
                        }
                        return;
                    }

                    const labels = this.trend.labels || [];
                    const values = this.trend.values || [];

                    if (!labels.length) {
                        if (this.trendChart) {
                            this.trendChart.destroy();
                            this.trendChart = null;
                        }
                        return;
                    }

                    if (this.trendChart) {
                        this.trendChart.destroy();
                        this.trendChart = null;
                    }

                    const yMax = this.computedYMax();

                    this.trendChart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Resolved / Closed Tickets',
                                data: values,
                                borderColor: '#051823',
                                backgroundColor: '#051823',
                                pointBackgroundColor: '#051823',
                                pointBorderColor: '#051823',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointHitRadius: 14,
                                borderWidth: 3,
                                tension: 0,
                                fill: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            interaction: {
                                mode: 'nearest',
                                intersect: true,
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: '#051823',
                                    titleColor: '#ffffff',
                                    bodyColor: '#ffffff',
                                    displayColors: false,
                                    callbacks: {
                                        title: (items) => items?.[0]?.label || '',
                                        label: (context) => `${context.parsed.y} ticket(s)`,
                                    }
                                }
                            },
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 10,
                                    top: 10,
                                    bottom: 0,
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: '#e5e7eb',
                                    },
                                    ticks: {
                                        color: '#334155',
                                        font: {
                                            size: 11,
                                        }
                                    },
                                    border: {
                                        color: '#94a3b8',
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    max: yMax,
                                    ticks: {
                                        stepSize: 10,
                                        color: '#334155',
                                        font: {
                                            size: 11,
                                        },
                                        precision: 0,
                                    },
                                    grid: {
                                        color: '#e5e7eb',
                                    },
                                    border: {
                                        color: '#94a3b8',
                                    }
                                }
                            }
                        }
                    });
                },
            }
        }

window.reportsPage = reportsPage;
