<x-layouts.app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $allTrades = $user->paperTrades()->latest()->take(6)->get();
        $sellTrades = $user->paperTrades()->where('type', 'sell')->get();
        $realizedPnl = round($sellTrades->sum(fn ($trade) => (float) ($trade->pnl ?? 0)), 2);
        $winningTrades = $sellTrades->filter(fn ($trade) => (float) ($trade->pnl ?? 0) > 0)->count();
        $winRate = $sellTrades->count() > 0 ? round(($winningTrades / $sellTrades->count()) * 100, 1) : 0;
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 bg-slate-100/70 p-6">
        <div class="overflow-hidden rounded-3xl bg-gradient-to-r from-slate-950 via-violet-950 to-fuchsia-700 p-8 text-white shadow-2xl shadow-violet-900/20">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.22em] text-violet-200">Trading overview</p>
                    <h1 class="text-3xl font-black tracking-tight">Welkom terug, {{ $user->name }} 👋</h1>
                    <p class="mt-2 text-sm text-violet-100">Je real-time portfolio en trade-history zijn meteen zichtbaar.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-violet-200">Saldo</p>
                    <p class="mt-1 text-2xl font-bold">{{ $user->wallet?->formatted_balance ?? '€ 0,00' }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="group overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-[0_14px_30px_-20px_rgba(15,23,42,0.45)] transition-transform duration-200 hover:-translate-y-1">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Wallet saldo</p>
                        <h3 class="mt-2 text-3xl font-black text-slate-900">{{ $user->wallet?->formatted_balance ?? '€ 0,00' }}</h3>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white shadow-lg shadow-violet-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-sm text-slate-500">Beschikbaar om nieuwe orders uit te voeren.</p>
            </div>

            <div class="group overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-[0_14px_30px_-20px_rgba(15,23,42,0.45)] transition-transform duration-200 hover:-translate-y-1">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Totaal trades</p>
                        <h3 class="mt-2 text-3xl font-black text-slate-900">{{ $user->paperTrades()->count() }}</h3>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                </div>
                <p class="text-sm text-slate-500">{{ $sellTrades->count() }} gerealiseerde verkopen afgerond.</p>
            </div>

            <div class="group overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-[0_14px_30px_-20px_rgba(15,23,42,0.45)] transition-transform duration-200 hover:-translate-y-1">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Win rate</p>
                        <h3 class="mt-2 text-3xl font-black text-slate-900">{{ $winRate }}%</h3>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <p class="text-sm {{ $realizedPnl >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-semibold">{{ $realizedPnl >= 0 ? '+' : '' }}€ {{ number_format($realizedPnl, 2, ',', '.') }} gerealiseerd</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-500">Portfolio</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-900">Actieve posities</h2>
                    </div>
                    <a href="{{ route('markets.show', ['symbol' => 'AAPL']) }}" class="rounded-full bg-violet-50 px-3 py-1.5 text-sm font-semibold text-violet-700 transition hover:bg-violet-100">Open markt</a>
                </div>

                <div class="space-y-3">
                    @forelse ($user->paperPositions()->latest()->get() as $position)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 font-bold text-white">
                                    {{ substr($position->symbol, 0, 2) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800">{{ $position->symbol }}</p>
                                    <p class="text-xs text-slate-500">{{ $position->quantity }} in bezit</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-900">€ {{ number_format((float) $position->total_invested, 2, ',', '.') }}</p>
                                <p class="text-xs text-slate-500">Gem. prijs € {{ number_format((float) $position->avg_buy_price, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                            <p class="text-lg font-semibold text-slate-700">Nog geen open posities</p>
                            <p class="mt-1 text-sm text-slate-500">Je eerste trade maakt meteen je portfolio zichtbaar.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-[0_16px_30px_-22px_rgba(15,23,42,0.5)]">
                <div class="mb-5">
                    <p class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-500">Recent trades</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-900">Laatste orders</h2>
                </div>

                <div class="space-y-3">
                    @forelse ($allTrades as $trade)
                        @php
                            $pnl = (float) ($trade->pnl ?? 0);
                            $positive = $trade->type === 'buy' ? false : $pnl >= 0;
                        @endphp

                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $trade->type === 'buy' ? 'bg-emerald-500' : 'bg-rose-500' }} font-bold text-white">
                                    {{ strtoupper(substr($trade->symbol, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900">{{ $trade->symbol }}</p>
                                    <p class="text-xs text-slate-500">{{ ucfirst($trade->type) }} • {{ number_format((float) $trade->quantity, 6, ',', '.') }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold {{ $trade->type === 'buy' ? 'text-slate-700' : ($pnl >= 0 ? 'text-emerald-600' : 'text-red-600') }}">
                                    {{ $trade->type === 'buy' ? '€ ' . number_format((float) $trade->total_value, 2, ',', '.') : (($pnl >= 0 ? '+' : '-') . '€ ' . number_format(abs($pnl), 2, ',', '.')) }}
                                </p>
                                <p class="text-xs text-slate-500">{{ $trade->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                            <p class="text-lg font-semibold text-slate-700">Nog geen trades</p>
                            <p class="mt-1 text-sm text-slate-500">Je eerste koop- of verkooporder verschijnt hier.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-violet-200 bg-gradient-to-r from-violet-50 to-pink-50 p-6 shadow-lg shadow-violet-100/60">
            <h2 class="mb-4 text-2xl font-black text-slate-900">Snelle acties</h2>
            <div class="grid gap-4 md:grid-cols-4">
                <a href="{{ route('markets.show', ['symbol' => 'AAPL']) }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg></div>
                    <span class="font-semibold text-slate-800">Nieuwe trade</span>
                </a>
                <a href="{{ route('markets.show', ['symbol' => 'MSFT']) }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                    <span class="font-semibold text-slate-800">Analytics</span>
                </a>
                <a href="{{ route('markets.show', ['symbol' => 'NVDA']) }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-violet-500 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                    <span class="font-semibold text-slate-800">Markten</span>
                </a>
                <a href="{{ route('markets.show', ['symbol' => 'BTC']) }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <span class="font-semibold text-slate-800">Crypto</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
