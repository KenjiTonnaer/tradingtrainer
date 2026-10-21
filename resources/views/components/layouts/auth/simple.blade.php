<!DOCTYPE html>
<html lang="nl">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-slate-100 antialiased">
    <div class="flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10">
        <a href="{{ route('home') }}" class="brand" style="color:#13243b"><span class="brand-mark">↗</span> TradingTrainer</a>
        <div class="w-full max-w-md panel p-8">{{ $slot }}</div>
        <p class="hint">Oefen met een plan. Leer van elke beslissing.</p>
    </div>
    @fluxScripts
</body>
</html>