/**
 * Smart previous-page navigation for Blade shell pages.
 *
 * Keeps the last visited internal page in sessionStorage so every Back button can
 * return to the real page the user accessed before this page, including query
 * strings from filters/search/pagination. Falls back to each button's configured
 * URL when there is no safe previous page, for example after opening a page in a
 * new tab or typing the URL manually.
 */
const PREVIOUS_PAGE_KEY = 'henan.previousPageUrl';
const CURRENT_PAGE_KEY = 'henan.currentPageUrl';

function normalizeUrl(url) {
    try {
        const parsed = new URL(url, window.location.origin);

        if (parsed.origin !== window.location.origin) {
            return null;
        }

        return `${parsed.pathname}${parsed.search}${parsed.hash}`;
    } catch (_) {
        return null;
    }
}

function isNavigableInternalPage(url) {
    const normalized = normalizeUrl(url);

    if (!normalized) {
        return false;
    }

    const blockedPrefixes = [
        '/api/',
        '/logout',
        '/login',
        '/register',
        '/storage/',
        '/build/',
    ];

    return !blockedPrefixes.some((prefix) => normalized === prefix || normalized.startsWith(prefix));
}

function currentPageUrl() {
    return `${window.location.pathname}${window.location.search}${window.location.hash}`;
}

function storeCurrentPageVisit() {
    const current = currentPageUrl();

    if (!isNavigableInternalPage(current)) {
        return;
    }

    const storedCurrent = sessionStorage.getItem(CURRENT_PAGE_KEY);

    if (storedCurrent && storedCurrent !== current && isNavigableInternalPage(storedCurrent)) {
        sessionStorage.setItem(PREVIOUS_PAGE_KEY, storedCurrent);
    }

    sessionStorage.setItem(CURRENT_PAGE_KEY, current);
}

function candidateFromSession() {
    const candidate = sessionStorage.getItem(PREVIOUS_PAGE_KEY);

    if (!candidate || candidate === currentPageUrl() || !isNavigableInternalPage(candidate)) {
        return null;
    }

    return normalizeUrl(candidate);
}

function candidateFromReferrer() {
    if (!document.referrer) {
        return null;
    }

    const candidate = normalizeUrl(document.referrer);

    if (!candidate || candidate === currentPageUrl() || !isNavigableInternalPage(candidate)) {
        return null;
    }

    return candidate;
}

function fallbackFromElement(element) {
    const candidate = element?.dataset?.fallbackUrl || element?.getAttribute('href') || '/dashboard';
    const normalized = normalizeUrl(candidate);

    return normalized && isNavigableInternalPage(normalized) ? normalized : '/dashboard';
}

export function resolvePreviousPageUrl(element = null) {
    return candidateFromSession() || candidateFromReferrer() || fallbackFromElement(element);
}

function handleSmartBackClick(event) {
    const trigger = event.target.closest('[data-smart-back]');

    if (!trigger) {
        return;
    }

    event.preventDefault();

    window.location.assign(resolvePreviousPageUrl(trigger));
}

if (typeof window !== 'undefined') {
    storeCurrentPageVisit();
    window.HenanBackNavigation = { resolvePreviousPageUrl };
    document.addEventListener('click', handleSmartBackClick);
}
