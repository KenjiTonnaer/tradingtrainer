<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welcome Back')" :description="__('Sign in to continue your trading journey')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <div>
                <flux:input
                    name="email"
                    :label="__('E-mailadres')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="jouw@email.nl"
                    class="w-full"
                />
            </div>

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Wachtwoord')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Voer je wachtwoord in')"
                    viewable
                    class="w-full"
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0 text-purple-600 hover:text-pink-500 transition-colors" :href="route('password.request')" wire:navigate>
                        {{ __('Wachtwoord vergeten?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Onthoud mij')" :checked="old('remember')" />

            <div class="flex items-center justify-end mt-2">
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500 text-white font-bold rounded-lg hover:shadow-lg hover:scale-105 transition-all duration-300" data-test="login-button">
                    {{ __('Inloggen') }}
                </button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-gray-600">
                <span>{{ __('Nog geen account?') }}</span>
                <flux:link :href="route('register')" wire:navigate class="text-purple-600 hover:text-pink-500 font-semibold transition-colors">{{ __('Registreer nu') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
