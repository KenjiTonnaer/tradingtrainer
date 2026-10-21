<!DOCTYPE html>
<html lang="nl">
<head>@include('partials.head')</head>
<body class="app-body" x-data="{ menuOpen: false }">
    <a class="skip-link" href="#main-content">Naar inhoud</a>
    <header class="mobile-bar">
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">↗</span> TradingTrainer</a>
        <button class="icon-button" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="app-navigation" aria-label="Navigatie openen">☰</button>
    </header>
    <aside id="app-navigation" class="app-sidebar" :class="{ 'is-open': menuOpen }" @keydown.escape.window="menuOpen = false">
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">↗</span> TradingTrainer</a>
        <p class="nav-caption">JOUW WERKPLEK</p>
        <nav aria-label="Hoofdnavigatie">
            @foreach ([['dashboard', 'Overzicht', '◫'], ['markets.show', 'Handelen', '↗'], ['journal.index', 'Handelsdagboek', '≡'], ['replay.index', 'Oefenstudio', '▷']] as [$route, $label, $icon])
                <a href="{{ $route === 'markets.show' ? route($route, ['symbol' => 'AAPL']) : route($route) }}" class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}" @if(request()->routeIs($route)) aria-current="page" @endif>
                    <span aria-hidden="true">{{ $icon }}</span>{{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="sidebar-note"><span class="status-dot"></span> Alleen virtueel geld<p>Oefen met een plan.<br>Leer van elke beslissing.</p></div>
        <div class="sidebar-account">
            <span class="avatar">{{ auth()->user()->initials() }}</span>
            <div><strong>{{ auth()->user()->name }}</strong><a href="{{ route('profile.edit') }}">Instellingen</a></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link w-full" type="submit" data-test="logout-button">↪ Uitloggen</button></form>
        <a href="{{ route('home') }}" class="sidebar-home">Naar de website ↗</a>
    </aside>
    <main id="main-content" class="app-main" tabindex="-1">
        @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
        {{ $slot }}
    </main>
    @fluxScripts
</body>
</html>