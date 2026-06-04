{{-- ========= MASTER DATA PAGE CONTROLS ========= --}}
{{-- Page heading, type switcher, and search/filter controls for master data. --}}

            <div class="mb-6">
                <h1 class="text-[34px] font-bold text-[#051823]">MASTER DATA</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Manage categories, issue types, teams, priorities, and SLA rules.
                </p>
            </div>

            <div class="mb-5 rounded bg-white p-3 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap gap-2">
                    <template x-for="tab in tabs" :key="tab.key">
                        <button
                            type="button"
                            @click="switchTab(tab.key)"
                            class="rounded-full px-4 py-2 text-sm font-medium transition"
                            :class="activeTab === tab.key
                                ? 'bg-slate-900 text-white'
                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            x-text="tab.label"></button>
                    </template>
                </div>
            </div>

            <div class="mb-5 rounded bg-white p-4 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="flex flex-wrap items-center gap-3">
                    <input
                        x-model="filters.q"
                        @keydown.enter.prevent="applyFilters()"
                        type="text"
                        placeholder="Search..."
                        class="h-10 w-full max-w-[300px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">

                    <div class="ml-auto flex items-center gap-2">
                        <select
                            x-model="filters.per_page"
                            @change="applyFilters()"
                            class="h-10 w-[90px] rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>

                        <button
                            type="button"
                            @click="applyFilters()"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Apply
                        </button>

                        <button
                            type="button"
                            @click="resetFilters()"
                            class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Reset
                        </button>

                        <button
                            type="button"
                            @click="openCreate()"
                            class="rounded bg-slate-900 px-4 py-2 text-sm text-white shadow hover:bg-slate-800">
                            <span x-text="`+ Add ${currentLabelSingle()}`"></span>
                        </button>
                    </div>
                </div>
            </div>
