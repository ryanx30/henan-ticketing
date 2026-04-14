<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tickets</h2>
    </x-slot>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
    $q = request('q', '');
    $status = request('status', 'all');
    $priority = request('priority', 'all');
    $dateFrom = request('date_from', '');
    $dateTo = request('date_to', '');
    $userRole = auth()->user()->role ?? null;
    @endphp

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div id="page-alert" class="hidden mb-4 p-3 rounded text-sm"></div>

            {{-- TOP BAR --}}
            <div class="mb-4 grid grid-cols-12 gap-6 items-center">
                <div class="col-span-12 lg:col-span-9 lg:pr-4">
                    <div class="flex w-full items-center justify-between">
                        <div id="tickets-summary" class="text-sm text-gray-500">
                            Loading tickets...
                        </div>

                        @if(in_array($userRole, ['cs', 'admin']))
                        <a href="{{ route('tickets.create') }}"
                            class="inline-flex items-center px-4 py-2 rounded bg-slate-900 text-white text-sm shadow ml-auto">
                            + Create Ticket
                        </a>
                        @endif
                    </div>
                </div>

                <div class="hidden lg:block lg:col-span-3"></div>
            </div>

            <div class="grid grid-cols-12 gap-6">

                {{-- LEFT: TABLE --}}
                <div class="col-span-12 lg:col-span-9">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full table-fixed text-sm">
                                    <thead>
                                        <tr class="border-b text-left text-gray-700">
                                            <th class="w-[110px] px-4 py-3">Ticket</th>
                                            <th class="w-[300px] px-4 py-3">Title</th>
                                            <th class="w-[90px] px-4 py-3 text-center">Status</th>
                                            <th class="w-[95px] px-4 py-3 text-center">Priority</th>
                                            <th class="w-[110px] px-4 py-3 text-center">Created By</th>
                                            <th class="w-[90px] px-4 py-3 text-center">Team</th>
                                            <th class="w-[150px] px-4 py-3 text-center">Created At</th>
                                            <th class="w-[150px] px-4 py-3 text-center">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody id="tickets-table-body">
                                        <tr>
                                            <td colspan="8" class="py-6 text-center text-gray-500">
                                                Loading...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div id="tickets-pagination" class="mt-4"></div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: FILTER --}}
                <div class="col-span-12 lg:col-span-3">
                    <div class="sticky top-4 space-y-4">
                        <div class="bg-white rounded shadow overflow-hidden">
                            <div class="px-4 py-2 bg-slate-100 text-xs font-semibold">
                                Search
                            </div>

                            <div class="p-4">
                                <form method="GET" action="{{ route('tickets.index') }}" class="space-y-3">

                                    {{-- Search --}}
                                    <div>
                                        <label class="text-xs text-gray-500">Search (Ticket / ID / Title)</label>
                                        <input
                                            type="text"
                                            name="q"
                                            value="{{ $q }}"
                                            placeholder="ex: 102010 / login / withdraw"
                                            class="mt-1 w-full border rounded px-3 py-2 text-sm" />
                                    </div>

                                    {{-- Status --}}
                                    <div>
                                        <label class="text-xs text-gray-500">Status</label>
                                        <select name="status" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                                            <option value="all" @selected($status==='all' )>All</option>
                                            <option value="new" @selected($status==='new' )>New</option>
                                            <option value="in_progress" @selected($status==='in_progress' )>On Going</option>
                                            <option value="waiting_info" @selected($status==='waiting_info' )>Waiting Info</option>
                                            <option value="resolved" @selected($status==='resolved' )>Resolved</option>
                                            <option value="closed" @selected($status==='closed' )>Closed</option>
                                        </select>
                                    </div>

                                    {{-- Priority --}}
                                    <div>
                                        <label class="text-xs text-gray-500">Priority</label>
                                        <select name="priority" class="mt-1 w-full border rounded px-3 py-2 text-sm">
                                            <option value="all" @selected($priority==='all' )>All</option>
                                            <option value="critical" @selected($priority==='critical' )>Critical</option>
                                            <option value="high" @selected($priority==='high' )>High</option>
                                            <option value="medium" @selected($priority==='medium' )>Medium</option>
                                            <option value="low" @selected($priority==='low' )>Low</option>
                                        </select>
                                    </div>

                                    {{-- Date from / to --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-xs text-gray-500">Date From</label>
                                            <input type="date" name="date_from" value="{{ $dateFrom }}"
                                                class="mt-1 w-full border rounded px-3 py-2 text-sm" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-gray-500">Date To</label>
                                            <input type="date" name="date_to" value="{{ $dateTo }}"
                                                class="mt-1 w-full border rounded px-3 py-2 text-sm" />
                                        </div>
                                    </div>

                                    <div class="pt-2 flex items-center gap-2">
                                        <button type="submit"
                                            class="flex-1 px-4 py-2 rounded bg-slate-900 text-white text-sm">
                                            Apply
                                        </button>

                                        <a href="{{ route('tickets.index') }}"
                                            class="flex-1 text-center px-4 py-2 rounded border bg-white text-sm">
                                            Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('tickets-table-body');
            const summaryEl = document.getElementById('tickets-summary');
            const paginationEl = document.getElementById('tickets-pagination');
            const alertEl = document.getElementById('page-alert');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const userRole = "{{ $userRole }}";

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function showAlert(message, type = 'success') {
                alertEl.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                alertEl.textContent = message;

                if (type === 'success') {
                    alertEl.classList.add('bg-green-100', 'text-green-800');
                } else {
                    alertEl.classList.add('bg-red-100', 'text-red-800');
                }

                setTimeout(() => {
                    alertEl.classList.add('hidden');
                }, 3000);
            }

            function formatDate(value) {
                if (!value) return '-';

                const date = new Date(value);

                return date.toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function renderStatus(status) {
                const classMap = {
                    new: 'bg-blue-100 text-blue-700',
                    in_progress: 'bg-yellow-100 text-yellow-700',
                    waiting_info: 'bg-orange-100 text-orange-700',
                    resolved: 'bg-green-100 text-green-700',
                    closed: 'bg-slate-200 text-slate-700',
                };

                const labelMap = {
                    new: 'New',
                    in_progress: 'On Going',
                    waiting_info: 'Waiting Info',
                    resolved: 'Resolved',
                    closed: 'Closed',
                };

                const classes = classMap[status] || 'bg-slate-100 text-slate-700';
                const label = labelMap[status] || status || '-';

                return `
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${classes}">
                        ${escapeHtml(label)}
                    </span>
                `;
            }

            function renderPriority(priority) {
                const classMap = {
                    critical: 'bg-red-100 text-red-700',
                    high: 'bg-pink-100 text-pink-700',
                    medium: 'bg-amber-100 text-amber-700',
                    low: 'bg-green-100 text-green-700',
                };

                const labelMap = {
                    critical: 'Critical',
                    high: 'High',
                    medium: 'Medium',
                    low: 'Low',
                };

                const classes = classMap[priority] || 'bg-slate-100 text-slate-700';
                const label = labelMap[priority] || priority || '-';

                return `
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${classes}">
                        ${escapeHtml(label)}
                    </span>
                `;
            }

            function buildActionButtons(ticket) {
                let html = `
                <a href="/tickets/${ticket.id}"
                class="inline-flex justify-center items-center min-w-[68px] px-3 py-1.5 rounded bg-slate-100 border text-xs hover:bg-slate-200 transition">
                    Open
                </a>
                `;

                if (userRole === 'admin') {
                    html += `
                    <button
                        type="button"
                        data-delete-id="${ticket.id}"
                        class="inline-flex justify-center items-center min-w-[68px] px-3 py-1.5 rounded bg-red-600 text-white text-xs hover:bg-red-700 transition">
                        Delete
                    </button>
                    `;
                }

                return html;
            }

            function renderRows(items) {
                if (!Array.isArray(items) || items.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="py-6 text-center text-gray-500">
                                No tickets found.
                            </td>
                        </tr>
                    `;
                    return;
                }

                tableBody.innerHTML = items.map(t => `
                    <tr class="border-b align-top hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono whitespace-nowrap">
                            #T-${escapeHtml(t.ticket_code ?? t.id)}
                        </td>

                        <td class="px-4 py-3">
                            <div class="line-clamp-2 break-words leading-5">
                                ${escapeHtml(t.title)}
                            </div>
                        </td>

                        <td class="px-4 py-3 text-center">
                            ${renderStatus(t.status)}
                        </td>

                        <td class="px-4 py-3 text-center">
                            ${renderPriority(t.priority)}
                        </td>

                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            ${escapeHtml(t.creator?.name ?? '-')}
                        </td>

                        <td class="px-4 py-3 text-center uppercase whitespace-nowrap">
                            ${escapeHtml(t.team ?? '-')}
                        </td>

                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            ${formatDate(t.created_at)}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                ${buildActionButtons(t)}
                            </div>
                        </td>
                    </tr>
                `).join('');

                bindDeleteButtons();
            }

            function renderPagination(meta) {
                if (!meta || !meta.last_page || meta.last_page <= 1) {
                    paginationEl.innerHTML = '';
                    return;
                }

                const params = new URLSearchParams(window.location.search);
                const currentPage = Number(meta.current_page || 1);
                let html = '<div class="flex items-center gap-2 flex-wrap">';

                for (let i = 1; i <= meta.last_page; i++) {
                    params.set('page', i);
                    const href = `?${params.toString()}`;

                    html += `
                        <a href="${href}"
                           class="px-3 py-1 border rounded text-sm ${i === currentPage ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 hover:bg-slate-50'}">
                            ${i}
                        </a>
                    `;
                }

                html += '</div>';
                paginationEl.innerHTML = html;
            }

            async function loadTickets() {
                try {
                    const params = new URLSearchParams(window.location.search);
                    const response = await fetch(`/api/tickets?${params.toString()}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const result = await response.json();

                    renderRows(result.data || []);
                    renderPagination(result.meta || null);

                    summaryEl.textContent = `Showing ${(result.data || []).length} of ${result.meta?.total ?? 0} tickets`;
                } catch (error) {
                    console.error('Failed load tickets:', error);

                    summaryEl.textContent = 'Failed to load tickets';
                    paginationEl.innerHTML = '';
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="py-6 text-center text-red-500">
                                Failed to load tickets.
                            </td>
                        </tr>
                    `;
                }
            }

            async function deleteTicket(ticketId) {
                const confirmed = confirm('Delete this ticket?');
                if (!confirmed) return;

                try {
                    const response = await fetch(`/api/tickets/${ticketId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to delete ticket');
                    }

                    showAlert(result.message || 'Ticket deleted successfully', 'success');
                    await loadTickets();
                } catch (error) {
                    console.error('Delete failed:', error);
                    showAlert(error.message || 'Failed to delete ticket', 'error');
                }
            }

            function bindDeleteButtons() {
                document.querySelectorAll('[data-delete-id]').forEach(button => {
                    button.addEventListener('click', async function() {
                        const ticketId = this.getAttribute('data-delete-id');
                        await deleteTicket(ticketId);
                    });
                });
            }

            loadTickets();
        });
    </script>
</x-app-layout>