


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
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <div
        id="my-queue-page"
        data-user-id="<?php echo e(auth()->id()); ?>"
        x-data="myQueuePage()"
        x-init="init()"
        class="min-h-screen bg-slate-100 p-6">
        <div class="mx-auto max-w-6xl">

            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold">My Queue</h1>
            </div>

            
            <div class="mb-5 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <template x-for="tab in tabs()" :key="tab.key">
                        <button
                            type="button"
                            @click="setActiveTab(tab.key)"
                            class="flex items-center justify-between gap-3 rounded-lg border px-4 py-3 text-left text-sm font-semibold transition"
                            :class="tabButtonClass(tab.key)">
                            <span x-text="tab.label"></span>
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
                                x-text="tab.count"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900" x-text="activeTabData()?.label || 'Queue'"></h2>
                    <p class="text-sm text-slate-500" x-text="activeTabData()?.description || ''"></p>
                </div>

                <template x-if="activeTab === 'resolved'">
                    <a href="<?php echo e(route('it.history', ['status' => 'resolved'])); ?>"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                        View all history →
                    </a>
                </template>
            </div>

            
            <div class="overflow-hidden rounded bg-white shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-900 text-left text-white">
                                <th class="px-3 py-2">Ticket</th>
                                <th class="px-3">Subject</th>
                                <th class="px-3">Priority</th>
                                <th class="px-3">SLA Status</th>
                                <th class="px-3">Holder</th>
                                <th class="px-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-if="loading && activeTickets().length === 0">
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-500">Loading tickets...</td>
                                </tr>
                            </template>

                            <template x-if="!loading && activeTickets().length === 0">
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-500" x-text="emptyMessage()"></td>
                                </tr>
                            </template>

                            <template x-for="t in activeTickets()" :key="t.id">
                                <tr class="border-b">
                                    <td class="px-3 py-3 font-mono" x-text="ticketLabel(t)"></td>

                                    <td class="px-3">
                                        <a :href="ticketUrl(t.id)" class="block">
                                            <div class="font-semibold text-slate-900 hover:text-blue-600 hover:underline" x-text="t.title"></div>
                                            <div class="max-w-[520px] truncate text-xs text-gray-500" x-text="t.description"></div>
                                        </a>
                                    </td>

                                    <td class="px-3">
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="priorityBadgeClass(t.priority)"
                                            x-text="ucfirst(t.priority)"></span>
                                    </td>

                                    <td class="w-[110px] whitespace-nowrap px-3">
                                        <span
                                            class="mx-auto inline-block w-[110px] font-mono tabular-nums text-slate-800"
                                            x-text="slaCountdown(t.sla_deadline_at)"></span>
                                    </td>

                                    <td class="px-3" x-text="t.holder?.name ?? '-'"></td>

                                    <td class="px-3 text-right">
                                        <template x-if="canClaimTicket(t)">
                                            <button
                                                type="button"
                                                @click="claimTicket(t.id)"
                                                class="rounded bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
                                                Claim
                                            </button>
                                        </template>

                                        <template x-if="!canClaimTicket(t) && canUpdateStatus(t)">
                                            <select
                                                class="w-[150px] rounded border px-2 py-2 text-xs"
                                                :value="statusValue(t)"
                                                @change="handleStatusChange(t, $event)">
                                                <option :value="statusValue(t)" x-text="`Current: ${statusLabel(t.status)}`"></option>
                                                <template x-for="option in statusOptionsFor(t)" :key="option.value">
                                                    <option :value="option.value" x-text="option.label"></option>
                                                </template>
                                            </select>
                                        </template>

                                        <template x-if="!canClaimTicket(t) && !canUpdateStatus(t)">
                                            <span
                                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                                :class="statusBadgeClass(t.status)"
                                                x-text="statusLabel(t.status)"></span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
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
<?php endif; ?>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/it/my-queue.blade.php ENDPATH**/ ?>