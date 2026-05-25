            {{-- KPI CARDS: Role-based report metrics rendered from the Reports API. --}}
            <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <template x-for="card in cardItems" :key="card.key">
                    <div class="rounded bg-white p-6 text-center shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                        <div class="text-lg text-slate-700" x-text="card.label"></div>
                        <div class="mt-2 text-[52px] leading-none text-[#2f6f8f]" x-text="card.value"></div>
                        <div class="mt-2 min-h-[18px] text-xs text-slate-500" x-text="card.description || ''"></div>
                    </div>
                </template>
            </div>
