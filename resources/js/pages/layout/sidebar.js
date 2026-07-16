/**
 * Sidebar layout controller.
 * Handles responsive navigation state, active sections, collapse behavior,
 * accessible tooltips, badge refreshes, and localStorage persistence.
 */

import { apiGet } from '../../utils/apiClient';

const SIDEBAR_COLLAPSED_KEY = 'sidebar-collapsed';
const SIDEBAR_SECTIONS_KEY = 'sidebar-open-sections';
const BADGE_REFRESH_INTERVAL_MS = 60 * 1000;

let badgeRefreshPromise = null;

function readStorage(key) {
    try {
        return window.localStorage.getItem(key);
    } catch (error) {
        return null;
    }
}

function writeStorage(key, value) {
    try {
        window.localStorage.setItem(key, value);
    } catch (error) {
        // Sidebar persistence is optional. Navigation remains functional without it.
    }
}

function parseSavedSections(value) {
    if (!value) {
        return {};
    }

    try {
        const parsed = JSON.parse(value);

        return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
            ? parsed
            : {};
    } catch (error) {
        console.warn('Failed to parse sidebar state.');
        return {};
    }
}

function staticSidebarNavigation(initialSections = {}, activeSection = null) {
    return {
        collapsed: readStorage(SIDEBAR_COLLAPSED_KEY) === '1',
        openSections: { ...initialSections },
        tooltip: {
            open: false,
            label: '',
            left: 0,
            top: 0,
        },

        init() {
            this.openSections = {
                ...this.openSections,
                ...parseSavedSections(readStorage(SIDEBAR_SECTIONS_KEY)),
            };

            if (activeSection && Object.hasOwn(this.openSections, activeSection)) {
                this.openSections[activeSection] = true;
            }

            this.persistSections();
        },

        toggleSidebar() {
            this.collapsed = !this.collapsed;
            this.hideTooltip();
            writeStorage(SIDEBAR_COLLAPSED_KEY, this.collapsed ? '1' : '0');
        },

        toggleSection(section) {
            if (!Object.hasOwn(this.openSections, section)) {
                return;
            }

            this.openSections[section] = !this.openSections[section];
            this.persistSections();
        },

        persistSections() {
            writeStorage(SIDEBAR_SECTIONS_KEY, JSON.stringify(this.openSections));
        },

        showTooltip(event, label) {
            if (!this.collapsed || !event?.currentTarget) {
                return;
            }

            const bounds = event.currentTarget.getBoundingClientRect();

            this.tooltip = {
                open: true,
                label: String(label || ''),
                left: Math.round(bounds.right + 12),
                top: Math.round(bounds.top + (bounds.height / 2)),
            };
        },

        hideTooltip() {
            this.tooltip.open = false;
        },
    };
}

function badgeLabel(count, compact) {
    if (compact && count > 9) {
        return '9+';
    }

    if (!compact && count > 99) {
        return '99+';
    }

    return String(count);
}

function updateSidebarBadges(badges = {}) {
    document.querySelectorAll('[data-sidebar-badge-key]').forEach((badge) => {
        const count = Math.max(0, Number(badges[badge.dataset.sidebarBadgeKey] || 0));
        const compact = badge.dataset.sidebarBadgeCompact === '1';

        badge.textContent = badgeLabel(count, compact);
        badge.classList.toggle('hidden', count <= 0);
    });

    document.querySelectorAll('[data-sidebar-link]').forEach((link) => {
        const label = link.dataset.sidebarLabel || 'Navigation item';
        const badge = link.querySelector('[data-sidebar-badge-key]');
        const count = badge
            ? Math.max(0, Number(badges[badge.dataset.sidebarBadgeKey] || 0))
            : 0;

        link.setAttribute('aria-label', count > 0 ? `${label}, ${count} items` : label);
    });
}

async function refreshSidebarBadges() {
    if (!document.querySelector('[data-sidebar-root]')) {
        return null;
    }

    if (badgeRefreshPromise) {
        return badgeRefreshPromise;
    }

    badgeRefreshPromise = apiGet('/api/navigation/sidebar-badges')
        .then((response) => {
            updateSidebarBadges(response.data?.badges || {});
            return response.data;
        })
        .catch(() => null)
        .finally(() => {
            badgeRefreshPromise = null;
        });

    return badgeRefreshPromise;
}

function initializeSidebarBadges() {
    if (!document.querySelector('[data-sidebar-root]')) {
        return;
    }

    refreshSidebarBadges();

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshSidebarBadges();
        }
    });

    window.addEventListener('henan:sidebar-refresh', refreshSidebarBadges);

    window.setInterval(() => {
        if (!document.hidden) {
            refreshSidebarBadges();
        }
    }, BADGE_REFRESH_INTERVAL_MS);
}

window.staticSidebarNavigation = staticSidebarNavigation;
window.refreshSidebarBadges = refreshSidebarBadges;

document.addEventListener('DOMContentLoaded', initializeSidebarBadges);

export default staticSidebarNavigation;
