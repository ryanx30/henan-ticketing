@php
$isIT = auth()->user()->role === 'it';
@endphp

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div
        id="resolver-inbox-page"
        data-is-it="{{ auth()->user()->role === 'it' ? '1' : '0' }}"
        data-user-id="{{ auth()->id() }}"
        x-data="resolverInboxPage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] p-6">
        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        <div class="mx-auto max-w-6xl space-y-5">
            @include('resolver-inbox.partials.header-filters')
            @include('resolver-inbox.partials.messages-table')
        </div>

        @include('resolver-inbox.partials.compose-modal')
    </div>
</x-app-layout>
