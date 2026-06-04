/**
 * Shared badge rendering helpers for ticket status, priority, SLA, and related labels.
 * Keeps color and label mapping consistent across dashboard, tickets, reports, and queue pages.
 */

export function normalizeStatus(status = '') {
    return String(status || '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_')
        .replace(/^ongoing$/, 'in_progress')
        .replace(/^on_going$/, 'in_progress')
        .replace(/^waiting$/, 'waiting_info')
        .replace(/^waiting_user$/, 'waiting_info');
}

export function normalizePriority(priority = '') {
    return String(priority || '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_');
}

export function statusBadgeClass(status = '') {
    const map = {
        new: 'badge-status-new',
        in_progress: 'badge-status-ongoing',
        waiting_info: 'badge-status-waiting',
        resolved: 'badge-status-resolved',
        closed: 'badge-status-closed',
    };

    return map[normalizeStatus(status)] || 'badge-status-default';
}

export function priorityBadgeClass(priority = '') {
    const map = {
        critical: 'badge-priority-critical',
        high: 'badge-priority-high',
        medium: 'badge-priority-medium',
        low: 'badge-priority-low',
    };

    return map[normalizePriority(priority)] || 'badge-priority-default';
}

export function statusLabel(status = '') {
    const map = {
        new: 'New',
        in_progress: 'Ongoing',
        waiting_info: 'Waiting Info',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    return map[normalizeStatus(status)] || status || '-';
}

export function priorityLabel(priority = '') {
    if (!priority) return '-';

    return String(priority)
        .replace(/_/g, ' ')
        .replace(/\w\S*/g, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
}

export function ticketLabel(ticket = null) {
    const rawCode = ticket?.ticket_code ?? ticket?.id ?? ticket ?? '';
    const cleanCode = String(rawCode)
        .trim()
        .replace(/[\s#]+/g, '')
        .replace(/^T-?/i, '');

    return cleanCode ? `T-${cleanCode}` : '-';
}


export function normalizedSlaValue(value = '') {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replaceAll('-', '_')
        .replaceAll(' ', '_');
}

export function slaResultBadgeClass(result = '') {
    const normalized = normalizedSlaValue(result);

    if (normalized === 'ok') return 'badge-sla-result-ok';
    if (normalized === 'breach' || normalized === 'breached') return 'badge-sla-result-breach';
    if (normalized === 'open') return 'badge-sla-result-open';
    if (normalized === 'closed' || normalized === 'direct_close') return 'badge-sla-result-closed';

    return 'badge-sla-result-default';
}

export function slaTimeBadgeClass(slaTime = '', result = '') {
    const timeText = String(slaTime || '').trim().toLowerCase();
    const normalizedResult = normalizedSlaValue(result);

    if (!timeText || timeText === '-') {
        return 'badge-sla-time-default';
    }

    if (normalizedResult === 'breach' || timeText.startsWith('overdue') || timeText.startsWith('breached by')) {
        return 'badge-sla-time-breach';
    }

    if (normalizedResult === 'ok' || timeText.startsWith('met by')) {
        return 'badge-sla-time-met';
    }

    if (normalizedResult === 'closed' || timeText.startsWith('direct close')) {
        return 'badge-sla-time-closed';
    }

    if (normalizedResult === 'open' || timeText.endsWith('left') || timeText.startsWith('no sla')) {
        return 'badge-sla-time-open';
    }

    return 'badge-sla-time-default';
}
