/**
 * Shared formatting helpers for dates, numbers, durations, text labels, and countdown display.
 * Avoids duplicated formatter logic inside individual page modules.
 */

export function titleCase(value = '') {
    if (!value) return '';

    return String(value)
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

export function formatDate(value, locale = 'en-GB') {
    if (!value) return '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return date.toLocaleDateString(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function formatHumanDate(value, locale = 'en-GB') {
    if (!value) return '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return date.toLocaleDateString(locale, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

export function formatDateTime(value, locale = 'id-ID') {
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
}

export function formatDateTimeShort(value, locale = 'id-ID') {
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
}


export function formatTimeShort(value, locale = 'id-ID') {
    if (!value) return '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return date.toLocaleTimeString(locale, {
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function formatDateTimeCompact(value, locale = 'id-ID') {
    if (!value) return '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return date.toLocaleString(locale, {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function truncateText(value = '', limit = 70, suffix = '...') {
    const text = String(value || '');

    if (text.length <= limit) return text;

    return text.substring(0, limit) + suffix;
}

export function formatCountdownClock(value) {
    if (!value) return '-';

    const deadline = value instanceof Date ? value.getTime() : new Date(value).getTime();

    if (Number.isNaN(deadline)) return '-';

    const diff = deadline - Date.now();

    if (diff <= 0) return 'OVERDUE';

    const totalSeconds = Math.floor(diff / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (number) => String(number).padStart(2, '0');

    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
}

export function formatNumber(value, locale = 'id-ID') {
    return new Intl.NumberFormat(locale).format(Number(value || 0));
}

export function toYmd(date) {
    const value = date instanceof Date ? date : new Date(date);

    if (Number.isNaN(value.getTime())) return '';

    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}


export function formatFileSize(size) {
    const bytes = Number(size || 0);
    if (!bytes) return 'Unknown size';

    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function formatLiveDuration(diffMs) {
    const safeDiff = Math.max(0, Math.abs(Number(diffMs) || 0));
    const totalSeconds = Math.floor(safeDiff / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (value) => String(value).padStart(2, '0');

    if (days > 0) {
        return `${days}d ${pad(hours)}h ${pad(minutes)}m`;
    }

    return `${pad(hours)}h ${pad(minutes)}m ${pad(seconds)}s`;
}
