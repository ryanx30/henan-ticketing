@php
    use App\Models\ResolverMessage;

    $user = auth()->user();
    $role = $user?->role;

    $roleLabel = match ($role) {
        'cs' => 'Customer Service',
        'it' => 'IT',
        'admin' => 'Administrator',
        'supervisor' => 'Supervisor',
        default => ucfirst((string) $role),
    };

    $notificationCount = $user
        ? ResolverMessage::query()
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->count()
        : 0;

    $mobileMenus = collect([
        [
            'label' => 'Dashboard',
            'href' => '/dashboard',
            'show' => true,
            'active' => request()->is('dashboard'),
        ],
        [
            'label' => 'New Ticket',
            'href' => '/tickets/create',
            'show' => in_array($role, ['cs', 'admin'], true),
            'active' => request()->is('tickets/create'),
        ],
        [
            'label' => 'Tickets',
            'href' => '/tickets',
            'show' => in_array($role, ['cs', 'admin', 'supervisor'], true),
            'active' => request()->is('tickets') || (request()->is('tickets/*') && ! request()->is('tickets/create')),
        ],
        [
            'label' => 'Resolver Inbox',
            'href' => '/resolver-inbox',
            'show' => true,
            'active' => request()->is('resolver-inbox') || request()->is('resolver-inbox/*'),
        ],
        [
            'label' => 'Reports',
            'href' => '/reports',
            'show' => true,
            'active' => request()->is('reports') || request()->is('reports/*'),
        ],
        [
            'label' => 'Case Analytics',
            'href' => '/case-analytics',
            'show' => in_array($role, ['it', 'admin', 'supervisor'], true),
            'active' => request()->is('case-analytics') || request()->is('case-analytics/*'),
        ],
        [
            'label' => 'My Queue',
            'href' => '/it/my-queue',
            'show' => in_array($role, ['it', 'admin'], true),
            'active' => request()->is('it/my-queue') || request()->is('it/my-queue/*'),
        ],
        [
            'label' => 'Team Queue',
            'href' => '/it/team-queue',
            'show' => in_array($role, ['it', 'admin', 'supervisor'], true),
            'active' => request()->is('it/team-queue') || request()->is('it/team-queue/*'),
        ],
        [
            'label' => 'History',
            'href' => '/it/history',
            'show' => in_array($role, ['it', 'admin', 'supervisor'], true),
            'active' => request()->is('it/history') || request()->is('it/history/*'),
        ],
        [
            'label' => 'Users',
            'href' => '/admin/users',
            'show' => $role === 'admin',
            'active' => request()->is('admin/users') || request()->is('admin/users/*'),
        ],
        [
            'label' => 'Master Data',
            'href' => '/admin/master-data',
            'show' => in_array($role, ['admin', 'supervisor'], true),
            'active' => request()->is('admin/master-data') || request()->is('admin/master-data/*'),
        ],
        [
            'label' => 'Audit Logs',
            'href' => '/admin/audit-logs',
            'show' => $role === 'admin',
            'active' => request()->is('admin/audit-logs') || request()->is('admin/audit-logs/*'),
        ],
    ])->filter(fn (array $menu) => $menu['show'])->values();
@endphp

<nav x-data="{ open: false, dropdownOpen: false }" class="bg-white border-b border-gray-200">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="w-full px-6">
        <div class="flex justify-between items-center h-16">

            <div class="flex items-center">
                <div class="font-bold text-slate-800">
                    Ticketing System
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center gap-5">
                <button type="button" class="relative text-slate-500 hover:text-slate-700">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17a3 3 0 0 0 6 0" />
                    </svg>

                    @if ($notificationCount > 0)
                        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 text-[11px] leading-[18px] text-center rounded-full bg-red-600 text-white">
                            {{ $notificationCount }}
                        </span>
                    @endif
                </button>

                <div class="relative">
                    <button
                        type="button"
                        @click="dropdownOpen = !dropdownOpen"
                        class="flex items-center gap-2 text-right leading-tight hover:opacity-90 focus:outline-none">
                        <div>
                            <div class="text-sm font-medium text-slate-800">{{ $user?->email ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $roleLabel ?: '-' }}</div>
                        </div>

                        <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div
                        x-show="dropdownOpen"
                        @click.outside="dropdownOpen = false"
                        x-transition
                        class="absolute right-0 mt-2 w-48 rounded-md bg-white shadow-lg border border-slate-200 z-50"
                        style="display: none;">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Profile
                        </a>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}"
                            class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open}"
                            class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1 px-4">
            @foreach ($mobileMenus as $menu)
                <a
                    href="{{ $menu['href'] }}"
                    class="block px-3 py-2 rounded-md text-sm {{ $menu['active'] ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-700 hover:bg-slate-50' }}"
                    @if ($menu['active']) aria-current="page" @endif>
                    {{ $menu['label'] }}
                </a>
            @endforeach
        </div>

        <div class="pt-4 pb-3 border-t border-gray-200 px-4">
            <div class="font-medium text-base text-slate-800">{{ $user?->name ?? '-' }}</div>
            <div class="font-medium text-sm text-slate-500">{{ $user?->email ?? '-' }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $roleLabel ?: '-' }}</div>

            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">
                    Profile
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
