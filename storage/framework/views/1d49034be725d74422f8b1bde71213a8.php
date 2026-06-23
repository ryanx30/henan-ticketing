


<div class="overflow-hidden rounded bg-white shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
    <div class="bg-[#051823] px-5 py-3">
        <h2 class="text-[20px] font-semibold text-white" x-text="currentLabelPlural()"></h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-slate-800">
            <?php echo $__env->make('admin.master-data.partials.table-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('admin.master-data.partials.table-body', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </table>
    </div>

    <?php echo $__env->make('admin.master-data.partials.table-pagination', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/admin/master-data/partials/table-card.blade.php ENDPATH**/ ?>