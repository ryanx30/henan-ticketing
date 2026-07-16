{{-- Role-aware dashboard shortcuts. Keep route visibility aligned with existing role permissions. --}}
@php
    $user = auth()->user();

    $quickActions = match (true) {
        $user?->isCS() => [
            ['label' => '+ Create Ticket', 'href' => route('tickets.create')],
            ['label' => 'View My Tickets', 'href' => route('tickets.index', ['mine' => 1])],
        ],
        $user?->isHeadCS() => [
            ['label' => 'Review Waiting Info', 'href' => route('tickets.index', ['status' => 'waiting_info'])],
            ['label' => 'View Team Tickets', 'href' => route('tickets.index')],
        ],
        $user?->isAdmin() => [
            ['label' => '+ Create Ticket', 'href' => route('tickets.create')],
            ['label' => 'Manage Users', 'href' => route('admin.users.index')],
            ['label' => 'View Audit Logs', 'href' => route('admin.audit-logs.index')],
        ],
        $user?->isIT() => [
            ['label' => 'Open My Queue', 'href' => route('it.my-queue')],
            ['label' => 'Open Team Queue', 'href' => route('it.team-queue')],
        ],
        default => [],
    };
@endphp

@if ($quickActions !== [])
    <div class="{{ $class ?? '' }}">
        <div class="text-md font-semibold">Quick Actions:</div>

        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($quickActions as $action)
                <a
                    href="{{ $action['href'] }}"
                    class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm transition duration-200 hover:border-slate-900 hover:bg-slate-900 hover:text-white hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif
