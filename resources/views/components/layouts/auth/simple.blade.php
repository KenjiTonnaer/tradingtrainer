<!DOCTYPE html>
<html lang="nl">
<head>@include('partials.head')</head>
<body class="auth-body antialiased">
    <div class="flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10">
        <x-brand />
        <div class="w-full max-w-md panel p-8">{{ $slot }}</div>
        <p class="hint">Oefen met een plan. Leer van elke beslissing.</p>
    </div>
    @fluxScripts
</body>
</html>
