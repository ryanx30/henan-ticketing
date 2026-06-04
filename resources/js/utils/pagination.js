/**
 * Reusable pagination rendering utilities for API-driven table pages.
 * Keeps page navigation markup and pagination behavior consistent.
 */

export function paginationItems(meta = {}) {
    const current = Number(meta.current_page || 1);
    const last = Number(meta.last_page || 1);

    if (last <= 7) {
        return Array.from({ length: Math.max(last, 1) }, (_, index) => index + 1);
    }

    const items = [1];

    if (current > 4) items.push('...');

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let page = start; page <= end; page += 1) {
        items.push(page);
    }

    if (current < last - 3) items.push('...');

    items.push(last);

    return items;
}
