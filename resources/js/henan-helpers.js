window.HenanApp = window.HenanApp || {};


window.HenanApp.normalizeStatus = function normalizeStatus(status = '') {
    return String(status || '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_')
        .replace(/^ongoing$/, 'in_progress')
        .replace(/^on_going$/, 'in_progress')
        .replace(/^waiting$/, 'waiting_info')
        .replace(/^waiting_user$/, 'waiting_info');
};

window.HenanApp.normalizePriority = function normalizePriority(priority = '') {
    return String(priority || '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_');
};

window.HenanApp.statusBadgeClass = function statusBadgeClass(status = '') {
    const normalized = window.HenanApp.normalizeStatus(status);

    const map = {
        new: 'badge-status-new',
        in_progress: 'badge-status-ongoing',
        waiting_info: 'badge-status-waiting',
        resolved: 'badge-status-resolved',
        closed: 'badge-status-closed',
    };

    return map[normalized] || 'badge-status-default';
};

window.HenanApp.priorityBadgeClass = function priorityBadgeClass(priority = '') {
    const normalized = window.HenanApp.normalizePriority(priority);

    const map = {
        critical: 'badge-priority-critical',
        high: 'badge-priority-high',
        medium: 'badge-priority-medium',
        low: 'badge-priority-low',
    };

    return map[normalized] || 'badge-priority-default';
};


window.HenanApp.ticketLabel = function ticketLabel(ticket = null) {
    const rawCode = ticket?.ticket_code ?? ticket?.id ?? ticket ?? '';
    const cleanCode = String(rawCode)
        .trim()
        .replace(/[\s#]+/g, '')
        .replace(/^T-?/i, '');

    return cleanCode ? `T-${cleanCode}` : '-';
};

window.HenanApp.statusLabel = function statusLabel(status = '') {
    const normalized = window.HenanApp.normalizeStatus(status);

    const map = {
        new: 'New',
        in_progress: 'Ongoing',
        waiting_info: 'Waiting Info',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    return map[normalized] || status || '-';
};

window.HenanApp.priorityLabel = function priorityLabel(priority = '') {
    if (!priority) return '-';

    return String(priority)
        .replace(/_/g, ' ')
        .replace(/\w\S*/g, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
};

window.HenanApp.formatDateTime = function formatDateTime(value, locale = 'id-ID') {
    if (!value) return '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return date.toLocaleString(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

window.HenanApp.formatNumber = function formatNumber(value, locale = 'id-ID') {
    return new Intl.NumberFormat(locale).format(Number(value || 0));
};


window.HenanApp.messageTitle = function messageTitle(message = null) {
    const ticketTitle = message?.ticket?.title || '';
    const subject = String(message?.subject || '').trim();

    if (ticketTitle) return ticketTitle;

    return subject
        ? subject.replace(/^Reply for\s+#?T-[A-Za-z0-9-]+\s*-\s*/i, '').trim()
        : 'Resolver update';
};

window.HenanApp.messagePreview = function messagePreview(message = null, limit = 100) {
    const body = String(message?.body || message?.subject || '-')
        .replace(/\s+/g, ' ')
        .trim();

    if (body.length <= limit) return body;

    return body.substring(0, limit) + '...';
};

window.HenanApp.participantsLabel = function participantsLabel(message = null, currentUserId = null) {
    const fromName = message?.sender?.name || message?.sender?.email || 'Unknown sender';
    const toName = message?.recipient?.name || message?.recipient?.email || 'Unknown recipient';
    const userId = Number(currentUserId || 0);

    const fromLabel = userId && Number(message?.from_user_id) === userId ? 'You' : fromName;
    const toLabel = userId && Number(message?.to_user_id) === userId ? 'You' : toName;

    return `${fromLabel} → ${toLabel}`;
};

window.HenanApp.isUnreadForUser = function isUnreadForUser(message = null, currentUserId = null) {
    return Boolean(message)
        && !message.is_read
        && Number(message.to_user_id) === Number(currentUserId || 0);
};

window.HenanApp.formatDateTimeShort = function formatDateTimeShort(value, locale = 'id-ID') {
    if (!value) return '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    const datePart = date.toLocaleDateString(locale, {
        day: '2-digit',
        month: 'short',
    });

    const timePart = date.toLocaleTimeString(locale, {
        hour: '2-digit',
        minute: '2-digit',
    });

    return `${datePart}, ${timePart}`;
};

window.HenanApp.showPageAlert = function showPageAlert(message, type = 'success', elementId = 'page-alert') {
    const alert = document.getElementById(elementId);

    if (!alert) return;

    alert.textContent = message;
    alert.className = 'mb-4 rounded p-3 text-sm ' + (
        type === 'error'
            ? 'bg-red-50 text-red-700 border border-red-200'
            : 'bg-green-50 text-green-700 border border-green-200'
    );

    alert.classList.remove('hidden');

    setTimeout(() => {
        alert.classList.add('hidden');
    }, 3500);
};

window.HenanApp.routes = Object.freeze({
    dashboard: '/api/dashboard',
    tickets: '/tickets',
    adminUsers: '/admin/users',
    ticketDetail: (ticketId) => `/tickets/${ticketId}`,
    resolverInboxDetail: (messageId) => `/resolver-inbox/${messageId}`,
    api: {
        dashboard: '/api/dashboard',
        caseAnalytics: '/api/case-analytics',
        caseAnalyticsExport: '/api/case-analytics/export',
        exportsStatus: (batchId) => `/api/exports/${batchId}/status`,
        exportsDownload: (batchId) => `/api/exports/${batchId}/download`,
    },
});
