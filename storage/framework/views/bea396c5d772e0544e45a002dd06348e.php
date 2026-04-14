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
        x-data="myQueuePage()"
        x-init="init()"
        class="p-6 bg-slate-100 min-h-screen">
        <div class="max-w-6xl mx-auto">

            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold">My Queue</h1>
            </div>

            
            <div class="mb-10">
                <div class="font-semibold text-lg mb-3">New Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="loading && newTickets.length === 0">
                                    <tr><td colspan="5" class="py-10 text-center text-gray-500">Loading tickets...</td></tr>
                                </template>

                                <template x-if="!loading && newTickets.length === 0">
                                    <tr><td colspan="5" class="py-10 text-center text-gray-500">No tickets.</td></tr>
                                </template>

                                <template x-for="t in newTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="'#T-' + (t.ticket_code ?? t.id)"></td>

                                        <td class="px-3">
                                            <div class="font-semibold" x-text="t.title"></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                  :class="priorityBadgeClass(t.priority)"
                                                  x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 w-[110px] whitespace-nowrap">
                                            <span class="font-mono tabular-nums inline-block w-[110px] mx-auto text-slate-800"
                                                  x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>

                                        <td class="px-3">
                                            <select class="border rounded px-2 py-2 text-xs w-[140px]"
                                                    :value="t.status"
                                                    @change="updateStatus(t.id, $event.target.value)">
                                                <option value="new">New</option>
                                                <option value="in_progress">On Going</option>
                                                <option value="waiting_info">Waiting Info</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="closed">Closed</option>
                                            </select>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <div class="mb-10">
                <div class="font-semibold text-lg mb-3">On Going Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="!loading && ongoingTickets.length === 0">
                                    <tr><td colspan="5" class="py-10 text-center text-gray-500">No tickets.</td></tr>
                                </template>

                                <template x-for="t in ongoingTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="'#T-' + (t.ticket_code ?? t.id)"></td>

                                        <td class="px-3">
                                            <div class="font-semibold" x-text="t.title"></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                  :class="priorityBadgeClass(t.priority)"
                                                  x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 w-[110px] whitespace-nowrap">
                                            <span class="font-mono tabular-nums inline-block w-[110px] mx-auto text-slate-800"
                                                  x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>

                                        <td class="px-3">
                                            <select class="border rounded px-2 py-2 text-xs w-[140px]"
                                                    :value="t.status"
                                                    @change="updateStatus(t.id, $event.target.value)">
                                                <option value="new">New</option>
                                                <option value="in_progress">On Going</option>
                                                <option value="waiting_info">Waiting Info</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="closed">Closed</option>
                                            </select>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <div class="mb-10">
                <div class="font-semibold text-lg mb-3">Waiting Info Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="!loading && waitingTickets.length === 0">
                                    <tr><td colspan="5" class="py-10 text-center text-gray-500">No tickets.</td></tr>
                                </template>

                                <template x-for="t in waitingTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="'#T-' + (t.ticket_code ?? t.id)"></td>

                                        <td class="px-3">
                                            <div class="font-semibold" x-text="t.title"></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                  :class="priorityBadgeClass(t.priority)"
                                                  x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3 w-[110px] whitespace-nowrap">
                                            <span class="font-mono tabular-nums inline-block w-[110px] mx-auto text-slate-800"
                                                  x-text="slaCountdown(t.sla_deadline_at)"></span>
                                        </td>

                                        <td class="px-3">
                                            <select class="border rounded px-2 py-2 text-xs w-[140px]"
                                                    :value="t.status"
                                                    @change="updateStatus(t.id, $event.target.value)">
                                                <option value="new">New</option>
                                                <option value="in_progress">On Going</option>
                                                <option value="waiting_info">Waiting Info</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="closed">Closed</option>
                                            </select>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <div class="mb-2">
                <div class="font-semibold text-lg mb-3">Resolved Tickets</div>

                <div class="bg-white rounded shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-white bg-slate-900">
                                    <th class="py-2 px-3">Ticket</th>
                                    <th class="px-3">Subject</th>
                                    <th class="px-3">Priority</th>
                                    <th class="px-3">SLA Status</th>
                                    <th class="px-3">Status Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <template x-if="!loading && resolvedTickets.length === 0">
                                    <tr><td colspan="5" class="py-10 text-center text-gray-500">No tickets.</td></tr>
                                </template>

                                <template x-for="t in resolvedTickets" :key="t.id">
                                    <tr class="border-b">
                                        <td class="py-3 px-3 font-mono" x-text="'#T-' + (t.ticket_code ?? t.id)"></td>

                                        <td class="px-3">
                                            <div class="font-semibold" x-text="t.title"></div>
                                            <div class="text-xs text-gray-500 truncate max-w-[520px]" x-text="t.description"></div>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                  :class="priorityBadgeClass(t.priority)"
                                                  x-text="ucfirst(t.priority)"></span>
                                        </td>

                                        <td class="px-3">
                                            <span class="text-green-600 text-xs">SLA Met</span>
                                        </td>

                                        <td class="px-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                  :class="statusBadgeClass(t.status)"
                                                  x-text="statusLabel(t.status)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function myQueuePage() {
            return {
                loading: false,
                timer: null,
                newTickets: [],
                ongoingTickets: [],
                waitingTickets: [],
                resolvedTickets: [],

                init() {
                    this.loadQueue();

                    this.timer = setInterval(() => {
                        this.newTickets = [...this.newTickets];
                        this.ongoingTickets = [...this.ongoingTickets];
                        this.waitingTickets = [...this.waitingTickets];
                    }, 1000);
                },

                destroy() {
                    if (this.timer) clearInterval(this.timer);
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },

                showAlert(message, type = 'success') {
                    const el = document.getElementById('page-alert');
                    el.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                    el.textContent = message;

                    if (type === 'success') {
                        el.classList.add('bg-green-100', 'text-green-800');
                    } else {
                        el.classList.add('bg-red-100', 'text-red-800');
                    }

                    setTimeout(() => {
                        el.classList.add('hidden');
                    }, 3000);
                },

                async loadQueue() {
                    this.loading = true;

                    try {
                        const response = await fetch('/api/it/my-queue', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to load my queue');
                        }

                        const data = result.data || {};
                        this.newTickets = data.new_tickets || [];
                        this.ongoingTickets = data.ongoing_tickets || [];
                        this.waitingTickets = data.waiting_tickets || [];
                        this.resolvedTickets = data.resolved_tickets || [];
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to load my queue', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async updateStatus(ticketId, status) {
                    try {
                        const response = await fetch(`/api/it/tickets/${ticketId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ status })
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to update status');
                        }

                        this.showAlert(result.message || 'Status updated successfully', 'success');
                        await this.loadQueue();
                    } catch (error) {
                        console.error(error);
                        this.showAlert(error.message || 'Failed to update status', 'error');
                        await this.loadQueue();
                    }
                },

                ucfirst(value) {
                    if (!value) return '-';
                    value = String(value);
                    return value.charAt(0).toUpperCase() + value.slice(1);
                },

                statusLabel(status) {
                    const map = {
                        new: 'New',
                        in_progress: 'On Going',
                        waiting_info: 'Waiting Info',
                        resolved: 'Resolved',
                        closed: 'Closed',
                    };
                    return map[status] || status || '-';
                },

                priorityBadgeClass(priority) {
                    switch (priority) {
                        case 'critical': return 'bg-red-600 text-white';
                        case 'high': return 'bg-orange-500 text-white';
                        case 'medium': return 'bg-amber-300 text-slate-900';
                        case 'low': return 'bg-green-600 text-white';
                        default: return 'bg-gray-200 text-slate-900';
                    }
                },

                statusBadgeClass(status) {
                    switch (status) {
                        case 'new': return 'bg-gray-200 text-slate-900';
                        case 'in_progress': return 'bg-orange-500 text-white';
                        case 'waiting_info': return 'bg-amber-400 text-slate-900';
                        case 'resolved': return 'bg-green-600 text-white';
                        case 'closed': return 'bg-sky-700 text-white';
                        default: return 'bg-gray-200 text-slate-900';
                    }
                },

                slaCountdown(deadline) {
                    if (!deadline) return '-';

                    const end = new Date(deadline).getTime();
                    const now = Date.now();
                    const diff = end - now;

                    if (diff <= 0) return 'OVERDUE';

                    const totalSeconds = Math.floor(diff / 1000);
                    const hours = Math.floor(totalSeconds / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;

                    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }
            }
        }
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\henan-ticketing\resources\views/it/my-queue.blade.php ENDPATH**/ ?>