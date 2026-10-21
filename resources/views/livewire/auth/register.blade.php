<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Open je oefenrekening')" :description="__('Maak een account aan en oefen met virtueel geld.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Naam')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Volledige naam')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('E-mailadres')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Wachtwoord')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Wachtwoord')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Bevestig je wachtwoord')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Bevestig je wachtwoord')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Oefenrekening aanmaken') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Heb je al een account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Inloggen') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
