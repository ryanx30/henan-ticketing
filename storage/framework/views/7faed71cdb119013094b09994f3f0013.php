<div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
    <div class="space-y-5">
        
        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.total.value)"></div>
                    <div class="mt-1 text-[16px] text-slate-700">Total Tickets</div>
                </div>
                <img src="<?php echo e(asset('images/icons/total.png')); ?>" alt="Total Tickets" class="h-10 w-10 object-contain opacity-90" />
            </div>

            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                <div class="flex items-center justify-between gap-3">
                    <span>Created This Month:</span>
                    <b class="text-slate-900" x-text="formatNumber(kpi.total.current_month)"></b>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <span>Created Last Month:</span>
                    <b class="text-slate-900" x-text="formatNumber(kpi.total.prev_month)"></b>
                </div>
            </div>
        </div>

        
        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.new.value)"></div>
                    <div class="mt-1 text-[16px] text-slate-700">New Tickets</div>
                </div>
                <img src="<?php echo e(asset('images/icons/new.png')); ?>" alt="New Tickets" class="h-10 w-10 object-contain opacity-90" />
            </div>

            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.new.prev_month)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.new.mom)"
                        x-text="trendText(kpi.new.mom)"></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.new.prev_year)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.new.yoy)"
                        x-text="trendText(kpi.new.yoy)"></span>
                </div>
            </div>
        </div>

        
        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.in_progress.value)"></div>
                    <div class="mt-1 text-[16px] text-slate-700">Ongoing</div>
                </div>
                <img src="<?php echo e(asset('images/icons/ongoing.png')); ?>" alt="Ongoing" class="h-10 w-10 object-contain opacity-90" />
            </div>

            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.in_progress.prev_month)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.in_progress.mom)"
                        x-text="trendText(kpi.in_progress.mom)"></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.in_progress.prev_year)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.in_progress.yoy)"
                        x-text="trendText(kpi.in_progress.yoy)"></span>
                </div>
            </div>
        </div>

        
        <div class="min-h-[132px] rounded-sm border border-slate-200 bg-white px-4 py-4 shadow-lg">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-[28px] font-bold leading-none text-slate-900" x-text="formatNumber(kpi.resolved.value)"></div>
                    <div class="mt-1 text-[16px] text-slate-700">Resolved</div>
                </div>
                <img src="<?php echo e(asset('images/icons/resolved.png')); ?>" alt="Resolved" class="h-10 w-10 object-contain opacity-90" />
            </div>

            <div class="mt-5 space-y-1.5 text-[16px] text-slate-700">
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.resolved.prev_month)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.resolved.mom)"
                        x-text="trendText(kpi.resolved.mom)"></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.resolved.prev_year)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.resolved.yoy)"
                        x-text="trendText(kpi.resolved.yoy)"></span>
                </div>
            </div>
        </div>

        
        <div class="overflow-hidden rounded-sm border border-slate-200 shadow-lg">
            <div class="min-h-[74px] bg-red-600 px-4 py-4 text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-[28px] font-bold leading-none" x-text="formatNumber(kpi.sla_risk.value)"></div>
                        <div class="mt-1 text-[16px]">SLA Risk</div>
                    </div>
                    <img src="<?php echo e(asset('images/icons/sla.png')); ?>" alt="SLA Risk" class="h-10 w-10 object-contain" />
                </div>
            </div>

            <div class="space-y-1.5 bg-white px-4 py-4 text-[16px] text-slate-700">
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Month: <b class="text-slate-900" x-text="formatNumber(kpi.sla_risk.prev_month)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.sla_risk.mom)"
                        x-text="trendText(kpi.sla_risk.mom)"></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>Previous Year: <b class="text-slate-900" x-text="formatNumber(kpi.sla_risk.prev_year)"></b></span>
                    <span
                        class="inline-flex whitespace-nowrap rounded px-2 py-1 text-[14px] font-semibold"
                        :class="trendClass(kpi.sla_risk.yoy)"
                        x-text="trendText(kpi.sla_risk.yoy)"></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/dashboard/partials/kpi-cards.blade.php ENDPATH**/ ?>