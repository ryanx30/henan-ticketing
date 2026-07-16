


<?php
    use App\Support\NavigationMenu;

    $user ??= auth()->user();
    $visibleMenuGroups ??= NavigationMenu::groupsForUser($user);

    $roleLabel = NavigationMenu::roleLabel($user?->role);
    $initialSections = collect($visibleMenuGroups)
        ->mapWithKeys(fn (array $group, string $key): array => [$key => $key === 'operations'])
        ->all();
    $activeGroup = collect($visibleMenuGroups)
        ->first(function (array $group): bool {
            return collect($group['items'])->contains(
                fn (array $item): bool => (bool) ($item['active'] ?? false)
            );
        });
    $activeGroupKey = $activeGroup['key'] ?? null;

    $nameParts = preg_split('/\s+/', trim((string) ($user?->name ?? '')));
    $userInitials = collect($nameParts ?: [])
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $userInitials = $userInitials !== '' ? $userInitials : 'U';

    $menuClasses = function (array $item): string {
        if (($item['key'] ?? null) === 'new-ticket') {
            return ($item['active'] ?? false)
                ? 'bg-blue-600 text-white shadow-sm ring-1 ring-blue-400'
                : 'bg-blue-600 text-white hover:bg-blue-500';
        }

        return ($item['active'] ?? false)
            ? 'bg-slate-800 text-white shadow-sm ring-1 ring-slate-700'
            : 'text-white/90 hover:bg-slate-800 hover:text-white';
    };

    $expandedBadgeLabel = fn (int $count): string => $count > 99 ? '99+' : (string) $count;
    $compactBadgeLabel = fn (int $count): string => $count > 9 ? '9+' : (string) $count;
?>

<aside
    x-data="staticSidebarNavigation(<?php echo \Illuminate\Support\Js::from($initialSections)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($activeGroupKey)->toHtml() ?>)"
    x-init="init()"
    data-sidebar-root
    aria-label="Primary navigation"
    :class="collapsed ? 'w-[80px]' : 'w-[230px]'"
    class="hidden lg:flex h-screen w-[230px] shrink-0 flex-col overflow-hidden bg-[#051823] text-white transition-all duration-300 ease-in-out">
    <div class="shrink-0 border-b border-slate-800 px-3 py-3">
        <div
            class="flex items-center"
            :class="collapsed ? 'flex-col gap-2' : 'justify-between gap-3'">
            <div
                x-cloak
                x-show="!collapsed"
                class="flex min-w-0 flex-1 items-center overflow-hidden">
                <img
                    src="<?php echo e(asset('images/logo-henan.png')); ?>"
                    alt="Henan Putihrai Sekuritas"
                    width="150"
                    height="40"
                    class="block h-12 w-auto max-w-[150px] object-contain" />
            </div>

            <div
                x-cloak
                x-show="collapsed"
                class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-xs font-bold tracking-wide text-slate-900"
                aria-hidden="true">
                HP
            </div>

            <button
                type="button"
                @click="toggleSidebar()"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-300 transition hover:bg-slate-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-expanded="(!collapsed).toString()"
                aria-controls="primary-sidebar-navigation">
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
        </div>
    </div>

    <div
        id="primary-sidebar-navigation"
        class="sidebar-scrollbar min-h-0 flex-1 overflow-y-auto"
        @scroll="hideTooltip()">
        <?php $__currentLoopData = $visibleMenuGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <section class="<?php echo e($loop->last ? 'p-3' : 'border-b border-slate-800 p-3'); ?>">
                <button
                    type="button"
                    x-cloak
                    x-show="!collapsed"
                    @click="toggleSection('<?php echo e($groupKey); ?>')"
                    :aria-expanded="Boolean(openSections['<?php echo e($groupKey); ?>']).toString()"
                    aria-controls="sidebar-section-<?php echo e($groupKey); ?>"
                    class="mb-2 flex w-full items-center justify-between rounded px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400 transition hover:bg-slate-800 hover:text-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                    <span><?php echo e($group['label']); ?></span>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 transition-transform duration-200"
                        :class="openSections['<?php echo e($groupKey); ?>'] ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <nav
                    id="sidebar-section-<?php echo e($groupKey); ?>"
                    x-cloak
                    x-show="collapsed || openSections['<?php echo e($groupKey); ?>']"
                    x-transition
                    aria-label="<?php echo e($group['label']); ?>"
                    class="space-y-1">
                    <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $badgeCount = max(0, (int) ($item['badge_count'] ?? 0));
                            $badgeKey = $item['badge_key'] ?? null;
                        ?>

                        <a
                            href="<?php echo e($item['href']); ?>"
                            data-sidebar-link
                            data-sidebar-label="<?php echo e($item['label']); ?>"
                            class="relative flex items-center rounded transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 <?php echo e($menuClasses($item)); ?>"
                            :class="collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-3 py-2'"
                            aria-label="<?php echo e($item['label']); ?>"
                            x-on:mouseenter="showTooltip($event, <?php echo \Illuminate\Support\Js::from($item['label'])->toHtml() ?>)"
                            @mouseleave="hideTooltip()"
                            x-on:focus="showTooltip($event, <?php echo \Illuminate\Support\Js::from($item['label'])->toHtml() ?>)"
                            @blur="hideTooltip()"
                            <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
                            <?php if($item['active']): ?>
                                <span
                                    x-cloak
                                    x-show="!collapsed"
                                    class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-white"
                                    aria-hidden="true"></span>
                            <?php endif; ?>

                            <span class="relative shrink-0">
                                <img
                                    src="<?php echo e(asset('images/icons/' . $item['icon'] . '.png')); ?>"
                                    alt=""
                                    class="h-5 w-5 object-contain brightness-0 invert <?php echo e($item['active'] ? 'opacity-100' : 'opacity-90'); ?>" />

                                <?php if($badgeKey): ?>
                                    <span
                                        x-cloak
                                        x-show="collapsed"
                                        data-sidebar-badge-key="<?php echo e($badgeKey); ?>"
                                        data-sidebar-badge-compact="1"
                                        aria-hidden="true"
                                        class="<?php echo e($badgeCount > 0 ? '' : 'hidden'); ?> absolute -right-3 -top-3 flex h-[17px] min-w-[17px] items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-semibold leading-none text-white ring-2 ring-[#051823]">
                                        <?php echo e($compactBadgeLabel($badgeCount)); ?>

                                    </span>
                                <?php endif; ?>
                            </span>

                            <span
                                x-cloak
                                x-show="!collapsed"
                                class="min-w-0 flex-1 truncate whitespace-nowrap">
                                <?php echo e($item['label']); ?>

                            </span>

                            <?php if($badgeKey): ?>
                                <span
                                    x-cloak
                                    x-show="!collapsed"
                                    data-sidebar-badge-key="<?php echo e($badgeKey); ?>"
                                    data-sidebar-badge-compact="0"
                                    aria-hidden="true"
                                    class="<?php echo e($badgeCount > 0 ? '' : 'hidden'); ?> inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold leading-none text-white">
                                    <?php echo e($expandedBadgeLabel($badgeCount)); ?>

                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>
            </section>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="shrink-0 border-t border-slate-800 p-3">
        <div
            class="flex items-center"
            :class="collapsed ? 'justify-center' : 'gap-3'">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
                :tabindex="collapsed ? 0 : -1"
                x-on:mouseenter="showTooltip($event, <?php echo \Illuminate\Support\Js::from(($user?->name ?? '-') . ' · ' . ($roleLabel ?: '-'))->toHtml() ?>)"
                @mouseleave="hideTooltip()"
                x-on:focus="showTooltip($event, <?php echo \Illuminate\Support\Js::from(($user?->name ?? '-') . ' · ' . ($roleLabel ?: '-'))->toHtml() ?>)"
                @blur="hideTooltip()">
                <?php echo e($userInitials); ?>

            </div>

            <div x-cloak x-show="!collapsed" class="min-w-0">
                <div class="truncate text-sm font-medium text-white">
                    <?php echo e($user?->name ?? '-'); ?>

                </div>
                <div class="truncate text-xs text-slate-400">
                    <?php echo e($roleLabel ?: '-'); ?>

                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div
            x-cloak
            x-show="tooltip.open && collapsed"
            x-transition.opacity.duration.100ms
            role="tooltip"
            class="pointer-events-none fixed z-[100] whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg"
            :style="`left: ${tooltip.left}px; top: ${tooltip.top}px; transform: translateY(-50%);`"
            x-text="tooltip.label">
        </div>
    </template>
</aside>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>