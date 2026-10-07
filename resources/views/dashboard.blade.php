<x-layouts.app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $allTrades = $user->paperTrades()->latest()->take(6)->get();
        $latestBuyAt = $user->paperTrades()
            ->where('type', 'buy')
            ->latest('created_at')
            ->get()
            ->groupBy('symbol')
            ->map(fn ($trades) => $trades->first()->created_at?->toISOString());
        $portfolioPositions = $user->paperPositions()
            ->latest()
            ->get()
            ->map(fn ($position) => [
                'symbol' => $position->symbol,
                'asset_type' => $position->asset_type,
                'quantity' => (float) $position->quantity,
                'total_invested' => (float) $position->total_invested,
                'avg_buy_price' => (float) $position->avg_buy_price,
                'last_buy_at' => $latestBuyAt[$position->symbol] ?? null,
            ])
            ->values();
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
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5z"/>
                            <path d="M3 10h18"/>
                            <path d="M16 15h2"/>
                        </svg>
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
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 19h18"/>
                            <path d="M7 16V8"/>
                            <path d="M12 16V5"/>
                            <path d="M17 16v-4"/>
                        </svg>
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
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 12.5l3 3 7-7"/>
                            <circle cx="12" cy="12" r="9"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm {{ $realizedPnl >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-semibold">{{ $realizedPnl >= 0 ? '+' : '' }}€ {{ number_format($realizedPnl, 2, ',', '.') }} gerealiseerd</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <div
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60"
                x-data="dashboardPortfolio(@js($portfolioPositions))"
                x-init="init()"
            >
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.15em] text-slate-500">Portfolio</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-900">Actieve posities</h2>
                    </div>
                    <a href="{{ route('markets.show', ['symbol' => 'AAPL']) }}" class="rounded-full bg-violet-50 px-3 py-1.5 text-sm font-semibold text-violet-700 transition hover:bg-violet-100">Open markt</a>
                </div>

                <div class="mb-4 flex flex-col gap-3 sm:flex-row">
                    <label class="relative flex-1">
                        <span class="sr-only">Zoek in dashboardportfolio</span>
                        <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 1 0 3.478 9.762l3.63 3.63 1.06-1.06-3.63-3.63A5.5 5.5 0 0 0 8.5 3ZM4.5 8.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd"/>
                        </svg>
                        <input
                            type="search"
                            x-model="searchTerm"
                            placeholder="Zoek op symbool"
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                        >
                    </label>
                    <select
                        x-model="assetFilter"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                    >
                        <option value="all">Alles</option>
                        <option value="stock">Aandelen</option>
                        <option value="crypto">Crypto</option>
                    </select>
                    <select
                        x-model="sortBy"
                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600 outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                    >
                        <option value="symbol">Alfabetisch</option>
                        <option value="recent">Recent aangekocht</option>
                        <option value="value">Totale waarde</option>
                        <option value="pnl">Winst/verlies</option>
                    </select>
                </div>

                <div class="space-y-3">
                    <template x-for="position in filteredPositions" :key="position.symbol">
                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 font-bold text-white">
                                    <span x-text="position.symbol.substring(0, 2)"></span>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800" x-text="position.symbol"></p>
                                    <p class="text-xs text-slate-500" x-text="Number(position.quantity).toLocaleString('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' in bezit'"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-900" x-text="livePrices[position.symbol] ? '€ ' + formatNumber(currentValue(position)) : 'Waarde laden...'"></p>
                                <p class="text-xs text-slate-500" x-text="'Ingelegd € ' + formatNumber(position.total_invested)"></p>
                                <p
                                    class="text-xs font-semibold"
                                    :class="pnl(position) >= 0 ? 'text-emerald-600' : 'text-red-600'"
                                    x-text="livePrices[position.symbol] ? (pnl(position) >= 0 ? '+' : '') + '€ ' + formatNumber(pnl(position)) + ' W/V' : 'W/V laden...'"
                                ></p>
                            </div>
                        </div>
                    </template>
                    <div
                        x-show="positions.length === 0"
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center"
                    >
                        <p class="text-lg font-semibold text-slate-700">Nog geen open posities</p>
                        <p class="mt-1 text-sm text-slate-500">Je eerste trade maakt meteen je portfolio zichtbaar.</p>
                    </div>
                    <div
                        x-show="positions.length > 0 && filteredPositions.length === 0"
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center"
                    >
                        <p class="text-lg font-semibold text-slate-700">Geen posities gevonden</p>
                        <p class="mt-1 text-sm text-slate-500">Pas je zoekterm of filter aan.</p>
                    </div>
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
                                    <p class="text-xs text-slate-500">{{ ucfirst($trade->type) }} • {{ number_format((float) $trade->quantity, 2, ',', '.') }}</p>
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
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-slate-800">Nieuwe trade</span>
                </a>
                <a href="{{ route('markets.show', ['symbol' => 'MSFT']) }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 19h18"/>
                            <path d="M7 16V8"/>
                            <path d="M12 16V5"/>
                            <path d="M17 16v-4"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-slate-800">Analytics</span>
                </a>
                <a href="{{ route('markets.show', ['symbol' => 'NVDA']) }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-violet-500 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 8h14"/>
                            <path d="M5 12h14"/>
                            <path d="M5 16h14"/>
                            <path d="M8 4v16"/>
                            <path d="M16 4v16"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-slate-800">Markten</span>
                </a>
                <a href="{{ route('markets.show', ['symbol' => 'BTC']) }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 15a8 8 0 1 1 16 0"/>
                            <path d="M12 4v8"/>
                            <path d="M12 12l4.5 2.5"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-slate-800">Crypto</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
