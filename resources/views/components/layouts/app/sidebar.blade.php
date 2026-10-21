<!DOCTYPE html>
<html lang="nl">
<head>@include('partials.head')</head>
<body class="app-body" x-data="workspaceShell" :class="{ 'navigation-open': menuOpen, 'sidebar-collapsed': sidebarCollapsed }" @resize.window="if (window.innerWidth > 900) menuOpen = false" @keydown.escape.window="if (menuOpen) { menuOpen = false; $refs.navigationToggle.focus() }">
    @php
        $section = match (true) {
            request()->routeIs('markets.*') => 'Handelen',
            request()->routeIs('journal.*') => 'Handelsdagboek',
            request()->routeIs('replay.*') => 'Oefenstudio',
            request()->is('settings*') => 'Instellingen',
            default => 'Overzicht',
        };
    @endphp
    <a class="skip-link" href="#main-content">Naar inhoud</a>
    <button class="sidebar-backdrop" x-show="menuOpen" x-cloak @click="menuOpen = false; $refs.navigationToggle.focus()" aria-label="Navigatie sluiten" tabindex="-1"></button>
    <aside class="app-sidebar" :class="{ 'is-open': menuOpen }" aria-label="Zijbalk">
        <div class="sidebar-brand-row">
            <x-brand :href="route('dashboard')" />
            <button class="sidebar-close icon-button" x-ref="sidebarClose" @click="menuOpen = false; $refs.navigationToggle.focus()" aria-label="Navigatie sluiten">×</button>
        </div>
        <div class="sidebar-section-heading">
            <p class="sidebar-caption">Je werkplek</p>
            <button class="sidebar-collapse-toggle" type="button" @click="toggleSidebar()" :aria-expanded="!sidebarCollapsed" aria-controls="app-navigation" :aria-label="sidebarCollapsed ? 'Sidebar uitklappen' : 'Sidebar inklappen'" :title="sidebarCollapsed ? 'Sidebar uitklappen' : 'Sidebar inklappen'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" :class="{ 'points-right': sidebarCollapsed }" aria-hidden="true"><path d="m14 6-6 6 6 6" /></svg>
            </button>
        </div>
        <nav id="app-navigation" class="sidebar-nav" aria-label="Hoofdnavigatie">
            @foreach ([
                ['dashboard', 'Overzicht', 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z'],
                ['markets.show', 'Handelen', 'M3 3v18h18 M6 15l5-6 4 3 6-8'],
                ['journal.index', 'Handelsdagboek', 'M4 3h13a3 3 0 0 1 3 3v15H7a3 3 0 0 1-3-3z M4 17h16 M8 7h8 M8 11h6'],
                ['replay.index', 'Oefenstudio', 'M9 7l8 5-8 5z M3 12a9 9 0 1 0 3-6 M3 3v5h5'],
            ] as [$route, $label, $icon])
                <a href="{{ $route === 'markets.show' ? route($route, ['symbol' => 'AAPL']) : route($route) }}" title="{{ $label }}" class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}" @if(request()->routeIs($route)) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}" /></svg>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        <div class="sidebar-practice-note"><span class="status-dot"></span> Oefenrekening<p>Je handelt met virtueel geld.<br>Alle bedragen zijn in euro's.</p></div>
        <nav class="sidebar-nav sidebar-secondary" aria-label="Accountnavigatie">
            <a href="{{ route('profile.edit') }}" title="Instellingen" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}" @if(request()->is('settings*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z M12 3v2 M12 19v2 M3 12h2 M19 12h2 M5.6 5.6 7 7 M17 17l1.4 1.4 M5.6 18.4 7 17 M17 7l1.4-1.4" /></svg>
                <span>Instellingen</span>
            </a>
            <a href="{{ route('home') }}" title="Naar de website" class="nav-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10 12 3l8 7v11h-6v-7h-4v7H4z" /></svg><span>Naar de website</span></a>
        </nav>
        <div class="sidebar-profile" title="{{ auth()->user()->name }}"><span class="avatar">{{ auth()->user()->initials() }}</span><div><strong>{{ auth()->user()->name }}</strong><span>Je persoonlijke werkplek</span></div></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="sidebar-logout" type="submit" data-test="logout-button" aria-label="Uitloggen" title="Uitloggen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H4v16h5 M9 12h12 M17 8l4 4-4 4" /></svg><span class="sidebar-logout-label">Uitloggen</span></button></form>
    </aside>
    <div class="workspace-content">
        <header class="workspace-header" :inert="menuOpen">
            <div class="workspace-heading"><button class="icon-button menu-toggle" x-ref="navigationToggle" @click="menuOpen = true; $nextTick(() => $refs.sidebarClose.focus())" :aria-expanded="menuOpen" aria-controls="app-navigation" aria-label="Navigatie openen">☰</button><span class="workspace-breadcrumb">Je werkplek <span aria-hidden="true">/</span></span><strong>{{ $section }}</strong></div>
            <span class="workspace-mode"><span class="status-dot"></span> Virtueel geld · EUR</span>
        </header>
        <main id="main-content" class="app-main" :inert="menuOpen" tabindex="-1">
            @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
            {{ $slot }}
        </main>
        <footer class="workspace-footer" :inert="menuOpen"><span>TradingTrainer · Oefen met een plan</span><span>Een les na iedere beslissing.</span></footer>
    </div>
    @fluxScripts
</body>
</html>
