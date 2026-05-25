<div class="overflow-hidden rounded bg-white shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
    <div class="bg-[#051823] px-5 py-3">
        <h2 class="text-[20px] font-semibold text-white" x-text="currentLabelPlural()"></h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-slate-800">
            @include('admin.master-data.partials.table-head')
            @include('admin.master-data.partials.table-body')
        </table>
    </div>

    @include('admin.master-data.partials.table-pagination')
</div>
