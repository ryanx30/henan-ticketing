{{-- ========= REPORTS SHELL ========= --}}
{{-- Composes report filters, KPI cards, trend chart, SLA table, and export controls. --}}

<x-app-layout>
    @php
        $defaultReportType = match (auth()->user()->role) {
            'admin' => 'it_performance',
            'head_cs' => 'cs_performance',
            'supervisor' => 'all',
            default => 'my',
        };
    @endphp

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />

    <style>
        .report-ticket-row {
            position: relative;
            cursor: pointer;
            transition:
                background-color 150ms ease,
                box-shadow 150ms ease,
                transform 150ms ease;
        }

        .report-ticket-row td {
            transition: background-color 150ms ease;
        }

        .report-ticket-row:hover {
            z-index: 10;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
            transform: translateY(-1px);
        }

        .report-ticket-row:hover td {
            background-color: #ffffff;
        }

        .report-ticket-row:focus {
            outline: 2px solid rgba(37, 99, 235, 0.35);
            outline-offset: -2px;
        }
    </style>

    <div
        x-data="reportsPage({ defaultReportType: @js($defaultReportType) })"
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
