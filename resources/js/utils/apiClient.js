/**
 * Centralized internal API client used by page modules.
 * Normalizes legacy and standard API responses, applies CSRF headers, and keeps fetch/error handling consistent.
 */

const DEFAULT_HEADERS = Object.freeze({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
});

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function buildQueryString(params = {}) {
    const query = new URLSearchParams();

    Object.entries(params || {}).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') return;
        if (value === 'all') return;
        query.set(key, value);
    });

    return query.toString();
}

async function parseJsonResponse(response) {
    const text = await response.text();

    if (!text) return null;

    try {
        return JSON.parse(text);
    } catch (error) {
        throw new Error('Invalid JSON response from server');
    }
}

function isPlainObject(value) {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

export function normalizeApiResponse(payload) {
    if (!isPlainObject(payload)) {
        return payload;
    }

    const normalized = { ...payload };
    const data = normalized.data;

    // Stage 3 transition adapter:
    // Some legacy pages still read result.meta / result.extra directly, while newer
    // endpoints may place meta-like values inside result.data. Mirror them safely so
    // both contracts work during the incremental API response cleanup.
    if (isPlainObject(data)) {
        if (normalized.meta === undefined && data.meta !== undefined) {
            normalized.meta = data.meta;
        }

        if (normalized.extra === undefined && data.extra !== undefined) {
            normalized.extra = data.extra;
        }

        if (normalized.links === undefined && data.links !== undefined) {
            normalized.links = data.links;
        }
    }

    // Keep compatibility with very old `{ success: true/false }` responses while
    // the official project standard is now `{ status: true/false }`.
    if (normalized.status === undefined && typeof normalized.success === 'boolean') {
        normalized.status = normalized.success;
    }

    return normalized;
}

export function getValidationErrors(error) {
    return error?.payload?.errors || {};
}

export function firstValidationMessage(error, fallback = 'Validation failed.') {
    const errors = getValidationErrors(error);

    for (const messages of Object.values(errors)) {
        if (Array.isArray(messages) && messages.length > 0) {
            return messages[0];
        }

        if (typeof messages === 'string' && messages.trim() !== '') {
            return messages;
        }
    }

    return error?.message || fallback;
}

export async function apiRequest(url, options = {}) {
    const method = String(options.method || 'GET').toUpperCase();
    const headers = {
        ...DEFAULT_HEADERS,
        ...(options.headers || {}),
    };

    const requestOptions = {
        credentials: 'same-origin',
        ...options,
        method,
        headers,
    };

    if (!['GET', 'HEAD'].includes(method) && csrfToken() && !headers['X-CSRF-TOKEN']) {
        requestOptions.headers['X-CSRF-TOKEN'] = csrfToken();
    }

    if (options.body && !(options.body instanceof FormData) && typeof options.body !== 'string') {
        requestOptions.headers['Content-Type'] = requestOptions.headers['Content-Type'] || 'application/json';
        requestOptions.body = JSON.stringify(options.body);
    }

    const response = await fetch(url, requestOptions);
    const parsedPayload = await parseJsonResponse(response);
    const fallbackPayload = response.ok
        ? { status: true, message: response.statusText || 'OK', data: null }
        : { status: false, message: `Request failed with status ${response.status}` };
    const payload = normalizeApiResponse(parsedPayload ?? fallbackPayload);

    if (isPlainObject(payload) && payload.http_status === undefined) {
        payload.http_status = response.status;
    }

    if (!response.ok || payload?.status === false || payload?.success === false) {
        const error = new Error(payload?.message || `Request failed with status ${response.status}`);
        error.status = response.status;
        error.payload = payload;
        error.errors = payload?.errors || {};
        throw error;
    }

    return payload;
}

export function apiGet(url, options = {}) {
    return apiRequest(url, { ...options, method: 'GET' });
}

export function apiPost(url, body = {}, options = {}) {
    return apiRequest(url, { ...options, method: 'POST', body });
}

export function apiPatch(url, body = {}, options = {}) {
    return apiRequest(url, { ...options, method: 'PATCH', body });
}

export function apiDelete(url, options = {}) {
    return apiRequest(url, { ...options, method: 'DELETE' });
}
