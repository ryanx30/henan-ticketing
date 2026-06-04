/**
 * Adds client-side export-ready notifications to the navbar notification dropdown.
 */

const STORAGE_KEY = 'henan_export_notifications_v1';
const MAX_EXPORT_NOTIFICATIONS = 5;
const MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;

function readExportNotifications() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        const items = raw ? JSON.parse(raw) : [];
        const now = Date.now();

        return Array.isArray(items)
            ? items.filter((item) => item && item.id && (now - Date.parse(item.created_at || now)) < MAX_AGE_MS)
            : [];
    } catch (error) {
        return [];
    }
}

function writeExportNotifications(items) {
    window.localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify(items.slice(0, MAX_EXPORT_NOTIFICATIONS))
    );
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

function exportNotificationMarkup(item) {
    const href = escapeHtml(item.url || '#');

    return `
        <a
            href="${href}"
            data-export-notification-id="${escapeHtml(item.id)}"
            style="display:block;padding:12px 16px;border-bottom:1px solid #f1f5f9;text-decoration:none;transition:background-color .15s ease;"
            onmouseover="this.style.backgroundColor='#eff6ff'"
            onmouseout="this.style.backgroundColor='transparent'">
            <div style="display:flex;gap:12px;">
                <span style="width:10px;height:10px;border-radius:9999px;background:#16a34a;flex-shrink:0;margin-top:4px;"></span>
                <div style="min-width:0;flex:1;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                        <div style="min-width:0;">
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8;">${escapeHtml(item.label)}</div>
                            <div style="font-size:14px;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(item.title)}</div>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;white-space:nowrap;">${escapeHtml(formatTime(item.created_at))}</div>
                    </div>
                    <p style="margin:4px 0 0 0;font-size:12px;color:#475569;line-height:1.35;">${escapeHtml(item.description)}</p>
                    <div style="margin-top:4px;font-size:11px;font-weight:600;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(item.meta)}</div>
                </div>
            </div>
        </a>`;
}

function updateNotificationCount(exportCount) {
    const badge = document.querySelector('[data-notification-count]');
    const summary = document.querySelector('[data-notification-summary]');

    if (!badge) {
        return;
    }

    const baseCount = Number.parseInt(badge.dataset.baseCount || '0', 10) || 0;
    const total = baseCount + exportCount;

    badge.textContent = total > 99 ? '99+' : String(total);
    badge.classList.toggle('hidden', total <= 0);

    if (summary) {
        summary.textContent = `${total} active`;
    }
}

function renderExportNotifications() {
    const container = document.querySelector('[data-export-notifications]');
    const emptyState = document.querySelector('[data-notification-empty]');

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

    if (emptyState && items.length > 0) {
        emptyState.style.display = 'none';
    }

    updateNotificationCount(items.length);
}

window.addEventListener('henan:export-ready', (event) => {
    addExportNotification(event.detail || {});
});

document.addEventListener('DOMContentLoaded', renderExportNotifications);
