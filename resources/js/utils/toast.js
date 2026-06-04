/**
 * Reusable toast/page alert helpers.
 * Provides a consistent user feedback pattern for success, warning, and error states.
 */

const ALERT_CLASSES = [
    'bg-green-100',
    'text-green-800',
    'bg-red-100',
    'text-red-800',
    'bg-green-50',
    'text-green-700',
    'border',
    'border-green-200',
    'bg-red-50',
    'text-red-700',
    'border-red-200',
];

export function showAlert(message, type = 'success', elementId = 'page-alert', timeout = 3000) {
    const element = document.getElementById(elementId);

    if (!element) return;

    element.textContent = message;
    element.classList.remove('hidden', ...ALERT_CLASSES);
    element.classList.add(type === 'error' ? 'bg-red-100' : 'bg-green-100');
    element.classList.add(type === 'error' ? 'text-red-800' : 'text-green-800');

    if (timeout) {
        setTimeout(() => element.classList.add('hidden'), timeout);
    }
}

export function showPageAlert(message, type = 'success', elementId = 'page-alert', timeout = 3500) {
    const element = document.getElementById(elementId);

    if (!element) return;

    element.textContent = message;
    element.className = 'mb-4 rounded p-3 text-sm ' + (
        type === 'error'
            ? 'bg-red-50 text-red-700 border border-red-200'
            : 'bg-green-50 text-green-700 border border-green-200'
    );

    element.classList.remove('hidden');

    if (timeout) {
        setTimeout(() => element.classList.add('hidden'), timeout);
    }
}
