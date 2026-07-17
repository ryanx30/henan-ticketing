import { formatNumber } from './formatter';

/**
 * Formats dashboard KPI comparisons and maps their business sentiment to UI classes.
 */

export function formatKpiTrend(item) {
    if (!item) return '-';

    const direction = String(item.direction || 'flat').trim().toLowerCase();
    const arrow = direction === 'up'
        ? '▲'
        : (direction === 'down' ? '▼' : '');

    if (direction === 'flat') {
        return `${item.label || 'No change'} ${arrow}`;
    }

    const difference = Number(item.value);
    const label = Number.isFinite(difference)
        ? `${difference > 0 ? '+' : '-'}${formatNumber(Math.abs(difference))}`
        : (item.label || '-');

    return `${label} ${arrow}`;
}

export function kpiTrendClass(item) {
    const sentiment = String(item?.sentiment || 'neutral').trim().toLowerCase();

    if (sentiment === 'positive') {
        return 'kpi-trend-positive';
    }

    if (sentiment === 'negative') {
        return 'kpi-trend-negative';
    }

    return 'kpi-trend-neutral';
}
