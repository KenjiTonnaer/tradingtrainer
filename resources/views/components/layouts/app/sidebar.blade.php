<!DOCTYPE html>
<html lang="nl">
<head>@include('partials.head')</head>
<body class="app-body" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
    <a class="skip-link" href="#main-content">Naar inhoud</a>
    <header class="workspace-header">
        <x-brand :href="route('dashboard')" />
        <nav id="app-navigation" class="workspace-nav" :class="{ 'is-open': menuOpen }" aria-label="Hoofdnavigatie">
            @foreach ([['dashboard', 'Overzicht', '01'], ['markets.show', 'Handelen', '02'], ['journal.index', 'Dagboek', '03'], ['replay.index', 'Oefenstudio', '04']] as [$route, $label, $number])
                <a href="{{ $route === 'markets.show' ? route($route, ['symbol' => 'AAPL']) : route($route) }}" class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}" @if(request()->routeIs($route)) aria-current="page" @endif>
                    <span aria-hidden="true">{{ $number }}</span>{{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="workspace-account">
            <span class="workspace-mode"><span class="status-dot"></span> PAPER / EUR</span>
            <details class="account-menu">
                <summary><span class="avatar">{{ auth()->user()->initials() }}</span><span class="account-name">{{ auth()->user()->name }}</span><span aria-hidden="true">⌄</span></summary>
                <div class="account-popover">
                    <a href="{{ route('profile.edit') }}">Instellingen ↗</a>
                    <a href="{{ route('home') }}">Naar de website ↗</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" data-test="logout-button">Uitloggen ↪</button></form>
                </div>
            </details>
            <button class="icon-button menu-toggle" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="app-navigation" aria-label="Navigatie openen">☰</button>
        </div>
    </header>
    <main id="main-content" class="app-main" tabindex="-1">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
        {{ $slot }}
    </main>
    <footer class="workspace-footer"><span>TRADINGTRAINER / OEFENOMGEVING</span><span>Een plan vóór de trade. Een les erna.</span></footer>
    @fluxScripts
</body>
</html>