const analyticsApiUrl = window.HenanCaseAnalytics?.apiUrl ?? window.HenanApp?.routes?.api?.caseAnalytics ?? '/api/case-analytics';
const analyticsExportUrl = window.HenanCaseAnalytics?.exportUrl ?? window.HenanApp?.routes?.api?.caseAnalyticsExport ?? '/api/case-analytics/export';

const analyticsState = {
    ticketVolumeChart: null,
    peakTimeChart: null,
    filters: {
        time_range: '1y',
        team: 'all',
    },
    filterOptionsLoaded: false,
    alertTimer: null,
};

const els = {
    timeRange: document.getElementById('timeRange'),
    teamFilter: document.getElementById('teamFilter'),
    applyFiltersBtn: document.getElementById('applyFiltersBtn'),
    applyBtnSpinner: document.getElementById('applyBtnSpinner'),
    analyticsSkeleton: document.getElementById('analyticsSkeleton'),
    analyticsContent: document.getElementById('analyticsContent'),
    analyticsError: document.getElementById('analyticsError'),
    metricsGrid: document.getElementById('metricsGrid'),
    leaderboardBody: document.getElementById('leaderboardBody'),
    topTeamsList: document.getElementById('topTeamsList'),
    topIssuesList: document.getElementById('topIssuesList'),
    peakTimeBadge: document.getElementById('peakTimeBadge'),
    exportMenuBtn: document.getElementById('exportMenuBtn'),
    exportMenu: document.getElementById('exportMenu'),
    exportMenuIcon: document.getElementById('exportMenuIcon'),
    exportButtons: document.querySelectorAll('[data-export-format]'),
};

document.addEventListener('DOMContentLoaded', async () => {
    bindEvents();
    await loadAnalytics(false, true);
});

function bindEvents() {
    els.applyFiltersBtn.addEventListener('click', async () => {
        analyticsState.filters.time_range = els.timeRange.value;
        analyticsState.filters.team = els.teamFilter.value;
        await loadAnalytics(true, false);
    });

    els.exportMenuBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        toggleExportMenu();
    });

    els.exportButtons.forEach((button) => {
        button.addEventListener('click', () => {
            exportAnalytics(button.dataset.exportFormat);
            closeExportMenu();
        });
    });

    document.addEventListener('click', (event) => {
        if (!els.exportMenu.contains(event.target) && !els.exportMenuBtn.contains(event.target)) {
            closeExportMenu();
        }
    });
}

async function loadAnalytics(fromButton = false, isInitialLoad = false) {
    try {
        setLoading(true, fromButton);
        hideError();

        const params = new URLSearchParams({
            time_range: analyticsState.filters.time_range || '1y',
            team: analyticsState.filters.team || 'all',
        });

        const url = new URL(analyticsApiUrl, window.location.origin);
        url.search = params.toString();

        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to load analytics data. Status: ${response.status}`);
        }

        const responseJson = await response.json();
        const payload = responseJson.data ?? responseJson;

        if (!analyticsState.filterOptionsLoaded || isInitialLoad) {
            renderTimeRanges(payload.filters?.time_ranges || []);
            renderTeams(payload.filters?.teams || []);

            analyticsState.filters.time_range = payload.filters?.selected?.time_range || '1y';
            analyticsState.filters.team = payload.filters?.selected?.team || 'all';

            els.timeRange.value = analyticsState.filters.time_range;
            els.teamFilter.value = analyticsState.filters.team;

            analyticsState.filterOptionsLoaded = true;
        }

        renderMetrics(payload.metrics || []);
        renderTicketVolumeChart(payload.ticket_volume_trend || {});
        renderPeakTimeChart(payload.peak_time_ticket_volume || {});
        renderLeaderboard(payload.agent_performance_leaderboard || []);
        renderTopTeams(payload.top_teams?.items || []);
        renderTopIssues(payload.top_issue_types?.items || []);
    } catch (error) {
        console.error(error);
        destroyCharts();
        showError(error.message || 'Something went wrong while loading analytics.');
    } finally {
        setLoading(false, fromButton);
    }
}

function renderTimeRanges(ranges) {
    els.timeRange.innerHTML = ranges.map(range => `
        <option value="${escapeAttr(range.value)}">${escapeHtml(range.label)}</option>
    `).join('');
}

function renderTeams(teams) {
    els.teamFilter.innerHTML = `
        <option value="all">All Teams</option>
        ${teams.map(team => `<option value="${escapeAttr(team.id)}">${escapeHtml(team.name)}</option>`).join('')}
    `;
}

function toggleExportMenu() {
    const isHidden = els.exportMenu.classList.contains('hidden');

    if (isHidden) {
        els.exportMenu.classList.remove('hidden');
        els.exportMenuIcon.classList.add('rotate-180');
    } else {
        closeExportMenu();
    }
}

function closeExportMenu() {
    els.exportMenu.classList.add('hidden');
    els.exportMenuIcon.classList.remove('rotate-180');
}

async function exportAnalytics(format = 'excel') {
    analyticsState.filters.time_range = els.timeRange.value || analyticsState.filters.time_range || '1y';
    analyticsState.filters.team = els.teamFilter.value || analyticsState.filters.team || 'all';

    const params = new URLSearchParams({
        time_range: analyticsState.filters.time_range,
        team: analyticsState.filters.team,
        format,
    });

    closeExportMenu();

    try {
        if (!window.HenanExportQueue) {
            window.location.href = `${analyticsExportUrl}?${params.toString()}`;
            return;
        }
        
        await window.HenanExportQueue.queueExport(`${analyticsExportUrl}?${params.toString()}`, {
            onQueued: (_payload, message) => showAlert(message || 'Case analytics export has been queued.', 'success'),
            onReady: () => showAlert('Export is ready. Downloading file...', 'success'),
            onError: (error) => showAlert(error.message || 'Failed to export case analytics.', 'error'),
        });
    } catch (error) {
        showAlert(error.message || 'Failed to export case analytics.', 'error');
    }
}

function renderMetrics(metrics) {
    if (!metrics.length) {
        els.metricsGrid.innerHTML = '';
        return;
    }

    els.metricsGrid.innerHTML = metrics.map(metric => {
        const pillClass = resolveMetricPillClass(metric);
        const changeText = formatChange(metric.change_pct, metric.trend);

        return `
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <div class="text-sm font-medium text-slate-500 mb-2">${escapeHtml(metric.title)}</div>
                <div class="text-2xl font-bold text-slate-900 mb-3">${escapeHtml(metric.value_display)}</div>
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${pillClass}">
                        ${escapeHtml(changeText)}
                    </span>
                    <span class="text-xs text-slate-400">vs prev. period</span>
                </div>
            </div>
        `;
    }).join('');
}

function renderLeaderboard(rows) {
    const topRows = rows.slice(0, 5);

    if (!topRows.length) {
        els.leaderboardBody.innerHTML = emptyTableRow(4);
        return;
    }

    els.leaderboardBody.innerHTML = topRows.map(row => `
        <tr class="hover:bg-slate-50 transition">
            <td class="py-3 pr-3 font-semibold text-slate-800">#${escapeHtml(row.rank)}</td>
            <td class="py-3 pr-3 text-slate-700">${escapeHtml(row.agent_name)}</td>
            <td class="py-3 pr-3 text-slate-700">${escapeHtml(row.resolved_count)}</td>
            <td class="py-3 text-slate-700">${escapeHtml(row.avg_resolution_display)}</td>
        </tr>
    `).join('');
}

function renderTopTeams(rows) {
    if (!rows.length) {
        els.topTeamsList.innerHTML = emptyListState('No team data available for selected filters.');
        return;
    }

    const maxTickets = Math.max(...rows.map(row => Number(row.tickets_count || 0)), 1);

    els.topTeamsList.innerHTML = rows.slice(0, 5).map(row => {
        const tickets = Number(row.tickets_count || 0);
        const resolved = Number(row.resolved_count || 0);
        const width = Math.max(8, Math.round((tickets / maxTickets) * 100));

        return `
            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-[11px] font-bold text-white">#${escapeHtml(row.rank)}</span>
                            <div class="truncate text-sm font-semibold text-slate-900">${escapeHtml(row.team_name)}</div>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Resolved ${resolved} • Avg. ${escapeHtml(row.avg_resolution_display || '-')}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-base font-bold text-slate-900">${tickets}</div>
                        <div class="text-[11px] text-slate-500">tickets</div>
                    </div>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-slate-900" style="width: ${width}%"></div>
                </div>
            </div>
        `;
    }).join('');
}

function renderTopIssues(rows) {
    if (!rows.length) {
        els.topIssuesList.innerHTML = emptyListState('No issue data available for selected filters.');
        return;
    }

    const maxTickets = Math.max(...rows.map(row => Number(row.count || 0)), 1);

    els.topIssuesList.innerHTML = rows.slice(0, 7).map((row, index) => {
        const tickets = Number(row.count || 0);
        const width = Math.max(8, Math.round((tickets / maxTickets) * 100));

        return `
            <div class="rounded-xl border border-slate-100 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-900 truncate">#${index + 1} ${escapeHtml(row.issue_type)}</div>
                        <div class="mt-1 text-xs text-slate-500 truncate">${escapeHtml(row.category || '-')} • Team with most tickets: ${escapeHtml(String(row.top_team || '-').toUpperCase())}</div>
                    </div>
                    <div class="shrink-0 text-sm font-bold text-slate-900">${tickets}</div>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full rounded-full bg-[#2f80d1]" style="width: ${width}%"></div>
                </div>
            </div>
        `;
    }).join('');
}

function renderTicketVolumeChart(data) {
    const ctx = document.getElementById('ticketVolumeChart');

    if (analyticsState.ticketVolumeChart) {
        analyticsState.ticketVolumeChart.destroy();
    }

    analyticsState.ticketVolumeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [
                {
                    label: data.datasets?.[0]?.label || 'Incoming',
                    data: data.datasets?.[0]?.data || [],
                    borderColor: '#94a3b8',
                    backgroundColor: '#94a3b8',
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBorderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    fill: false,
                },
                {
                    label: data.datasets?.[1]?.label || 'Resolved',
                    data: data.datasets?.[1]?.data || [],
                    borderColor: '#0f172a',
                    backgroundColor: '#0f172a',
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBorderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    fill: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    max: data.y_axis_max || 10,
                    ticks: {
                        stepSize: data.step_size || 10,
                        precision: 0,
                    },
                    grid: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });
}

function renderPeakTimeChart(data) {
    const ctx = document.getElementById('peakTimeChart');

    if (analyticsState.peakTimeChart) {
        analyticsState.peakTimeChart.destroy();
    }

    const labels = data.labels || [];
    const values = data.values || [];
    const peak = values.reduce((best, value, index) => {
        return Number(value) > Number(best.value) ? { value, label: labels[index] } : best;
    }, { value: 0, label: '-' });

    if (Number(peak.value) > 0) {
        els.peakTimeBadge.textContent = `Peak: ${peak.label}`;
        els.peakTimeBadge.classList.remove('hidden');
    } else {
        els.peakTimeBadge.classList.add('hidden');
        els.peakTimeBadge.textContent = '';
    }

    analyticsState.peakTimeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Tickets',
                data: values,
                backgroundColor: '#0f172a',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Tickets: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    max: data.y_axis_max || 10,
                    ticks: {
                        stepSize: data.step_size || 10,
                        precision: 0,
                    },
                    grid: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });
}

function resolveMetricPillClass(metric) {
    if (metric.semantic === 'neutral') {
        return metric.trend === 'up'
            ? 'bg-blue-50 text-blue-700'
            : metric.trend === 'down'
                ? 'bg-slate-100 text-slate-700'
                : 'bg-slate-100 text-slate-600';
    }

    if (metric.improved === true) return 'bg-emerald-50 text-emerald-700';
    if (metric.improved === false) return 'bg-red-50 text-red-700';

    return 'bg-slate-100 text-slate-700';
}

function formatChange(changePct, trend) {
    if (trend === 'flat') return '0.0%';

    const arrow = trend === 'up' ? '↑' : '↓';
    return `${arrow} ${Math.abs(Number(changePct || 0)).toFixed(1)}%`;
}

function setLoading(isLoading, fromButton = false) {
    if (isLoading) {
        els.analyticsSkeleton.classList.remove('hidden');
        els.analyticsContent.classList.add('hidden');
    } else {
        els.analyticsSkeleton.classList.add('hidden');
        els.analyticsContent.classList.remove('hidden');
    }

    if (fromButton) {
        els.applyFiltersBtn.disabled = isLoading;
        els.applyBtnSpinner.classList.toggle('hidden', !isLoading);
    }
}

function showAlert(message, type = 'error', autoHide = null) {
    if (analyticsState.alertTimer) {
        clearTimeout(analyticsState.alertTimer);
        analyticsState.alertTimer = null;
    }

    els.analyticsError.textContent = message;
    els.analyticsError.classList.remove(
        'hidden',
        'border-red-200', 'bg-red-50', 'text-red-700',
        'border-green-200', 'bg-green-50', 'text-green-700'
    );

    if (type === 'success') {
        els.analyticsError.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
    } else {
        els.analyticsError.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
    }

    const shouldAutoHide = autoHide ?? type === 'success';

    if (shouldAutoHide) {
        analyticsState.alertTimer = window.setTimeout(() => {
            hideError();
        }, 3500);
    }
}

function showError(message) {
    showAlert(message, 'error');
}

function hideError() {
    if (analyticsState.alertTimer) {
        clearTimeout(analyticsState.alertTimer);
        analyticsState.alertTimer = null;
    }

    els.analyticsError.classList.add('hidden');
    els.analyticsError.textContent = '';
}

function destroyCharts() {
    if (analyticsState.ticketVolumeChart) analyticsState.ticketVolumeChart.destroy();
    if (analyticsState.peakTimeChart) analyticsState.peakTimeChart.destroy();

    analyticsState.ticketVolumeChart = null;
    analyticsState.peakTimeChart = null;
}

function emptyTableRow(colspan) {
    return `
        <tr>
            <td colspan="${colspan}" class="py-6 text-center text-slate-400">
                No data available for selected filters.
            </td>
        </tr>
    `;
}

function emptyListState(message) {
    return `
        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400">
            ${escapeHtml(message)}
        </div>
    `;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/`/g, '&#096;');
}
