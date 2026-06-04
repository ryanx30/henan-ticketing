{{-- ========= TICKETS INDEX SHELL ========= --}}
{{-- Filter, table, and pagination containers for API-backed ticket listing. --}}

@php
$initialFilters = [
    'q' => request('q', ''),
    'status' => request('status', 'all'),
    'priority' => request('priority', 'all'),
    'date_from' => request('date_from', ''),
    'date_to' => request('date_to', ''),
    'focus' => request('focus', ''),
    'mine' => request('mine', ''),
    'sort_by' => request('sort_by', 'created_at'),
    'sort_dir' => request('sort_dir', 'desc'),
    'per_page' => request('per_page', '10'),
    'page' => request('page', '1'),
];
@endphp

<x-app-layout>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />

    <div
        id="tickets-index-page"
        x-data="ticketsIndexPage({ initialFilters: @js($initialFilters) })"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1400px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-[30px] font-bold leading-tight text-slate-900">Tickets</h1>
                    <p class="mt-1 text-sm text-slate-500">Track, filter, and manage tickets.</p>
                </div>

                <a
                    href="{{ route('tickets.create') }}"
                    class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-slate-800">
                    + Create Ticket
                </a>
            </div>

            @include('tickets.partials.index-focus-banner')

            <div class="rounded bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                @include('tickets.partials.index-filters')

                @include('tickets.partials.index-table')

                @include('tickets.partials.pagination')
            </div>
        </div>
    </div>

    @include('tickets.partials.index-styles')

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>
    @endpush
</x-app-layout>
