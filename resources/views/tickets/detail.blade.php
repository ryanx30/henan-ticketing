{{-- ========= TICKET DETAIL SHELL ========= --}}
{{-- Composes reusable ticket detail partials and delegates dynamic actions to the detail page script. --}}

<x-app-layout>

    <div
        x-data="ticketDetailPage({ ticketId: @json($ticketId), currentUserId: @json(auth()->id()) })"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7">
        <div class="mx-auto w-full max-w-[1400px]">
            <div id="page-alert" class="mb-6 hidden rounded-lg border px-4 py-3 text-sm shadow-sm"></div>

            @include('tickets.detail-partials.header')

            <template x-if="errorMessage">
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                    <span x-text="errorMessage"></span>
                </div>
            </template>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-6">
                    @include('tickets.detail-partials.information-card')
                    @include('tickets.detail-partials.attachments')
                    @include('tickets.detail-partials.status-history')
                    @include('tickets.detail-partials.similar-tickets')
                    @include('tickets.detail-partials.recent-updates')
                </div>

                @include('tickets.detail-partials.sidebar')
            </div>
        </div>

        @include('tickets.detail-partials.compose-modal')
    </div>
</x-app-layout>
