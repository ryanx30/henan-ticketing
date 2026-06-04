{{-- ========= MASTER DATA SHELL ========= --}}
{{-- Composes reusable master data partials and delegates CRUD behavior to the master data script. --}}

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        x-data="masterDataPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1600px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            @include('admin.master-data.partials.page-controls')
            @include('admin.master-data.partials.table-card')
        </div>

        @include('admin.master-data.partials.form-modal')
    </div>
</x-app-layout>
