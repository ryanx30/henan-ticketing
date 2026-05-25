{{-- Focus filter banner is separated from the page shell to keep dashboard-to-ticket behavior readable. --}}
<div
    x-cloak
    x-show="filters.focus"
    class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
    <div class="flex items-center justify-between gap-3">
        <div>
            <span class="font-semibold">Focus Filter Active:</span>
            <span x-text="focusLabel(filters.focus)"></span>
        </div>

        <button
            type="button"
            @click="clearFocus()"
            class="rounded border border-sky-300 bg-white px-3 py-1 text-xs font-medium text-sky-700 hover:bg-sky-100">
            Clear Focus
        </button>
    </div>
</div>
