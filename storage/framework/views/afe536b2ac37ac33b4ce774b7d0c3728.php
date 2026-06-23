


        
        <div
            x-show="showCompose"
            x-transition
            class="fixed inset-0 z-50"
            style="display: none;">
            <div class="pointer-events-none absolute inset-0 bg-transparent"></div>

            <div class="pointer-events-auto fixed bottom-0 right-6 z-50 w-full max-w-xl overflow-hidden rounded-t-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-slate-100 px-5 py-3">
                    <h3 class="text-[18px] font-semibold text-slate-900">New Message</h3>

                    <div class="flex items-center gap-4 text-slate-500">
                        <button type="button" @click="showCompose = false">—</button>
                        <button type="button" @click="discardDraft()">✕</button>
                    </div>
                </div>

                <form @submit.prevent="submitCompose" class="p-5" enctype="multipart/form-data">
                    <input type="hidden" x-model="form.to_user_id">

                    
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Ticket</span>

                            <select
                                x-model="form.ticket_id"
                                @change="syncTicketMeta()"
                                class="w-full border-0 bg-transparent text-sm outline-none">
                                <option value="">Choose Ticket</option>
                                <template x-for="ticket in composeTickets" :key="ticket.id">
                                    <option
                                        :value="ticket.id"
                                        x-text="`${ticketLabel(ticket)} - ${ticket.title}`">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>

                    
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">To</span>

                            <input
                                type="text"
                                x-model="form.to_display"
                                readonly
                                class="w-full border-0 bg-transparent text-sm text-slate-800 outline-none"
                                placeholder="<?php echo e($isIT ? 'Auto-filled from ticket creator' : 'Auto-filled from ticket holder'); ?>">
                        </div>
                    </div>

                    
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Subject</span>
                            <input
                                type="text"
                                x-model="form.subject"
                                class="w-full border-0 bg-transparent text-sm outline-none"
                                placeholder="Message subject">
                        </div>
                    </div>

                    
                    <div class="py-4">
                        <textarea
                            x-model="form.body"
                            rows="8"
                            class="w-full resize-none border-0 text-sm outline-none"
                            placeholder="Write your message..."></textarea>
                    </div>


                    
                    <template x-if="form.attachmentPreview">
                        <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1"
                                    :class="attachmentIconClass()">
                                    <template x-if="form.attachmentPreview.status === 'uploading'">
                                        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                        </svg>
                                    </template>

                                    <template x-if="form.attachmentPreview.status !== 'uploading'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                        </svg>
                                    </template>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800" x-text="attachmentFileName()"></p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        <span x-text="attachmentStatusLabel()"></span>
                                        <template x-if="attachmentSizeLabel()">
                                            <span x-text="` • ${attachmentSizeLabel()}`"></span>
                                        </template>
                                    </p>

                                    <template x-if="form.attachmentPreview.status === 'uploading'">
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200">
                                            <div class="h-full w-2/3 animate-pulse rounded-full bg-blue-600"></div>
                                        </div>
                                    </template>
                                </div>

                                <button
                                    type="button"
                                    @click="clearAttachment()"
                                    class="rounded-full p-1.5 text-slate-400 transition hover:bg-white hover:text-red-600"
                                    aria-label="Remove attachment">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    
                    <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                        <div class="flex items-center gap-3">
                            <button
                                type="submit"
                                class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.attachmentPreview?.status === 'uploading'">
                                <span x-text="form.attachmentPreview?.status === 'uploading' ? 'Preparing...' : 'Send'"></span>
                            </button>

                            <div class="group relative">
                                <label class="cursor-pointer text-slate-600 hover:text-slate-900">
                                    <input
                                        :key="form.attachmentInputKey"
                                        type="file"
                                        @change="handleAttachment"
                                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"
                                        class="hidden">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                    </svg>
                                </label>

                                <div class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
                                    Attach file
                                </div>
                            </div>
                        </div>

                        <div class="group relative">
                            <button
                                type="button"
                                @click="discardDraft()"
                                class="text-slate-500 hover:text-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 3v7m4-7v7m4-7v7M7 7l1 12h8l1-12" />
                                </svg>
                            </button>

                            <div class="pointer-events-none absolute -top-9 right-0 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-[11px] text-white opacity-0 shadow transition group-hover:opacity-100">
                                Discard draft
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
<?php /**PATH C:\laragon\www\henan-ticketing\resources\views/resolver-inbox/partials/compose-modal.blade.php ENDPATH**/ ?>