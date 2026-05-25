{{-- Pagination is Alpine-driven to avoid string-built HTML and global onclick handlers. --}}
<div class="mt-5 flex flex-col gap-3 text-sm text-slate-700 md:flex-row md:items-center md:justify-end">
    <div class="flex items-center gap-2">
        <span>Items per page:</span>
        <select
            x-model="filters.per_page"
            @change="applyFilters()"
            class="h-9 w-16 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>

    <div class="flex items-center gap-1" x-show="Number(meta.last_page || 1) > 1">
        <button
            type="button"
            @click="goToPage(Number(meta.current_page || 1) - 1)"
            :disabled="Number(meta.current_page || 1) <= 1"
            class="rounded border bg-white px-3 py-1 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
            ‹
        </button>

        <template x-for="item in paginationItems()" :key="`page-${item}`">
            <span>
                <span
                    x-show="item === '...'"
                    class="select-none px-2 py-1 text-sm text-slate-500">...</span>

                <button
                    x-show="item !== '...'"
                    type="button"
                    @click="goToPage(item)"
                    class="rounded border px-3 py-1 text-sm"
                    :class="Number(item) === Number(meta.current_page || 1) ? 'border-slate-900 bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50'"
                    x-text="item"></button>
            </span>
        </template>

        <button
            type="button"
            @click="goToPage(Number(meta.current_page || 1) + 1)"
            :disabled="Number(meta.current_page || 1) >= Number(meta.last_page || 1)"
            class="rounded border bg-white px-3 py-1 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
            ›
        </button>
    </div>
</div>
