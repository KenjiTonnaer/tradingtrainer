<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'TradingTrainer - Master Your Trading Skills' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $styles ?? '' }}
</head>
<body class="antialiased bg-gradient-to-br from-purple-600 via-pink-500 to-orange-500 min-h-screen">
    <nav class="sticky top-0 pt-4 px-4 z-50">
        <div class="max-w-6xl mx-auto bg-slate-900/80 backdrop-blur-lg rounded-2xl shadow-lg border border-slate-700/50">
            <div class="px-6 relative flex items-center h-14">
                <a href="/" class="flex items-center hover:opacity-80 transition">
                    <svg class="h-8 w-8 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-white">TradingTrainer</span>
                </a>
                <a href="{{ route('markets.show', ['symbol' => 'AAPL']) }}" class="absolute left-1/3 -translate-x-1/2 px-5 py-2 rounded-full bg-white/20 backdrop-blur text-sm font-bold text-white hover:bg-white/30 hover:scale-110 transition-all duration-300 shadow-lg">
                    TradingView
                </a>
                <div class="ml-auto flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-bold text-yellow-300 hover:text-white hover:scale-110 transition-all duration-300">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-white/80 hover:text-white transition">Inloggen</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur text-white text-sm font-bold rounded-lg hover:bg-white/30 transition shadow">Gratis Starten</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    {{ $slot }}

    {{ $scripts ?? '' }}
</body>
</html>
