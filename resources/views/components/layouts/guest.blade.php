<!DOCTYPE html>
<html lang="nl">
<head>@include('partials.head')</head>
<body class="landing">
    <a class="skip-link" href="#main-content">Naar inhoud</a>
    <nav class="landing-nav" aria-label="Hoofdnavigatie"><a href="{{ route('home') }}" class="brand"><span class="brand-mark">↗</span> TradingTrainer</a><div class="landing-links">@auth<a href="{{ route('dashboard') }}" class="button primary">Naar je dashboard →</a>@else<a href="{{ route('login') }}">Inloggen</a><a href="{{ route('register') }}" class="button primary">Begin met oefenen →</a>@endauth</div></nav>
    <main id="main-content">{{ $slot }}</main>
    <footer class="landing-footer"><span>TradingTrainer · {{ now()->year }}</span><span>Een oefenplatform met virtueel geld.<br>Simulatie en historische resultaten voorspellen geen toekomstig rendement.</span></footer>
    @fluxScripts
</body>
</html>