


<?php
    use App\Services\Notifications\NotificationPayloadService;
    use App\Support\NavigationMenu;

    $user = auth()->user();
    $role = $user?->role;
    $roleLabel = NavigationMenu::roleLabel($role);
    $notificationPayload = $user
        ? app(NotificationPayloadService::class)->payloadFor($user)
        : ['count' => 0, 'latest' => []];
    $notificationCount = (int) ($notificationPayload['count'] ?? 0);
    $latestNotifications = $notificationPayload['latest'] ?? [];
    $mobileMenus = NavigationMenu::flatForUser($user);
?>

<nav x-data="{ open: false, dropdownOpen: false, notificationOpen: false }" class="shrink-0 bg-white border-b border-gray-200 z-40">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <div class="w-full px-6">
        <div class="flex justify-between items-center h-16">

            <div class="flex items-center">
                <div class="font-bold text-slate-800">
                    Ticketing System
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center gap-5">
                <div class="relative">
                    <button
                        type="button"
                        @click="notificationOpen = !notificationOpen; dropdownOpen = false"
                        class="relative text-slate-500 hover:text-slate-700 focus:outline-none"
                        aria-label="Open notifications">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 17a3 3 0 0 0 6 0" />
                        </svg>

                        <span
                            data-notification-count
                            data-base-count="<?php echo e($notificationCount); ?>"
                            class="<?php echo e($notificationCount > 0 ? '' : 'hidden'); ?> absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 text-[11px] leading-[18px] text-center rounded-full bg-red-600 text-white">
                            <?php echo e($notificationCount > 99 ? '99+' : $notificationCount); ?>

                        </span>
                    </button>

                    <div
                        x-show="notificationOpen"
                        @click.outside="notificationOpen = false"
                        x-transition
                        class="absolute right-0 mt-3 w-96 max-w-[calc(100vw-2rem)] rounded-2xl bg-white shadow-xl border border-slate-200 z-50 overflow-hidden"
                        style="display: none;">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Notifications</div>
                                <div class="text-xs text-slate-500">Latest actionable updates</div>
                            </div>
                            <span
                                data-notification-summary
                                class="text-[11px] font-semibold px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                                <?php echo e($notificationCount); ?> active
                            </span>
                        </div>

                        <div data-notification-list class="max-h-[420px] overflow-y-auto">
                            <?php $__empty_1 = true; $__currentLoopData = $latestNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $notificationAccentClass = match ($notification['type'] ?? null) {
                                        'sla_breached' => 'bg-red-600',
                                        'sla_warning' => 'bg-amber-500',
                                        'waiting_info' => 'bg-violet-600',
                                        'ticket_reopened' => 'bg-teal-700',
                                        'team_queue' => 'bg-green-600',
                                        'ticket_status' => 'bg-sky-600',
                                        default => 'bg-blue-600',
                                    };
                                ?>
                                <a
                                    href="<?php echo e($notification['url']); ?>"
                                    class="group block px-4 py-3 border-b border-slate-100 hover:bg-blue-50/70 transition">
                                    <div class="flex gap-3">
                                        <span class="mt-1 h-2.5 w-2.5 rounded-full shrink-0 <?php echo e($notificationAccentClass); ?>"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                        <?php echo e($notification['label']); ?>

                                                    </div>
                                                    <div class="text-sm font-semibold text-slate-800 truncate group-hover:text-blue-700">
                                                        <?php echo e($notification['title']); ?>

                                                    </div>
                                                </div>
                                                <div class="shrink-0 text-[11px] text-slate-400 whitespace-nowrap">
                                                    <?php echo e($notification['time']); ?>

                                                </div>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-600 line-clamp-2">
                                                <?php echo e($notification['description']); ?>

                                            </p>
                                            <div class="mt-1 text-[11px] font-medium text-slate-400 truncate">
                                                <?php echo e($notification['meta']); ?>

                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div data-notification-empty class="px-4 py-8 text-center">
                                    <div class="text-sm font-medium text-slate-700">No active notifications</div>
                                    <div class="mt-1 text-xs text-slate-500">You're all caught up for now.</div>
                                </div>
                            <?php endif; ?>

                            <div data-export-notifications></div>
                        </div>

                        <div class="grid grid-cols-2 divide-x divide-slate-100 border-t border-slate-100 bg-slate-50">
                            <a href="<?php echo e(route('resolver-inbox.index', ['unread' => 'unread'])); ?>" class="px-4 py-3 text-center text-xs font-semibold text-slate-600 hover:bg-white hover:text-blue-700">
                                Resolver Inbox
                            </a>
                            <a href="<?php echo e($user?->isIT() ? route('it.my-queue') : route('tickets.index')); ?>" class="px-4 py-3 text-center text-xs font-semibold text-slate-600 hover:bg-white hover:text-blue-700">
                                <?php echo e($user?->isIT() ? 'My Queue' : 'View Tickets'); ?>

                            </a>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button
                        type="button"
                        @click="dropdownOpen = !dropdownOpen"
                        class="flex items-center gap-2 text-right leading-tight hover:opacity-90 focus:outline-none">
                        <div>
                            <div class="text-sm font-medium text-slate-800"><?php echo e($user?->email ?? '-'); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e($roleLabel ?: '-'); ?></div>
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
                        <form action="<?php echo e(route('logout')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
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
            <?php $__currentLoopData = $mobileMenus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a
                    href="<?php echo e($menu['href']); ?>"
                    class="block px-3 py-2 rounded-md text-sm <?php echo e($menu['active'] ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-700 hover:bg-slate-50'); ?>"
                    <?php if($menu['active']): ?> aria-current="page" <?php endif; ?>>
                    <?php echo e($menu['label']); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="pt-4 pb-3 border-t border-gray-200 px-4">
            <div class="font-medium text-base text-slate-800"><?php echo e($user?->name ?? '-'); ?></div>
            <div class="font-medium text-sm text-slate-500"><?php echo e($user?->email ?? '-'); ?></div>
            <div class="text-xs text-slate-500 mt-1"><?php echo e($roleLabel ?: '-'); ?></div>

            <div class="mt-3 space-y-1">
                <a href="<?php echo e(route('profile.edit')); ?>" class="block px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">
                    Profile
                </a>

                <form action="<?php echo e(route('logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="block w-full text-left px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/layouts/navigation.blade.php ENDPATH**/ ?>