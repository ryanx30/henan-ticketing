        
        <div class="mt-5 flex items-center justify-end gap-3 text-sm text-slate-700">
            <div class="flex items-center gap-2">
                <span>Items per page:</span>

                <select
                    x-model="filters.per_page"
                    style="height:32px;border-radius:6px;border:1px solid #cbd5e1;background:white url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2364748b\' stroke-width=\'2.5\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M19 9l-7 7-7-7\'/></svg>') no-repeat right 8px center;appearance:none;-webkit-appearance:none;padding:0 28px 0 8px;font-size:13px;line-height:32px;color:#334155;cursor:pointer;outline:none;"
                    @change="applyFilters()">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <div id="history-pagination" class="flex items-center gap-1"></div>
        </div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/it/history-partials/pagination.blade.php ENDPATH**/ ?>