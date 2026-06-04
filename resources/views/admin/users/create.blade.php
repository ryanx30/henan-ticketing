{{-- ========= ADMIN USER CREATE ========= --}}
{{-- Create user form layout; submission is handled by the page script. --}}

<x-app-layout>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div
        x-data="adminUserCreatePage()"
        x-init="init()"
        class="min-h-screen bg-[#eef1f5] px-8 py-7"
    >
        <div class="mx-auto w-full max-w-[1000px]">
            <div id="page-alert" class="hidden mb-4 rounded p-3 text-sm"></div>

            <div class="mb-6">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700">
                    ← Back to Users
                </a>

                <h1 class="mt-2 text-[34px] font-bold text-[#051823]">CREATE USER</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Add a new system user and assign role access.
                </p>
            </div>

            <div class="rounded bg-white p-6 shadow-[0_4px_16px_rgba(15,23,42,0.08)]">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Full Name</label>
                        <input
                            x-model="form.name"
                            type="text"
                            class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none"
                            placeholder="Enter full name"
                        >
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                        <input
                            x-model="form.email"
                            type="email"
                            class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none"
                            placeholder="Enter email"
                        >
                        <p x-show="errors.email" x-text="errors.email" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Role</label>
                        <select
                            x-model="form.role"
                            class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-slate-400 focus:outline-none"
                        >
                            <option value="cs">CS</option>
                            <option value="it">IT</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Admin</option>
                        </select>
                        <p x-show="errors.role" x-text="errors.role" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-3 rounded-md border border-slate-300 px-4 py-3">
                            <input
                                x-model="form.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                            >
                            <span class="text-sm text-slate-700">Set user as active</span>
                        </label>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Password</label>

                        <div class="relative">
                            <input
                                x-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="h-11 w-full rounded-md border border-slate-300 px-3 pr-11 text-sm focus:border-slate-400 focus:outline-none"
                                placeholder="Minimum 8 characters"
                            >

                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-500 hover:text-slate-700"
                                :title="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>

                                <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587A2 2 0 0013.414 13.4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A9.77 9.77 0 0112 4.8c4.478 0 8.268 2.943 9.542 7a9.72 9.72 0 01-4.15 5.262M6.228 6.228A9.744 9.744 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.61 0 3.13-.38 4.478-1.055" />
                                </svg>
                            </button>
                        </div>

                        <p x-show="errors.password" x-text="errors.password" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Confirm Password</label>

                        <div class="relative">
                            <input
                                x-model="form.password_confirmation"
                                :type="showPasswordConfirm ? 'text' : 'password'"
                                class="h-11 w-full rounded-md border border-slate-300 px-3 pr-11 text-sm focus:border-slate-400 focus:outline-none"
                                placeholder="Repeat password"
                            >

                            <button
                                type="button"
                                @click="showPasswordConfirm = !showPasswordConfirm"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-500 hover:text-slate-700"
                                :title="showPasswordConfirm ? 'Hide password' : 'Show password'"
                            >
                                <svg x-show="!showPasswordConfirm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>

                                <svg x-show="showPasswordConfirm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587A2 2 0 0013.414 13.4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A9.77 9.77 0 0112 4.8c4.478 0 8.268 2.943 9.542 7a9.72 9.72 0 01-4.15 5.262M6.228 6.228A9.744 9.744 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.61 0 3.13-.38 4.478-1.055" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="rounded border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="button"
                        @click="submit()"
                        :disabled="submitting"
                        class="rounded bg-slate-900 px-5 py-2 text-sm text-white shadow hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span x-text="submitting ? 'Saving...' : 'Create User'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>