<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welkom terug')" :description="__('Log in op je oefenrekening.')" />

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
                    <flux:link class="absolute top-0 text-sm end-0 text-link" :href="route('password.request')" wire:navigate>
                        {{ __('Wachtwoord vergeten?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Onthoud mij')" :checked="old('remember')" />

            <div class="flex items-center justify-end mt-2">
                <button type="submit" class="button primary full" data-test="login-button">
                    {{ __('Inloggen') }}
                </button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse muted">
                <span>{{ __('Nog geen account?') }}</span>
                <flux:link :href="route('register')" wire:navigate class="text-link">{{ __('Registreer nu') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
