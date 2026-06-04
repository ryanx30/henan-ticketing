<x-guest-layout>
    <div
        x-data="{ showPassword: false }"
        class="min-h-screen flex items-center justify-center px-4"
        style="background: linear-gradient(90deg, #051823 0%, #0C3B56 50%, #145E89 100%);"
    >
        <div class="w-full max-w-md rounded-[36px] shadow-2xl border border-white/10 bg-white/30 backdrop-blur-md p-8">
            <div class="flex flex-col items-center text-white">
                <img src="{{ asset('images/logo-henan.png') }}" class="h-20 w-auto mb-3" alt="Henan" />

                <div class="mt-5 text-2xl font-semibold">Ticketing System</div>
            </div>

            <x-auth-session-status class="mt-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <x-input-label for="email" :value="__('Email')" class="text-white/90" />
                    <div class="mt-1">
                        <x-text-input
                            id="email"
                            class="block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Username / Email"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                {{-- Password --}}
                <div>
                    <x-input-label for="password" :value="__('Password')" class="text-white/90" />

                    <div class="mt-1 relative">
                        <x-text-input
                            id="password"
                            class="block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40 pr-11"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                        />

                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-white/60 hover:text-white"
                            :title="showPassword ? 'Hide password' : 'Show password'"
                        >
                            <svg
                                x-show="!showPassword"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                                style="display: none;"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>

                            <svg
                                x-show="showPassword"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                                style="display: none;"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587A2 2 0 0013.414 13.4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A9.77 9.77 0 0112 4.8c4.478 0 8.268 2.943 9.542 7a9.72 9.72 0 01-4.15 5.262M6.228 6.228A9.744 9.744 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.61 0 3.13-.38 4.478-1.055" />
                            </svg>
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between text-sm text-white/80">
                    <label for="remember_me" class="inline-flex items-center gap-2">
                        <input
                            id="remember_me"
                            type="checkbox"
                            class="rounded border-[#051823]/50 bg-transparent text-[#051823] focus:ring-0"
                            name="remember"
                        >
                        <span>Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="underline hover:text-white" href="{{ route('password.request') }}">
                            Forgot Password?
                        </a>
                    @endif
                </div>

                <button
                    type="submit"
                    class="w-full h-12 rounded-2xl bg-[#CDEEFF] text-[#051823] font-bold text-lg shadow-lg"
                >
                    Login
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>