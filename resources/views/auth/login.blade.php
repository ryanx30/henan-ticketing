<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4"
         style="background: linear-gradient(90deg, #051823 0%, #0C3B56 50%, #145E89 100%);">
        
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
                        <x-text-input id="email"
                                      class="block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40"
                                      type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                                      placeholder="Username / Email" />
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                {{-- Password --}}
                <div>
                    <x-input-label for="password" :value="__('Password')" class="text-white/90" />
                    <div class="mt-1">
                        <x-text-input id="password"
                                      class="block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40"
                                      type="password" name="password" required autocomplete="current-password"
                                      placeholder="Password" />
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between text-sm text-white/80">
                    <label for="remember_me" class="inline-flex items-center gap-2">
                        <input id="remember_me" type="checkbox"
                               class="rounded border-[#051823]/50 bg-transparent text-[#051823] focus:ring-0"
                               name="remember">
                        <span>Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="underline hover:text-white" href="{{ route('password.request') }}">
                            Forgot Password?
                        </a>
                    @endif
                </div>

                <button type="submit"
                        class="w-full h-12 rounded-2xl bg-[#CDEEFF] text-[#051823] font-bold text-lg shadow-lg">
                    Login
                </button>

                <div class="text-center text-sm text-white/80">
                    <a class="underline hover:text-white" href="{{ route('register') }}">Create account</a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>