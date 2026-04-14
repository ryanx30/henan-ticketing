<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4"
         style="background: linear-gradient(90deg, #051823 0%, #0C3B56 50%, #145E89 100%);">

        <div class="w-full max-w-md rounded-[36px] shadow-2xl border border-white/10 bg-white/30 backdrop-blur-md p-8">
            <div class="flex flex-col items-center text-white">
                <img src="{{ asset('images/logo-henan.png') }}" class="h-20 w-auto mb-3" alt="Henan" />

                <div class="mt-5 text-2xl font-semibold">Ticketing System</div>
                <div class="mt-1 text-base opacity-90">Create account</div>
            </div>

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
                @csrf

                {{-- GRID 2 KOLOM --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- Name (kiri) --}}
                    <div>
                        <x-input-label for="name" :value="__('Name')" class="text-white/90" />
                        <x-text-input id="name"
                                      class="mt-1 block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40"
                                      type="text" name="name" :value="old('name')" required autofocus autocomplete="name"
                                      placeholder="Full name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- Email (kanan) --}}
                    <div>
                        <x-input-label for="email" :value="__('Email')" class="text-white/90" />
                        <x-text-input id="email"
                                      class="mt-1 block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40"
                                      type="email" name="email" :value="old('email')" required autocomplete="username"
                                      placeholder="name@company.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Password (kiri) --}}
                    <div>
                        <x-input-label for="password" :value="__('Password')" class="text-white/90" />
                        <x-text-input id="password"
                                      class="mt-1 block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40"
                                      type="password" name="password" required autocomplete="new-password"
                                      placeholder="Password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Confirm Password (kanan) --}}
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-white/90" />
                        <x-text-input id="password_confirmation"
                                      class="mt-1 block w-full rounded-xl bg-[#061d29] border border-white/10 text-white placeholder-white/40"
                                      type="password" name="password_confirmation" required autocomplete="new-password"
                                      placeholder="Confirm password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                                        {{-- Role (kiri) --}}
                    <div>
                        <x-input-label for="role" :value="__('Role')" class="text-white/90" />
                        <select id="role" name="role"
                                class="mt-1 block w-full rounded-xl bg-[#061d29] border border-white/10 text-white">
                            <option value="cs" @selected(old('role','cs')==='cs')>Customer Service (CS)</option>
                            <option value="it" @selected(old('role')==='it')>IT</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    {{-- Spacer (kanan) biar rapi, nanti bisa dipakai field lain --}}
                    <div class="hidden md:block"></div>

                </div>

                <div class="flex items-center justify-between text-sm text-white/80">
                    <a class="underline hover:text-white" href="{{ route('login') }}">
                        Already registered?
                    </a>
                </div>

                <button type="submit"
                        class="w-full h-12 rounded-2xl bg-[#CDEEFF] text-[#051823] font-bold text-lg shadow-lg">
                    Register
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>