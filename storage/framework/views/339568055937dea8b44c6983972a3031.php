


<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title>Henan Ticketing System</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/hpsekuritas.ico?v=1">
    <link rel="shortcut icon" href="/hpsekuritas.ico?v=1">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>

<body class="font-sans antialiased overflow-hidden">
    <?php
        $authenticatedUser = auth()->user();
        $sidebarNavigationService = app(\App\Services\Navigation\SidebarNavigationService::class);
        $sidebarBadgeCounts = $sidebarNavigationService->badgeCountsFor($authenticatedUser);
        $visibleMenuGroups = $sidebarNavigationService->groupsForUser($authenticatedUser, $sidebarBadgeCounts);
        $mobileMenus = $sidebarNavigationService->flatForUser($authenticatedUser, $sidebarBadgeCounts);
    ?>

    <div class="h-screen min-h-screen flex overflow-hidden">

        
        <?php echo $__env->make('partials.sidebar', [
            'user' => $authenticatedUser,
            'visibleMenuGroups' => $visibleMenuGroups,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div class="min-w-0 flex-1 flex flex-col h-screen overflow-hidden">
            <?php echo $__env->make('layouts.navigation', [
                'user' => $authenticatedUser,
                'mobileMenus' => $mobileMenus,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            
            <main class="min-w-0 flex-1 overflow-y-auto bg-gray-100">
                <?php echo e($slot); ?>

            </main>
        </div>
    </div>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/layouts/app.blade.php ENDPATH**/ ?>