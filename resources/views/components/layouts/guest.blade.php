<!DOCTYPE html>
<html lang="nl">
<head>@include('partials.head')</head>
<body class="landing">
    <a class="skip-link" href="#main-content">Naar inhoud</a>
    <header class="landing-header">
        <nav class="landing-nav" aria-label="Hoofdnavigatie">
            <x-brand />
            <a href="#werkwijze" class="nav-method">De werkwijze <span aria-hidden="true">↘</span></a>
            <div class="landing-links">
                @auth
                    <a href="{{ route('dashboard') }}" class="button primary">Naar je handelsdesk <span aria-hidden="true">↗</span></a>
                @else
                    <a href="{{ route('login') }}" class="login-link">Inloggen</a>
                    <a href="{{ route('register') }}" class="button primary">Open oefenrekening <span aria-hidden="true">↗</span></a>
                @endauth
            </div>
        </nav>
    </header>
    <main id="main-content">{{ $slot }}</main>
    <footer class="landing-footer">
        <x-brand />
        <p>Alleen virtueel geld. Simulatie en historische resultaten<br>voorspellen geen toekomstig rendement.</p>
        <span>© {{ now()->year }} TradingTrainer</span>
    </footer>
    @fluxScripts
</body>
</html>