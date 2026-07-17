


<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <style>
        .dashboard-ticket-row {
            position: relative;
            cursor: pointer;
            transition:
                background-color 150ms ease,
                filter 150ms ease,
                transform 150ms ease;
        }

        .dashboard-ticket-row td {
            transition: background-color 150ms ease;
        }

        .dashboard-ticket-row:hover {
            z-index: 10;
            filter: drop-shadow(0 8px 14px rgba(15, 23, 42, 0.14));
            transform: translateY(-1px);
        }

        .dashboard-ticket-row:hover td {
            background-color: #ffffff;
        }

        .dashboard-ticket-row:focus {
            outline: 2px solid rgba(37, 99, 235, 0.35);
            outline-offset: -2px;
        }
    </style>

    <div
        id="dashboard-cs-page"
        data-current-user-id="<?php echo e(auth()->id()); ?>"
        x-data="dashboardCsPage()"
        x-init="init()"
        class="min-h-screen bg-slate-100 p-6">

        <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

        <div class="grid grid-cols-12 gap-6">

            
            <div class="col-span-12 lg:col-span-3">
                <?php echo $__env->make('dashboard.partials.kpi-cards', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                
                <div class="mt-3 rounded-md border border-slate-200 bg-white p-4 shadow-lg">
                    <div class="mb-3 text-sm font-semibold text-slate-800">Status Legend</div>

                    <div class="grid grid-cols-2 gap-2 text-sm text-slate-700">
                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-new"></span>
                            <span>New</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-ongoing"></span>
                            <span>Ongoing</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-waiting"></span>
                            <span>Waiting Info</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-resolved"></span>
                            <span>Resolved</span>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            <span class="legend-status-dot legend-status-closed"></span>
                            <span>Closed</span>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-span-12 space-y-6 lg:col-span-9">
                <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="space-y-6">

                        
                        <div class="rounded bg-white p-4 shadow-lg">
                            <div class="mb-3 text-xl font-semibold">Today's Focus</div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <a
                                    :href="focusLink('sla_risk')"
                                    class="group relative rounded-xl bg-gradient-to-r from-red-600 to-red-800 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-110"
                                    title="View SLA Risk Tickets">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.sla"></span>
                                    <div class="text-lg text-center font-bold leading-tight">SLA &lt; 30m</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Critical - Act Now)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>

                                <a
                                    :href="focusLink('due_today')"
                                    class="group relative rounded-xl bg-gradient-to-r from-orange-400 to-orange-600 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-105"
                                    title="View Tickets Due Today">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.due_today"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Due Today</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Clear Before EOD)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>

                                <a
                                    :href="focusLink('pending_user')"
                                    class="group relative rounded-xl bg-gradient-to-r from-yellow-400 to-yellow-500 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-105"
                                    title="View Pending User Tickets">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.pending_user"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Pending User</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Follow up)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>

                                <a
                                    :href="focusLink('reopened')"
                                    class="group relative rounded-xl bg-gradient-to-r from-sky-400 to-blue-700 p-4 text-white shadow-lg transition-all duration-200 hover:-translate-y-[2px] hover:shadow-xl hover:brightness-105"
                                    title="View Reopened Tickets">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-800 shadow"
                                        x-text="focus.reopened"></span>
                                    <div class="text-lg text-center font-bold leading-tight">Reopened</div>
                                    <div class="text-lg text-center font-bold leading-tight">(Review & Resolve)</div>

                                    <div class="mt-3 text-center text-xs font-medium text-white/90 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        Click to view tickets
                                    </div>
                                </a>
                            </div>

                            <?php echo $__env->make('dashboard.partials.quick-actions', ['class' => 'mt-4'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <?php echo $__env->make('dashboard.partials.cs-my-tickets', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                        <?php echo $__env->make('dashboard.partials.cs-active-tickets', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                        <?php echo $__env->make('dashboard.partials.resolver-inbox-preview', [
                            'title' => 'Resolver Update Inbox',
                            'subtitle' => 'Latest resolver conversations that need attention.',
                            'showTimeFilter' => true,
                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                    </div>
                </div>
            </div>

        </div>
    </div>


 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/dashboard-cs.blade.php ENDPATH**/ ?>