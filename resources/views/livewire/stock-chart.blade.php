<div class="w-full">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500 rounded-2xl p-6 text-white shadow-xl mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold mb-1">📈 Live Trading Charts</h1>
                <p class="text-purple-100">Real-time marktdata van Alpaca Markets (IEX)</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2 border border-white/30">
                <span class="text-sm font-semibold">{{ count($historicalData) }} candles loaded</span>
            </div>
        </div>
    </div>

    <!-- Header met symbool en huidige prijs -->
    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
            <div class="flex items-center gap-4">
                @php
                    $cryptoSymbols = ['BTC', 'ETH', 'SOL', 'DOGE', 'XRP', 'ADA', 'LINK', 'DOT', 'MATIC', 'LTC', 'AVAX'];
                    $isCrypto = in_array($symbol, $cryptoSymbols);
                @endphp

                @if($isCrypto)
                    <select wire:change="changeSymbol($event.target.value)" class="px-4 py-2 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-lg text-gray-900 font-bold text-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <optgroup label="💰 Crypto (24/7 Live via CoinGecko)">
                            <option value="BTC" @selected($symbol === 'BTC')>₿ Bitcoin</option>
                            <option value="ETH" @selected($symbol === 'ETH')>Ξ Ethereum</option>
                            <option value="SOL" @selected($symbol === 'SOL')>◎ Solana</option>
                            <option value="DOGE" @selected($symbol === 'DOGE')>Ð Dogecoin</option>
                            <option value="XRP" @selected($symbol === 'XRP')>✕ Ripple</option>
                            <option value="ADA" @selected($symbol === 'ADA')>₳ Cardano</option>
                            <option value="LINK" @selected($symbol === 'LINK')>🔗 Chainlink</option>
                            <option value="DOT" @selected($symbol === 'DOT')>● Polkadot</option>
                            <option value="MATIC" @selected($symbol === 'MATIC')>⬡ Polygon</option>
                            <option value="LTC" @selected($symbol === 'LTC')>Ł Litecoin</option>
                            <option value="AVAX" @selected($symbol === 'AVAX')>🔺 Avalanche</option>
                        </optgroup>
                    </select>
                @else
                    <select wire:change="changeSymbol($event.target.value)" class="px-4 py-2 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-lg text-gray-900 font-bold text-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <optgroup label="🇺🇸 US Stocks (9:30–16:00 EST)">
                            <option value="AAPL" @selected($symbol === 'AAPL')>AAPL - Apple Inc.</option>
                            <option value="TSLA" @selected($symbol === 'TSLA')>TSLA - Tesla</option>
                            <option value="MSFT" @selected($symbol === 'MSFT')>MSFT - Microsoft</option>
                            <option value="GOOGL" @selected($symbol === 'GOOGL')>GOOGL - Alphabet</option>
                            <option value="AMZN" @selected($symbol === 'AMZN')>AMZN - Amazon</option>
                            <option value="META" @selected($symbol === 'META')>META - Meta Platforms</option>
                            <option value="NVDA" @selected($symbol === 'NVDA')>NVDA - NVIDIA</option>
                            <option value="AMD" @selected($symbol === 'AMD')>AMD - Advanced Micro Devices</option>
                            <option value="NFLX" @selected($symbol === 'NFLX')>NFLX - Netflix</option>
                            <option value="DIS" @selected($symbol === 'DIS')>DIS - Disney</option>
                        </optgroup>
                    </select>
                @endif

                <div>
                    <h2 class="text-3xl font-bold text-gray-900">{{ $symbol }}</h2>
                    <div class="flex items-baseline gap-3 mt-1">
                        <span class="text-3xl font-bold text-gray-900">${{ number_format($currentQuote['c'] ?? 0, 2) }}</span>
                        <span class="text-lg font-semibold {{ ($currentQuote['dp'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ ($currentQuote['dp'] ?? 0) >= 0 ? '▲' : '▼' }}
                            {{ number_format(abs($currentQuote['dp'] ?? 0), 2) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- WebSocket Status -->
            <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg border border-gray-200">
                <span class="inline-block w-2 h-2 rounded-full bg-gray-400" id="ws-indicator-{{ $symbol }}"></span>
                <span id="ws-text-{{ $symbol }}" class="text-sm font-medium text-gray-700">Connecting...</span>
            </div>
        </div>

        @if(!$isCrypto)
            @php
                $now = \Carbon\Carbon::now('America/New_York');
                $isWeekday = $now->isWeekday();
                $hour = $now->hour;
                $minute = $now->minute;
                $marketOpen = $isWeekday && (($hour == 9 && $minute >= 30) || ($hour > 9 && $hour < 16));
            @endphp
            @if(!$marketOpen)
                <div class="mt-4 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-amber-800">US Market Closed</p>
                            <p class="text-sm text-amber-700 mt-1">
                                Live data is only available Mon–Fri 9:30 AM–4:00 PM ET.
                                @if($isWeekday) Market opens at 9:30 AM ET. @else Market opens Monday at 9:30 AM ET. @endif
                                Currently showing historical/simulated data.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- Timeframe selector -->
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach(['1m', '5m', '15m', '30m', '1h', '6h', '12h', '1D', '30D', '6M', '1Y', 'ALL'] as $tf)
                <button
                    wire:click="changeTimeframe('{{ $tf }}')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200
                        {{ $timeframe === $tf
                            ? 'bg-gradient-to-r from-purple-600 to-pink-500 text-white shadow-md'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    {{ $tf }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white rounded-xl p-4 shadow-lg border border-purple-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Current Price</p>
                    <h3 class="text-xl font-bold text-gray-900">${{ number_format($currentQuote['c'] ?? 0, 2) }}</h3>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-lg border border-green-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Daily Change</p>
                    <h3 class="text-xl font-bold {{ ($currentQuote['dp'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(abs($currentQuote['dp'] ?? 0), 2) }}%
                    </h3>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Previous Close</p>
                    <h3 class="text-xl font-bold text-gray-900">${{ number_format($currentQuote['pc'] ?? 0, 2) }}</h3>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-lg border border-blue-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Timeframe</p>
                    <h3 class="text-xl font-bold text-gray-900">{{ $timeframe }}</h3>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ CHART + TRADE PANEL rij ══════════════════════════════════════════ -->
    <div class="flex flex-col xl:flex-row gap-6 mb-6">

        <!-- Linker kolom: charts -->
        <div class="flex-1 min-w-0 flex flex-col gap-4">
            <!-- Price Chart -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Price Chart</h3>
                <div
                    id="stock-chart-{{ $symbol }}"
                    class="w-full rounded-lg"
                    style="height: 500px;"
                    data-symbol="{{ $symbol }}"
                    data-timeframe="{{ $timeframe }}"
                    data-candles="{{ json_encode($historicalData) }}"
                ></div>
            </div>

            <!-- Volume Chart -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Volume</h3>
                <div
                    id="volume-chart-{{ $symbol }}"
                    class="w-full rounded-lg"
                    style="height: 120px;"
                ></div>
            </div>
        </div>

        <!-- Rechter kolom: trade panel -->
        @auth
        <div
            class="xl:w-80 w-full"
            x-data="tradePanel('{{ $symbol }}', {{ $currentQuote['c'] ?? 0 }})"
            x-init="init()"
        >
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden sticky top-4">

                <!-- Panel header -->
                <div class="bg-gradient-to-r from-purple-600 to-pink-500 px-6 py-4">
                    <p class="text-white text-xs font-semibold uppercase tracking-wider mb-1">Paper Trading</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-white text-2xl font-bold" x-text="'$' + price.toFixed(2)"></span>
                        <span class="text-purple-200 text-sm" x-text="symbol"></span>
                    </div>
                </div>

                <div class="p-6 space-y-5">

                    <!-- Wallet saldo -->
                    <div class="bg-gray-50 rounded-xl px-4 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-600 font-medium">💰 Beschikbaar</span>
                        <span class="text-sm font-bold text-gray-900"
                            data-wallet-balance="{{ auth()->user()->wallet->balance ?? 0 }}"
                            x-text="'€' + walletBalance.toLocaleString('nl-NL', {minimumFractionDigits:2, maximumFractionDigits:2})">
                        </span>                    
                    </div>

                    <!-- Buy / Sell toggle -->
                    <div class="flex rounded-xl overflow-hidden border border-gray-200">
                        <button
                            @click="side = 'buy'"
                            :class="side === 'buy'
                                ? 'bg-green-500 text-white shadow-inner'
                                : 'bg-white text-gray-600 hover:bg-green-50'"
                            class="flex-1 py-3 text-sm font-bold transition-all duration-200"
                        >
                            ▲ Kopen
                        </button>
                        <button
                            @click="side = 'sell'"
                            :class="side === 'sell'
                                ? 'bg-red-500 text-white shadow-inner'
                                : 'bg-white text-gray-600 hover:bg-red-50'"
                            class="flex-1 py-3 text-sm font-bold transition-all duration-200"
                        >
                            ▼ Verkopen
                        </button>
                    </div>

                    <!-- Toggle: aantal coins vs bedrag in € -->
                    <div class="flex rounded-lg overflow-hidden border border-gray-300 mb-3">
                        <button
                            @click="inputMode = 'quantity'"
                            :class="inputMode === 'quantity' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="flex-1 px-3 py-1.5 text-sm font-medium transition-colors"
                        >
                            Aantal
                        </button>
                        <button
                            @click="inputMode = 'amount'"
                            :class="inputMode === 'amount' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="flex-1 px-3 py-1.5 text-sm font-medium transition-colors"
                        >
                            Bedrag ($)
                        </button>
                    </div>

                    <!-- Hoeveelheid -->
                    <div x-show="inputMode === 'quantity'">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wider">Hoeveelheid</label>
                        <div class="flex items-center gap-2">
                            <button
                                @click="decrement()"
                                class="w-9 h-9 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg font-bold text-gray-700 transition"
                            >−</button>
                            <input
                                type="number"
                                x-model="quantity"
                                :step="isCrypto() ? '0.000001' : '1'"
                                :min="isCrypto() ? '0.000001' : '1'"
                                :placeholder="isCrypto() ? '0.001' : '1'"
                                class="w-full px-3 py-2 border rounded-lg text-right font-mono"
                            >
                            <button
                                @click="increment()"
                                class="w-9 h-9 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg font-bold text-gray-700 transition"
                            >+</button>
                        </div>
                    </div>

                    <!-- Amount input (dollars/euros) -->
                    <div x-show="inputMode === 'amount'">
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                            <input
                                type="number"
                                x-model="amountInput"
                                @input="quantity = price > 0 ? parseFloat((amountInput / price).toFixed(6)) : 0"
                                step="0.01"
                                min="0.01"
                                placeholder="100.00"
                                class="w-full pl-7 pr-3 py-2 border rounded-lg text-right font-mono"
                            >
                        </div>
                        <p class="text-xs text-gray-500 mt-1" x-show="quantity > 0">
                            ≈ <span x-text="quantity"></span> <span x-text="symbol"></span>
                        </p>
                    </div>

                    <!-- Prijs -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Prijs per stuk ($)</label>
                            <button
                                @click="price = livePrice"
                                class="text-xs text-purple-600 hover:text-purple-800 font-semibold underline"
                            >Live prijs</button>
                        </div>
                        <input
                            type="number"
                            x-model.number="price"
                            min="0.01"
                            step="0.01"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-purple-400"
                            placeholder="0.00"
                        />
                    </div>

                    <!-- Snelkeuze % van wallet (alleen bij kopen) -->
                    <div x-show="side === 'buy'" class="flex gap-2">
                        <template x-for="pct in [25, 50, 75, 100]" :key="pct">
                            <button
                                @click="setByPercent(pct)"
                                class="flex-1 py-1.5 text-xs font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg transition"
                                x-text="pct + '%'"
                            ></button>
                        </template>
                    </div>

                    <!-- Totaal -->
                    <div class="bg-gray-50 rounded-xl px-4 py-3 space-y-1">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotaal</span>
                            <span class="font-semibold text-gray-900" x-text="'$' + total.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span x-text="quantity + ' × $' + price.toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Fout- / succesbericht -->
                    <div x-show="message" x-cloak>
                        <div
                            :class="isError ? 'bg-red-50 border-red-300 text-red-700' : 'bg-green-50 border-green-300 text-green-700'"
                            class="border rounded-xl px-4 py-3 text-sm font-medium"
                            x-text="message"
                        ></div>
                    </div>

                    <!-- Submit knop -->
                    <button
                        @click="submitOrder()"
                        :disabled="loading || quantity <= 0 || price <= 0"
                        :class="side === 'buy'
                            ? 'bg-green-500 hover:bg-green-600 disabled:bg-green-200'
                            : 'bg-red-500 hover:bg-red-600 disabled:bg-red-200'"
                        class="w-full py-3 text-white font-bold rounded-xl transition-all duration-200 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span x-text="loading ? 'Bezig...' : (side === 'buy' ? '▲ Koop ' + symbol : '▼ Verkoop ' + symbol)"></span>
                    </button>
                </div>
            </div>
        </div>
        @endauth
    </div>
    <!-- ══ EINDE chart + trade panel rij ════════════════════════════════════ -->

    <!-- Info panel -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-200 mb-6">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900 mb-1">
                    <strong>Data bron:</strong>
                    @if(empty(config('services.alpaca.key')) || config('services.alpaca.key') === 'your_alpaca_key_id')
                        <span class="text-orange-600">Simulated data</span> (voeg geldige Alpaca API keys toe in <code>.env</code>)
                    @else
                        <span class="text-green-600">Alpaca Markets</span> (REST + server polling)
                    @endif
                </p>
                <p class="text-sm text-gray-600">
                    <strong>Timeframe:</strong> {{ $timeframe }} |
                    <strong>Candles:</strong> {{ count($historicalData) }}
                </p>
            </div>
        </div>
    </div>

    <!-- ══ PORTFOLIO TABEL ═══════════════════════════════════════════════════ -->
    @auth
    <div
        class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6"
        x-data="portfolio()"
        x-init="load()"
    >
        <!-- Tabel header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">📊 Mijn Portfolio</h2>
                <p class="text-sm text-gray-500 mt-0.5">Alle open posities op dit moment</p>
            </div>
            <button
                @click="load()"
                class="flex items-center gap-2 px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 text-sm font-semibold rounded-lg transition"
            >
                <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Vernieuwen
            </button>
        </div>

        <!-- Loading state -->
        <div x-show="loading && positions.length === 0" class="px-6 py-12 text-center">
            <svg class="animate-spin w-8 h-8 text-purple-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <p class="text-sm text-gray-500">Portfolio laden...</p>
        </div>

        <!-- Lege state -->
        <div x-show="!loading && positions.length === 0" class="px-6 py-12 text-center">
            <div class="text-4xl mb-3">📭</div>
            <p class="text-gray-500 font-medium">Nog geen open posities</p>
            <p class="text-sm text-gray-400 mt-1">Gebruik het trade paneel hierboven om je eerste aandeel te kopen.</p>
        </div>

        <!-- Tabel -->
        <div x-show="positions.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Symbool</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aandelen</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Gem. Prijs</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Huidige Prijs</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Huidige Waarde</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">W&V</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">W&V %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="pos in positions" :key="pos.symbol">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <!-- Symbool -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-bold text-xs" x-text="pos.symbol.substring(0,2)"></span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900" x-text="pos.symbol"></p>
                                        <p class="text-xs text-gray-400" x-text="pos.asset_type === 'crypto' ? 'Crypto' : 'Stock'"></p>
                                    </div>
                                </div>
                            </td>
                            <!-- Aandelen -->
                            <td class="px-6 py-4 text-right font-semibold text-gray-900" x-text="parseFloat(pos.quantity).toLocaleString('nl-NL', {maximumFractionDigits:6})"></td>
                            <!-- Gem. aankoopprijs -->
                            <td class="px-6 py-4 text-right text-gray-600" x-text="'$' + parseFloat(pos.avg_buy_price).toFixed(2)"></td>
                            <!-- Huidige prijs -->
                            <td class="px-6 py-4 text-right">
                                <span
                                    class="font-semibold"
                                    :class="(livePrices[pos.symbol] || 0) >= parseFloat(pos.avg_buy_price) ? 'text-green-600' : 'text-red-600'"
                                    x-text="livePrices[pos.symbol] ? '$' + livePrices[pos.symbol].toFixed(2) : '—'"
                                ></span>
                            </td>
                            <!-- Huidige waarde -->
                            <td class="px-6 py-4 text-right font-semibold text-gray-900"
                                x-text="livePrices[pos.symbol]
                                    ? '$' + (parseFloat(pos.quantity) * livePrices[pos.symbol]).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
                                    : '—'"
                            ></td>
                            <!-- Absolute W&V -->
                            <td class="px-6 py-4 text-right">
                                <template x-if="livePrices[pos.symbol]">
                                    <span
                                        :class="(parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested)) >= 0 ? 'text-green-600 font-bold' : 'text-red-600 font-bold'"
                                        x-text="((parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested)) >= 0 ? '+' : '') +
                                                '$' + (parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested)).toFixed(2)"
                                    ></span>
                                </template>
                                <template x-if="!livePrices[pos.symbol]">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>
                            <!-- W&V % -->
                            <td class="px-6 py-4 text-right">
                                <template x-if="livePrices[pos.symbol] && parseFloat(pos.total_invested) > 0">
                                    <span
                                        :class="((parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested)) / parseFloat(pos.total_invested) * 100) >= 0
                                            ? 'inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-bold'
                                            : 'inline-flex items-center px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold'"
                                        x-text="(((parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested)) / parseFloat(pos.total_invested) * 100) >= 0 ? '+' : '') +
                                                ((parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested)) / parseFloat(pos.total_invested) * 100).toFixed(2) + '%'"
                                    ></span>
                                </template>
                                <template x-if="!livePrices[pos.symbol]">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <!-- Totaalrij -->
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td class="px-6 py-4 font-bold text-gray-900" colspan="4">Totaal portfolio</td>
                        <!-- Totale huidige waarde -->
                        <td class="px-6 py-4 text-right font-bold text-gray-900"
                            x-text="'$' + positions.reduce((sum, pos) => sum + (livePrices[pos.symbol] ? parseFloat(pos.quantity) * livePrices[pos.symbol] : 0), 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})"
                        ></td>
                        <!-- Totale W&V absoluut -->
                        <td class="px-6 py-4 text-right">
                            <span
                                :class="positions.reduce((sum, pos) => sum + (livePrices[pos.symbol] ? parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested) : 0), 0) >= 0
                                    ? 'font-bold text-green-600'
                                    : 'font-bold text-red-600'"
                                x-text="(positions.reduce((sum, pos) => sum + (livePrices[pos.symbol] ? parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested) : 0), 0) >= 0 ? '+' : '') +
                                        '$' + positions.reduce((sum, pos) => sum + (livePrices[pos.symbol] ? parseFloat(pos.quantity) * livePrices[pos.symbol] - parseFloat(pos.total_invested) : 0), 0).toFixed(2)"
                            ></span>
                        </td>
                        <td class="px-6 py-4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endauth
    <!-- ══ EINDE portfolio tabel ═════════════════════════════════════════════ -->
@script
    (function() {
        // ── Chart initialisatie ────────────────────────────────────────────
        const symbol     = '{{ $symbol }}';
        const chartId    = `stock-chart-${symbol}`;
        const volId      = `volume-chart-${symbol}`;

        const container    = document.getElementById(chartId);
        const volContainer = document.getElementById(volId);
        if (!container || !volContainer) return;

        const initialData = JSON.parse(container.dataset.candles || '[]');

        const chart = LightweightCharts.createChart(container, {
            layout: { background: { type: 'solid', color: '#ffffff' }, textColor: '#1f2937' },
            grid: { vertLines: { color: '#f3f4f6' }, horzLines: { color: '#f3f4f6' } },
            crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
            rightPriceScale: { borderColor: '#e5e7eb' },
            timeScale: { borderColor: '#e5e7eb', timeVisible: true, secondsVisible: false },
            width: container.clientWidth,
            height: 500,
        });

        const candleSeries = chart.addSeries(LightweightCharts.CandlestickSeries, {
            upColor: '#10b981', downColor: '#ef4444',
            borderVisible: false, wickUpColor: '#10b981', wickDownColor: '#ef4444',
        });

        const volumeChart = LightweightCharts.createChart(volContainer, {
            layout: { background: { type: 'solid', color: '#ffffff' }, textColor: '#1f2937' },
            grid: { vertLines: { color: 'transparent' }, horzLines: { color: '#f3f4f6' } },
            rightPriceScale: { borderColor: '#e5e7eb', scaleMargins: { top: 0.1, bottom: 0 } },
            timeScale: { visible: false },
            width: volContainer.clientWidth,
            height: 120,
        });

        const volumeSeries = volumeChart.addSeries(LightweightCharts.HistogramSeries, {
            color: '#6366f1',
            priceFormat: { type: 'volume' },
        });

        if (initialData.length) {
            candleSeries.setData(initialData);
            volumeSeries.setData(initialData.map(d => ({ time: d.time, value: d.volume ?? 0 })));
            chart.timeScale().fitContent();
        }

        // Live price polling — stuurt event naar tradePanel
        let lastKnownPrice = initialData.length ? initialData[initialData.length - 1].close : 0;

        const fetchLatestBar = async () => {
            try {
                const res = await fetch(`/markets/${symbol}/bars/latest`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();
                const price = parseFloat(data.price ?? data.c ?? 0);
                if (price > 0 && price !== lastKnownPrice) {
                    lastKnownPrice = price;
                    const ts = Math.floor(Date.now() / 1000);
                    candleSeries.update({ time: ts, open: price, high: price, low: price, close: price });
                    window.dispatchEvent(new CustomEvent('live-price-update', { detail: { symbol, price } }));
                }
            } catch (e) { console.warn('Latest bar fetch failed:', e); }
        };

        setInterval(fetchLatestBar, 10000);

        new ResizeObserver(() => {
            chart.applyOptions({ width: container.clientWidth });
            volumeChart.applyOptions({ width: volContainer.clientWidth });
        }).observe(container);
    })();

    // ── Alpine components ─────────────────────────────────────────────────
    document.addEventListener('alpine:init', () => {

        Alpine.data('tradePanel', (symbol, initialPrice) => ({
            symbol,
            side:          'buy',
            quantity:      1,
            price:         parseFloat(initialPrice) || 0,
            livePrice:     parseFloat(initialPrice) || 0,
            walletBalance: 0,
            message:       '',
            isError:       false,
            loading:       false,
            inputMode:     'quantity', 
            amountInput:    '',

            get total() {
                return (parseFloat(this.quantity) || 0) * (parseFloat(this.price) || 0);
            },

            init() {
                this.fetchWallet();
                if (this.livePrice > 0) 
                this.price = this.livePrice;
                
                fetch(`/markets/${this.symbol}/bars/latest`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(r =? r.ok ? r.json() : null)
                  .then(data => {
                      const price = parseFloat(data?.price ?? data?.c ?? 0);
                      if (price > 0) {
                          this.livePrice = price;
                          this.price = price;
                      }
                  }).catch(e => console.warn('Initial live price fetch failed:', e));

                window.addEventListener('live-price-update', (e) => {
                    if (e.detail.symbol === this.symbol) {
                        this.livePrice = e.detail.price;
                        if (Math.abs(this.price - this.livePrice) < 0.01 || this.price === 0) {
                            this.price = this.livePrice;
                        }
                    }
                });
            },

            increment() { const step = this.isCrypto() ? 0.001 : 1;
                const decimals = this.isCrypto() ? 6 : 0;
                this.quantity = parseFloat(((parseFloat(this.quantity) || 0) + step).toFixed(decimals))
            },
            decrement() {
                const cur = parseFloat(this.quantity) || 1;
                this.quantity = Math.max(this.isCrypto() ? 0.000001 : 1, cur - 1);
            },

            isCrypto() {
                return ['BTC','ETH','SOL','DOGE','XRP','ADA','LINK','DOT','MATIC','LTC','AVAX']
                    .includes(this.symbol.toUpperCase());
            },

            async fetchWallet() {
                try {
                    const el = document.querySelector('[data-wallet-balance]');
                    if (el) this.walletBalance = parseFloat(el.dataset.walletBalance) || 0;
                } catch (e) { console.warn('Wallet fetch failed:', e); }
            },

            setByPercent(pct) {
                if (this.price <= 0 || this.walletBalance <= 0) return;
                const rawQty = (this.walletBalance * (pct / 100)) / this.price;
                this.quantity = this.isCrypto() ? parseFloat(rawQty.toFixed(6)) : Math.floor(rawQty);
                this.amountInput = (this.quantity * this.price).toFixed(2);
            },

            async submitOrder() {
                if (this.loading || this.quantity <= 0 || this.price <= 0) return;
                this.loading = true;
                this.message = '';
                const endpoint = this.side === 'buy' ? '/trades/buy' : '/trades/sell';
                const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                try {
                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                                   'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ symbol: this.symbol, quantity: this.quantity,
                                              price: this.price, asset_type: this.isCrypto() ? 'crypto' : 'stock' }),
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.message = data.message ?? '✅ Order uitgevoerd!';
                        this.isError = false;
                        this.quantity = 1;
                        this.price = this.livePrice;
                        if (data.wallet_balance !== undefined) this.walletBalance = parseFloat(data.wallet_balance);
                        window.dispatchEvent(new CustomEvent('trade-executed'));
                    } else {
                        this.message = data.message ?? 'Er ging iets mis.';
                        this.isError = true;
                    }
                } catch (e) {
                    this.message = 'Netwerkfout: ' + e.message;
                    this.isError = true;
                } finally {
                    this.loading = false;
                    setTimeout(() => { this.message = ''; }, 5000);
                }
            },
        }));

        Alpine.data('portfolio', () => ({
            positions: [], livePrices: {}, loading: false, pollTimer: null,

            init() {
                this.load();
                this.pollTimer = setInterval(() => this.fetchLivePrices(), 15000);
                window.addEventListener('trade-executed', () => this.load());
                window.addEventListener('live-price-update', (e) => {
                    if (e.detail.price > 0) this.livePrices[e.detail.symbol] = e.detail.price;
                });
            },

            destroy() { if (this.pollTimer) clearInterval(this.pollTimer); },

            async load() {
                this.loading = true;
                try {
                    const res = await fetch('/portfolio', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.ok) { this.positions = await res.json(); await this.fetchLivePrices(); }
                } catch (e) { console.error('Portfolio load error:', e); }
                finally { this.loading = false; }
            },

            async fetchLivePrices() {
                for (const pos of this.positions) {
                    try {
                        const res = await fetch(`/markets/${pos.symbol}/bars/latest`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            const price = parseFloat(data.price ?? data.c ?? 0);
                            if (price > 0) this.livePrices[pos.symbol] = price;
                        }
                    } catch (e) { console.warn('Live price failed for', pos.symbol); }
                }
            },
        }));
    });
    @endscript
</div>
