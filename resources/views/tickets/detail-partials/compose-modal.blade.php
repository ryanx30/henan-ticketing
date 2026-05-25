        {{-- Compose / Reply modal --}}
        <div
            x-show="showCompose"
            x-transition
            class="fixed inset-0 z-50"
            style="display:none;">
            <div class="absolute inset-0 bg-black/20" @click="discardDraft()"></div>

            <div class="pointer-events-auto fixed bottom-0 right-6 z-50 w-full max-w-3xl overflow-hidden rounded-t-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between bg-slate-100 px-5 py-3">
                    <h3 class="text-[18px] font-semibold text-slate-900" x-text="composeMode === 'reply' ? 'Reply Message' : 'New Message'"></h3>

                    <div class="flex items-center gap-4 text-slate-500">
                        <button type="button" @click="discardDraft()">✕</button>
                    </div>
                </div>

                <form @submit.prevent="submitMessage" class="p-5" enctype="multipart/form-data">
                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Ticket</span>
                            <div class="w-full text-sm text-slate-800" x-text="currentTicketLabel()"></div>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">To</span>
                            <div class="w-full text-sm text-slate-800" x-text="composeRecipientLabel()"></div>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 py-2">
                        <div class="flex items-center gap-4">
                            <span class="w-14 text-sm text-slate-700">Subject</span>
                            <input type="text" x-model="composeForm.subject" class="w-full border-0 bg-transparent text-sm outline-none" placeholder="Message subject">
                        </div>
                    </div>

                    <div class="py-4">
                        <textarea
                            x-model="composeForm.body"
                            rows="10"
                            class="w-full resize-none border-0 text-sm outline-none"
                            placeholder="Write your message..."></textarea>
                    </div>

                    <div class="mb-4 flex items-center gap-3">
                        <label class="cursor-pointer text-slate-600 hover:text-slate-900" title="Attach file">
                            <input type="file" class="hidden" @change="handleAttachment($event)">
                            <span class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6.5l-7.8 7.8a3 3 0 104.2 4.2l8.5-8.5a5 5 0 00-7.1-7.1l-9 9a7 7 0 009.9 9.9l7.1-7.1" />
                                </svg>
                                Attach file
                            </span>
                        </label>

                        <template x-if="composeForm.attachmentName">
                            <span class="text-sm text-slate-500" x-text="composeForm.attachmentName"></span>
                        </template>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                        <button
                            type="submit"
                            class="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                            :disabled="messageSubmitting || !composeForm.body.trim()">
                            <span x-text="messageSubmitting ? 'Sending...' : 'Send'"></span>
                        </button>

                        <button
                            type="button"
                            @click="discardDraft()"
                            class="text-slate-500 hover:text-red-600">
                            Discard
                        </button>
                    </div>
                </form>
            </div>
        </div>
