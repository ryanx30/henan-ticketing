<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />

    <div
        id="it-history-page"
        x-data="historyPage()"
        x-init="init()"
        class="rounded bg-white mx-8 my-7 p-8 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        @include('it.history-partials.filters')
        @include('it.history-partials.table')
        @include('it.history-partials.pagination')
    </div>

    @include('it.history-partials.assets')

    <style>
        .history-click-row {
            cursor: pointer;
            position: relative;
            transition: background-color 150ms ease, filter 150ms ease, transform 150ms ease;
        }

        .history-click-row:hover {
            z-index: 2;
            filter: drop-shadow(0 8px 14px rgba(15, 23, 42, 0.14));
            transform: translateY(-1px);
        }

        .history-click-row:hover td { background-color: #ffffff; }

        .history-click-row:focus {
            outline: 2px solid rgba(47, 136, 216, 0.35);
            outline-offset: -2px;
        }
    </style>
</x-app-layout>
