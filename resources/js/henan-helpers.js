window.HenanApp = window.HenanApp || {};

window.HenanApp.ticketLabel = function ticketLabel(ticket = null) {
    const rawCode = ticket?.ticket_code ?? ticket?.id ?? ticket ?? '';
    const cleanCode = String(rawCode)
        .trim()
        .replace(/[\s#]+/g, '')
        .replace(/^T-?/i, '');

    return cleanCode ? `T-${cleanCode}` : '-';
};

window.HenanApp.statusLabel = function statusLabel(status = '') {
    const map = {
        new: 'New',
        in_progress: 'On Going',
        waiting_info: 'Waiting Info',
        resolved: 'Resolved',
        closed: 'Closed',
    };

    return map[status] || status || '-';
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
