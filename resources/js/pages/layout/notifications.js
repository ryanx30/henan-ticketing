/**
 * Keeps navbar notifications fresh, preserves per-user read state through the
 * internal API, and retains queued-export notifications in the current browser.
 */

import { apiGet, apiPost } from '../../utils/apiClient';

const EXPORT_STORAGE_KEY = 'henan_export_notifications_v1';
const MAX_EXPORT_NOTIFICATIONS = 5;
const MAX_EXPORT_AGE_MS = 7 * 24 * 60 * 60 * 1000;
const REFRESH_INTERVAL_MS = 60 * 1000;

let serverPayload = {
    unread_count: 0,
    action_count: 0,
    total_count: 0,
    latest: [],
};
let refreshPromise = null;

function readExportNotifications() {
    try {
        const raw = window.localStorage.getItem(EXPORT_STORAGE_KEY);
        const items = raw ? JSON.parse(raw) : [];
        const now = Date.now();

        return Array.isArray(items)
            ? items.filter((item) => item && item.id && (now - Date.parse(item.created_at || now)) < MAX_EXPORT_AGE_MS)
            : [];
    } catch (error) {
        return [];
    }
}

function writeExportNotifications(items) {
    try {
        window.localStorage.setItem(
            EXPORT_STORAGE_KEY,
            JSON.stringify(items.slice(0, MAX_EXPORT_NOTIFICATIONS))
        );
    } catch (error) {
        // Local export notifications are optional. Server notifications still work.
    }
}

function addExportNotification(payload) {
    const items = readExportNotifications();
    const id = `export:${payload.batch_id || Date.now()}`;
    const notification = {
        id,
        label: 'Export Ready',
        title: 'Your export is ready',
        description: payload.filename || 'Download generated export file',
        meta: 'Queued export completed',
        url: payload.download_url || '#',
        created_at: new Date().toISOString(),
    };

    writeExportNotifications([
        notification,
        ...items.filter((item) => item.id !== id),
    ]);

    renderExportNotifications();
}

function removeExportNotification(id) {
    writeExportNotifications(readExportNotifications().filter((item) => item.id !== id));
    renderExportNotifications();
}

function formatTime(isoString) {
    const timestamp = Date.parse(isoString);

    if (!timestamp) {
        return 'just now';
    }

    const diffSeconds = Math.max(1, Math.floor((Date.now() - timestamp) / 1000));

    if (diffSeconds < 60) {
        return 'just now';
    }

    const diffMinutes = Math.floor(diffSeconds / 60);

    if (diffMinutes < 60) {
        return `${diffMinutes}m ago`;
    }

    const diffHours = Math.floor(diffMinutes / 60);

    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }

    return `${Math.floor(diffHours / 24)}d ago`;
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function safeAccent(value) {
    return /^#[0-9a-f]{6}$/i.test(String(value || '')) ? value : '#2563eb';
}

function notificationMarkup(item) {
    const key = escapeHtml(item.key);
    const href = escapeHtml(item.url || '#');
    const unreadClass = item.is_unread ? 'bg-blue-50/50' : 'bg-white';
    const dismissPadding = item.can_dismiss ? 'pr-10' : '';
    const actionBadge = item.requires_action
        ? '<span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] text-amber-700">Need action</span>'
        : '';
    const dismissButton = item.can_dismiss
        ? `
            <button
                type="button"
                data-notification-dismiss="${key}"
                class="absolute right-3 top-3 rounded p-1 text-slate-300 hover:bg-slate-100 hover:text-slate-600"
                aria-label="Dismiss notification">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" />
                </svg>
            </button>`
        : '';

    return `
        <div
            data-notification-item
            data-notification-key="${key}"
            class="relative border-b border-slate-100 ${unreadClass}">
            <a
                href="${href}"
                data-notification-link
                data-notification-key="${key}"
                data-notification-unread="${item.is_unread ? '1' : '0'}"
                class="group block px-4 py-3 ${dismissPadding} hover:bg-blue-50/70 transition">
                <div class="flex gap-3">
                    <span
                        class="mt-1 h-2.5 w-2.5 rounded-full shrink-0"
                        style="background-color:${safeAccent(item.accent)};">
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                    <span>${escapeHtml(item.label)}</span>
                                    ${actionBadge}
                                </div>
                                <div class="text-sm font-semibold text-slate-800 truncate group-hover:text-blue-700">
                                    ${escapeHtml(item.title)}
                                </div>
                            </div>
                            <div class="shrink-0 text-[11px] text-slate-400 whitespace-nowrap">
                                ${escapeHtml(item.time)}
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-600 line-clamp-2">
                            ${escapeHtml(item.description)}
                        </p>
                        <div class="mt-1 text-[11px] font-medium text-slate-400 truncate">
                            ${escapeHtml(item.meta)}
                        </div>
                    </div>
                </div>
            </a>
            ${dismissButton}
        </div>`;
}

function exportNotificationMarkup(item) {
    const href = escapeHtml(item.url || '#');

    return `
        <a
            href="${href}"
            data-export-notification-id="${escapeHtml(item.id)}"
            class="group block border-b border-slate-100 bg-emerald-50/40 px-4 py-3 hover:bg-emerald-50 transition">
            <div class="flex gap-3">
                <span class="mt-1 h-2.5 w-2.5 rounded-full shrink-0 bg-emerald-600"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">${escapeHtml(item.label)}</div>
                            <div class="text-sm font-semibold text-slate-800 truncate group-hover:text-emerald-700">${escapeHtml(item.title)}</div>
                        </div>
                        <div class="shrink-0 text-[11px] text-slate-400 whitespace-nowrap">${escapeHtml(formatTime(item.created_at))}</div>
                    </div>
                    <p class="mt-1 text-xs text-slate-600 line-clamp-2">${escapeHtml(item.description)}</p>
                    <div class="mt-1 text-[11px] font-medium text-slate-400 truncate">${escapeHtml(item.meta)}</div>
                </div>
            </div>
        </a>`;
}

function renderServerNotifications() {
    const container = document.querySelector('[data-server-notifications]');

    if (!container) {
        return;
    }

    container.innerHTML = (serverPayload.latest || []).map(notificationMarkup).join('');
    bindServerNotificationActions(container);
    updateNotificationUi();
}

function renderExportNotifications() {
    const container = document.querySelector('[data-export-notifications]');

    if (!container) {
        return;
    }

    const items = readExportNotifications();
    writeExportNotifications(items);
    container.innerHTML = items.map(exportNotificationMarkup).join('');

    container.querySelectorAll('[data-export-notification-id]').forEach((link) => {
        link.addEventListener('click', () => {
            removeExportNotification(link.dataset.exportNotificationId);
        });
    });

    updateNotificationUi();
}

function bindServerNotificationActions(container = document) {
    container.querySelectorAll('[data-notification-link]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            if (link.dataset.notificationUnread !== '1') {
                return;
            }

            event.preventDefault();
            const destination = link.href;

            try {
                const response = await apiPost('/api/notifications/read', {
                    key: link.dataset.notificationKey,
                });
                applyServerPayload(response.data);
            } catch (error) {
                // Reading state must not block navigation to the ticket/message.
            }

            window.location.href = destination;
        });
    });

    container.querySelectorAll('[data-notification-dismiss]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();
            button.disabled = true;

            try {
                const response = await apiPost('/api/notifications/dismiss', {
                    key: button.dataset.notificationDismiss,
                });
                applyServerPayload(response.data);
            } catch (error) {
                button.disabled = false;
            }
        });
    });
}

function updateNotificationUi() {
    const exports = readExportNotifications();
    const exportCount = exports.length;
    const serverUnread = Number(serverPayload.unread_count || 0);
    const actionCount = Number(serverPayload.action_count || 0);
    const unreadCount = serverUnread + exportCount;
    const badge = document.querySelector('[data-notification-count]');
    const actionIndicator = document.querySelector('[data-notification-action-indicator]');
    const summary = document.querySelector('[data-notification-summary]');
    const readAllButton = document.querySelector('[data-notification-read-all]');
    const emptyState = document.querySelector('[data-notification-empty]');
    const hasServerItems = Number(serverPayload.total_count || 0) > 0 || (serverPayload.latest || []).length > 0;

    if (badge) {
        badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        badge.classList.toggle('hidden', unreadCount <= 0);
        badge.dataset.baseCount = String(serverUnread);
    }

    if (actionIndicator) {
        actionIndicator.classList.toggle('hidden', actionCount <= 0 || unreadCount > 0);
    }

    if (summary) {
        summary.textContent = `${unreadCount} unread · ${actionCount} need action`;
    }

    if (readAllButton) {
        readAllButton.classList.toggle('hidden', unreadCount <= 0);
    }

    if (emptyState) {
        emptyState.classList.toggle('hidden', hasServerItems || exportCount > 0);
    }
}

function applyServerPayload(payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    serverPayload = {
        unread_count: Number(payload.unread_count || payload.count || 0),
        action_count: Number(payload.action_count || 0),
        total_count: Number(payload.total_count || 0),
        latest: Array.isArray(payload.latest) ? payload.latest : [],
    };

    renderServerNotifications();
}

async function refreshNotifications() {
    if (!document.querySelector('[data-notification-root]')) {
        return null;
    }

    if (refreshPromise) {
        return refreshPromise;
    }

    refreshPromise = apiGet('/api/notifications?limit=7')
        .then((response) => {
            applyServerPayload(response.data);
            return response.data;
        })
        .catch(() => null)
        .finally(() => {
            refreshPromise = null;
        });

    return refreshPromise;
}

async function markAllAsRead() {
    const button = document.querySelector('[data-notification-read-all]');

    if (button) {
        button.disabled = true;
    }

    try {
        const response = await apiPost('/api/notifications/read-all');
        writeExportNotifications([]);
        applyServerPayload(response.data);
        renderExportNotifications();
    } catch (error) {
        if (button) {
            button.disabled = false;
        }
    }
}

function initializeNotifications() {
    const root = document.querySelector('[data-notification-root]');

    if (!root) {
        return;
    }

    serverPayload.unread_count = Number(root.dataset.initialUnread || 0);
    serverPayload.action_count = Number(root.dataset.initialActions || 0);
    serverPayload.total_count = document.querySelectorAll('[data-server-notifications] [data-notification-item]').length;

    bindServerNotificationActions(document.querySelector('[data-server-notifications]') || document);
    renderExportNotifications();

    document.querySelector('[data-notification-toggle]')?.addEventListener('click', () => {
        refreshNotifications();
    });

    document.querySelector('[data-notification-read-all]')?.addEventListener('click', markAllAsRead);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshNotifications();
        }
    });

    window.setInterval(() => {
        if (!document.hidden) {
            refreshNotifications();
        }
    }, REFRESH_INTERVAL_MS);

    refreshNotifications();
}

window.addEventListener('henan:export-ready', (event) => {
    addExportNotification(event.detail || {});
});

document.addEventListener('DOMContentLoaded', initializeNotifications);
