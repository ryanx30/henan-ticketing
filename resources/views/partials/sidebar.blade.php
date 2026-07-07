{{-- ========= SIDEBAR NAVIGATION ========= --}}
{{-- Desktop sidebar that consumes the centralized navigation menu for consistent role-based access. --}}

@php
    use App\Support\NavigationMenu;

    $user = auth()->user();
    $role = $user?->role;
    $roleLabel = NavigationMenu::roleLabel($role);
    $visibleMenuGroups = NavigationMenu::groupsForUser($user);

    $menuClasses = function (bool $active): string {
        $stateClasses = $active
            ? 'bg-slate-800 text-white shadow-sm ring-1 ring-slate-700'
            : 'text-white/90 hover:bg-slate-800 hover:text-white';

        return "relative flex items-center rounded transition duration-200 {$stateClasses}";
    };
@endphp

<style>
    [x-cloak] { display: none !important; }

    .sidebar-scrollbar-hidden {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .sidebar-scrollbar-hidden::-webkit-scrollbar {
        display: none;
    }
</style>

<aside
    x-data="staticSidebarNavigation()"
    x-init="init()"
    :class="collapsed ? 'w-[80px]' : 'w-[230px]'"
    class="h-screen shrink-0 bg-[#051823] text-white flex flex-col transition-all duration-300 ease-in-out overflow-hidden">
    <div class="pl-4 pr-4 pt-3 pb-6 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="toggleSidebar()"
                class="shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-md hover:bg-slate-800 transition"
                :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                <svg
                    x-cloak
                    x-show="!collapsed"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>

                <svg
                    x-cloak
                    x-show="collapsed"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div
                class="flex h-12 items-center justify-center overflow-hidden transition-[width] duration-300"
                :class="collapsed ? 'w-[34px]' : 'w-[170px]'">
                <img
                    src="{{ asset('images/logo-henan.png') }}"
                    alt="Henan Logo"
                    width="170"
                    height="40"
                    class="block h-14 max-h-14 w-auto max-w-[170px] shrink-0 object-contain transition-none" />
            </div>
        </div>
    </div>

    <div class="sidebar-scrollbar-hidden min-h-0 flex-1 overflow-y-auto">
        @foreach ($visibleMenuGroups as $groupKey => $group)
            <div class="{{ $loop->last ? 'p-3' : 'border-b border-slate-800 p-3' }}">
                <button
                    type="button"
                    x-cloak
                    x-show="!collapsed"
                    @click="toggleSection('{{ $groupKey }}')"
                    class="mb-2 flex w-full items-center justify-between rounded px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400 hover:bg-slate-800 transition">
                    <span>{{ $group['label'] }}</span>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 transition-transform duration-200"
                        :class="openSections.{{ $groupKey }} ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <nav
                    x-cloak
                    x-show="collapsed || openSections.{{ $groupKey }}"
                    x-transition
                    class="space-y-1">
                    @foreach ($group['items'] as $item)
                        <a
                            href="{{ $item['href'] }}"
                            class="{{ $menuClasses($item['active']) }}"
                            :class="collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-3 py-2'"
                            title="{{ $item['label'] }}"
                            @if ($item['active']) aria-current="page" @endif>
                            @if ($item['active'])
                                <span
                                    x-cloak
                                    x-show="!collapsed"
                                    class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-white"></span>
                            @endif

                            <img
                                src="{{ asset('images/icons/' . $item['icon'] . '.png') }}"
                                alt=""
                                class="h-5 w-5 shrink-0 object-contain brightness-0 invert {{ $item['active'] ? 'opacity-100' : 'opacity-90' }}" />

                            <span
                                x-cloak
                                x-show="!collapsed"
                                class="whitespace-nowrap">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>
            </div>
        @endforeach
    </div>

    <div class="shrink-0 border-t border-slate-800 p-4 text-xs text-slate-400">
        <template x-if="!collapsed">
            <div>
                Logged in as:
                <span>{{ $user?->name ?? '-' }}</span>
                (<span>{{ $roleLabel ?: '-' }}</span>)
            </div>
        </template>

        <template x-if="collapsed">
            <div class="text-center" title="{{ $user?->name ? $user->name . ' (' . ($roleLabel ?: '-') . ')' : '' }}">
                <span>{{ $roleLabel ?: '-' }}</span>
            </div>
        </template>
    </div>

</aside>
