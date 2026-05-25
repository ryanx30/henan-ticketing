            
            <div class="mb-5 rounded bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="mb-6 text-[28px] font-semibold text-slate-800">Trend (Resolved / Closed)</div>

                <div class="rounded border border-slate-200 bg-slate-50 p-5">
                    <div class="h-[360px]">
                        <div
                            x-show="trend.labels.length === 0"
                            class="flex h-full items-center justify-center text-sm text-slate-500">
                            No trend data available.
                        </div>

                        <div x-show="trend.labels.length > 0" class="h-full">
                            <canvas id="reportsTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/reports/partials/trend-card.blade.php ENDPATH**/ ?>