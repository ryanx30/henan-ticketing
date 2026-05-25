{{-- ATTACHMENTS: Files uploaded when the ticket was created or updated. --}}
<div class="rounded-sm border border-slate-200 bg-white shadow-lg">
    <div class="border-b border-slate-200 px-6 py-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-[20px] font-bold text-slate-900">Attachments</h2>
                <p class="mt-1 text-sm text-slate-500">Supporting files submitted with this ticket.</p>
            </div>

            <span
                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                x-text="`${attachments.length} file(s)`">
            </span>
        </div>
    </div>

    <div class="px-6 py-5">
        <template x-if="attachments.length === 0">
            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                No attachments available.
            </div>
        </template>

        <template x-if="attachments.length > 0">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <template x-for="attachment in attachments" :key="attachment.id">
                    <a
                        :href="attachment.download_url"
                        target="_blank"
                        rel="noopener"
                        class="group overflow-hidden rounded-lg border border-slate-200 bg-slate-50 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md">
                        <template x-if="isImageAttachment(attachment)">
                            <div class="h-40 overflow-hidden border-b border-slate-200 bg-white">
                                <img
                                    :src="attachment.download_url"
                                    :alt="attachment.file_name || 'Attachment preview'"
                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.02]">
                            </div>
                        </template>

                        <template x-if="!isImageAttachment(attachment)">
                            <div class="flex h-40 items-center justify-center border-b border-slate-200 bg-white">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-sm font-bold uppercase text-white"
                                    x-text="attachmentExtension(attachment)">
                                </div>
                            </div>
                        </template>

                        <div class="px-4 py-3">
                            <div class="truncate text-sm font-semibold text-slate-900" x-text="attachment.file_name || 'Attachment'"></div>
                            <div class="mt-1 flex items-center justify-between gap-3 text-xs text-slate-500">
                                <span x-text="formatFileSize(attachment.file_size)"></span>
                                <span class="font-semibold text-[#2f88d8]">Open</span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </template>
    </div>
</div>
