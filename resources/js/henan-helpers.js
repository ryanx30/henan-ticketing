/**
 * Global frontend helper bridge exposed for legacy Blade/Alpine usage.
 * Keeps shared utility functions available while the app transitions toward modular page scripts.
 */

import {
    normalizeStatus,
    normalizePriority,
    statusBadgeClass,
    priorityBadgeClass,
    statusLabel,
    priorityLabel,
    ticketLabel,
    normalizedSlaValue,
    slaResultBadgeClass,
    slaTimeBadgeClass,
} from './utils/badges';
import {
    formatDateTime,
    formatDateTimeShort,
    formatNumber,
    formatFileSize,
    formatLiveDuration,
    formatTimeShort,
    formatDateTimeCompact,
    truncateText,
    formatCountdownClock,
} from './utils/formatter';
import { showPageAlert } from './utils/toast';

window.HenanApp = window.HenanApp || {};

Object.assign(window.HenanApp, {
    normalizeStatus,
    normalizePriority,
    statusBadgeClass,
    priorityBadgeClass,
    ticketLabel,
    statusLabel,
    priorityLabel,
    normalizedSlaValue,
    slaResultBadgeClass,
    slaTimeBadgeClass,
    formatDateTime,
    formatNumber,
    formatDateTimeShort,
    formatFileSize,
    formatLiveDuration,
    formatTimeShort,
    formatDateTimeCompact,
    truncateText,
    formatCountdownClock,
    showPageAlert,
});

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
