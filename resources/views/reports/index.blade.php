{{-- ========= REPORTS SHELL ========= --}}
{{-- Composes report filters, KPI cards, trend chart, SLA table, and export controls. --}}

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />

    <div
        x-data="reportsPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1600px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-4">
                <h1 class="text-[34px] font-bold text-[#051823]">REPORTS</h1>
            </div>

            @include('reports.partials.filter-bar')
            @include('reports.partials.kpi-cards')
            @include('reports.partials.trend-card')
            @include('reports.partials.sla-table')
        </div>
    </div>

    @include('reports.partials.assets')
</x-app-layout>
