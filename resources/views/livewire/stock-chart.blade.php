<div class="w-full">
    <!-- Welcome Header - Dashboard Style -->
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

    <!-- Header met symbool en huidige prijs - Dashboard Style -->
    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
            <div class="flex items-center gap-4">
                <!-- Symbol Selector - Stocks or Crypto based on current symbol -->
                @php
                    $cryptoSymbols = ['BTC', 'ETH', 'SOL', 'DOGE', 'XRP', 'ADA', 'LINK', 'DOT', 'MATIC', 'LTC', 'AVAX'];
                    $isCrypto = in_array($symbol, $cryptoSymbols);
                @endphp

                @if($isCrypto)
                    <!-- Crypto Selector -->
                    <select wire:model.live="symbol" class="px-4 py-2 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-lg text-gray-900 font-bold text-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <optgroup label="💰 Crypto (24/7 Live via CoinGecko)">
                            <option value="BTC">₿ Bitcoin</option>
                            <option value="ETH">Ξ Ethereum</option>
                            <option value="SOL">◎ Solana</option>
                            <option value="DOGE">Ð Dogecoin</option>
                            <option value="XRP">✕ Ripple</option>
                            <option value="ADA">₳ Cardano</option>
                            <option value="LINK">🔗 Chainlink</option>
                            <option value="DOT">● Polkadot</option>
                            <option value="MATIC">⬡ Polygon</option>
                            <option value="LTC">Ł Litecoin</option>
                            <option value="AVAX">🔺 Avalanche</option>
                        </optgroup>
                    </select>
                @else
                    <!-- Stocks Selector -->
                    <select wire:model.live="symbol" class="px-4 py-2 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-lg text-gray-900 font-bold text-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <optgroup label="🇺🇸 US Stocks (9:30–16:00 EST)">
                            <option value="AAPL">AAPL - Apple Inc.</option>
                            <option value="TSLA">TSLA - Tesla</option>
                            <option value="MSFT">MSFT - Microsoft</option>
                            <option value="GOOGL">GOOGL - Alphabet (Google)</option>
                            <option value="AMZN">AMZN - Amazon</option>
                            <option value="META">META - Meta Platforms</option>
                            <option value="NVDA">NVDA - NVIDIA</option>
                            <option value="AMD">AMD - Advanced Micro Devices</option>
                            <option value="NFLX">NFLX - Netflix</option>
                            <option value="DIS">DIS - Disney</option>
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

            <!-- WebSocket Status Indicator -->
            <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg border border-gray-200">
                <span class="inline-block w-2 h-2 rounded-full bg-gray-400" id="ws-indicator-{{ $symbol }}"></span>
                <span id="ws-text-{{ $symbol }}" class="text-sm font-medium text-gray-700">Connecting...</span>
            </div>
        </div>

        <!-- Market Hours Warning for Stocks -->
        @if(!$isCrypto)
            @php
                // Check if US market is open (NYSE: 9:30 AM - 4:00 PM ET, Mon-Fri)
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
                                Live data is only available Mon-Fri, 9:30 AM - 4:00 PM ET.
                                @if($isWeekday)
                                    Market opens at 9:30 AM ET.
                                @else
                                    Market opens Monday at 9:30 AM ET.
                                @endif
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

    <!-- Quick Stats - Dashboard Style -->
    <div class="grid gap-4 md:grid-cols-4 mb-6">
        <div class="group relative overflow-hidden bg-white rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 border border-purple-100">
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

        <div class="group relative overflow-hidden bg-white rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 border border-green-100">
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

        <div class="group relative overflow-hidden bg-white rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 border border-orange-100">
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

        <div class="group relative overflow-hidden bg-white rounded-xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 border border-blue-100">
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

    <!-- Chart containers - Dashboard Style -->
    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200 mb-4">
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
    <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200 mb-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Volume</h3>
        <div
            id="volume-chart-{{ $symbol }}"
            class="w-full rounded-lg"
            style="height: 120px;"
        ></div>
    </div>

    <!-- Info panel - Dashboard Style -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-200">
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
                        <span class="text-orange-600">Simulated data</span> (add valid Alpaca API keys in `.env`)
                    @else
                        <span class="text-green-600">Alpaca Markets</span> (REST + secure server polling)
                    @endif
                </p>
                <p class="text-sm text-gray-600">
                    <strong>Timeframe:</strong> {{ $timeframe }} |
                    <strong>Candles:</strong> {{ count($historicalData) }}
                </p>
            </div>
        </div>
    </div>

    @script
    <script>
        (function() {
            const symbol = '{{ $symbol }}';
            const chartId = `stock-chart-${symbol}`;
            const volumeChartId = `volume-chart-${symbol}`;
            const wsStatusId = `ws-status-${symbol}`;
            const wsIndicatorId = `ws-indicator-${symbol}`;
            const wsTextId = `ws-text-${symbol}`;

            const container = document.getElementById(chartId);
            const volumeContainer = document.getElementById(volumeChartId);

            if (!container || !volumeContainer) return;

            // Parse initial data
            const initialData = JSON.parse(container.dataset.candles || '[]');
            // No secrets in the browser. We will poll a secure backend endpoint instead of a direct WS.

            // Create main chart with light theme to match dashboard
            const chart = LightweightCharts.createChart(container, {
                layout: {
                    background: { color: '#ffffff' },
                    textColor: '#1f2937',
                },
                grid: {
                    vertLines: { color: '#f3f4f6' },
                    horzLines: { color: '#f3f4f6' },
                },
                crosshair: {
                    mode: LightweightCharts.CrosshairMode.Normal,
                },
                rightPriceScale: {
                    borderColor: '#e5e7eb',
                },
                timeScale: {
                    borderColor: '#e5e7eb',
                    timeVisible: true,
                    secondsVisible: false,
                },
                width: container.clientWidth,
                height: 500,
            });

            // Create candlestick series with gradient colors matching dashboard
            const candleSeries = chart.addCandlestickSeries({
                upColor: '#10b981',
                downColor: '#ef4444',
                borderVisible: false,
                wickUpColor: '#10b981',
                wickDownColor: '#ef4444',
            });

            // Set initial data and ensure it's visible
            function applyAllData(data){
                candleSeries.setData(data);
                const volumeData = data.map(candle => ({
                    time: candle.time,
                    value: candle.volume || 0,
                    color: candle.close >= candle.open ? '#10b98180' : '#ef444480',
                }));
                volumeSeries.setData(volumeData);
                try { chart.timeScale().fitContent(); } catch (e) {}
                try { volumeChart.timeScale().fitContent(); } catch (e) {}
            }
            applyAllData(initialData);

            // Create volume chart with light theme
            const volumeChart = LightweightCharts.createChart(volumeContainer, {
                layout: {
                    background: { color: '#ffffff' },
                    textColor: '#1f2937',
                },
                grid: {
                    vertLines: { color: '#f3f4f6' },
                    horzLines: { color: '#f3f4f6' },
                },
                rightPriceScale: {
                    borderColor: '#e5e7eb',
                },
                timeScale: {
                    borderColor: '#e5e7eb',
                    visible: false,
                },
                width: volumeContainer.clientWidth,
                height: 120,
            });

            // Create volume series
            const volumeSeries = volumeChart.addHistogramSeries({
                color: '#3b82f6',
                priceFormat: {
                    type: 'volume',
                },
            });

            // Set volume handled in applyAllData

            // Sync time scales
            chart.timeScale().subscribeVisibleTimeRangeChange(() => {
                const timeRange = chart.timeScale().getVisibleRange();
                if (timeRange) {
                    volumeChart.timeScale().setVisibleRange(timeRange);
                }
            });

            // Handle window resize
            const resizeObserver = new ResizeObserver(entries => {
                if (entries.length === 0) return;
                const newWidth = entries[0].contentRect.width;
                chart.applyOptions({ width: newWidth });
                volumeChart.applyOptions({ width: newWidth });
            });
            resizeObserver.observe(container);

            // WebSocket connection
            let ws = null;
            let wsReconnectAttempts = 0;
            const maxReconnectAttempts = 5;

            function updateWSStatus(status, text) {
                const indicator = document.getElementById(wsIndicatorId);
                const textEl = document.getElementById(wsTextId);

                if (indicator && textEl) {
                    const colors = {
                        connecting: 'bg-yellow-500',
                        connected: 'bg-green-500',
                        disconnected: 'bg-red-500',
                        error: 'bg-red-500',
                    };

                    indicator.className = `inline-block w-2 h-2 rounded-full ${colors[status] || 'bg-gray-500'}`;
                    textEl.textContent = text;
                }
            }

            let pollInterval = null;
            let currentSymbol = symbol;
            let currentTf = container.dataset.timeframe || '1D';

            function rehydrateFromDom(){
                const fresh = document.getElementById(chartId);
                if (!fresh) return;
                try{
                    const data = JSON.parse(fresh.dataset.candles || '[]');
                    if (Array.isArray(data) && data.length){
                        // replace initialData contents
                        initialData.length = 0;
                        data.forEach(d=>initialData.push(d));
                        currentTf = fresh.dataset.timeframe || currentTf;
                        applyAllData(initialData);
                    }
                }catch(e){ console.warn('Rehydrate parse failed', e); }
            }

            function mapTimeframe(tf) {
                switch (tf) {
                    case '1m': return '1Min';
                    case '5m': return '5Min';
                    case '15m': return '15Min';
                    case '30m': return '30Min';
                    case '1h': return '1Hour';
                    case '6h': return '1Hour';
                    case '12h': return '1Hour';
                    case '1D':
                    case '30D':
                    case '6M':
                    case '1Y':
                    case 'ALL':
                        return '1Day';
                    default:
                        return '1Min';
                }
            }

            async function startServerPolling() {
                if (pollInterval) clearInterval(pollInterval);
                updateWSStatus('connecting', 'Connecting...');
                let firstSuccess = false;
                let failureCount = 0;
                const poll = async () => {
                    try {
                        const tfParam = mapTimeframe(currentTf);
                        const url = `/markets/${encodeURIComponent(currentSymbol)}/bars/latest?timeframe=${encodeURIComponent(tfParam)}`;
                        const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                        if (!res.ok) {
                            failureCount++;
                            // On auth/config errors, fall back to simulation quickly
                            if (failureCount >= 3 || [400,401,403,429,502,503].includes(res.status)) {
                                updateWSStatus('disconnected', 'Simulated');
                                if (pollInterval) clearInterval(pollInterval);
                                startSimulatedUpdates();
                            }
                            return;
                        }
                        const bar = await res.json();
                        if (!bar || !bar.time) {
                            // Treat empty/no-data responses as transient failures
                            failureCount++;
                            if (!firstSuccess && failureCount >= 5) {
                                updateWSStatus('disconnected', 'Simulated');
                                if (pollInterval) clearInterval(pollInterval);
                                startSimulatedUpdates();
                            }
                            return;
                        }

                        const lastCandle = initialData[initialData.length - 1];
                        if (lastCandle && bar.time === lastCandle.time) {
                            initialData[initialData.length - 1] = bar;
                        } else {
                            initialData.push(bar);
                        }
                        if (initialData.length === 1) {
                            candleSeries.setData(initialData);
                            volumeSeries.setData([{ time: bar.time, value: bar.volume, color: bar.close >= bar.open ? '#10b98180' : '#ef444480' }]);
                        } else {
                            candleSeries.update(bar);
                            volumeSeries.update({ time: bar.time, value: bar.volume, color: bar.close >= bar.open ? '#10b98180' : '#ef444480' });
                        }

                        if (!firstSuccess) {
                            updateWSStatus('connected', 'Live (secure)');
                            firstSuccess = true;
                            failureCount = 0;
                        }
                    } catch (e) {
                        console.warn('Polling error', e);
                        failureCount++;
                        if (failureCount >= 5) {
                            updateWSStatus('disconnected', 'Simulated');
                            if (pollInterval) clearInterval(pollInterval);
                            startSimulatedUpdates();
                        }
                    }
                };

                // Initial tick and interval
                await poll();
                pollInterval = setInterval(poll, 2000);
            }

            // Simulated updates fallback
            let simulationInterval = null;

            function startSimulatedUpdates() {
                if (simulationInterval) return;

                console.log('🎲 Starting simulated price updates...');
                updateWSStatus('disconnected', 'Simulated');

                simulationInterval = setInterval(() => {
                    const lastCandle = initialData[initialData.length - 1];
                    if (!lastCandle) return;

                    const volatility = 0.002;
                    const change = lastCandle.close * volatility * (Math.random() * 2 - 1);
                    const newPrice = Math.max(0.01, lastCandle.close + change);

                    const newCandle = {
                        time: Math.floor(Date.now() / 1000),
                        open: lastCandle.close,
                        high: Math.max(lastCandle.close, newPrice),
                        low: Math.min(lastCandle.close, newPrice),
                        close: newPrice,
                        volume: Math.floor(Math.random() * 100000),
                    };

                    initialData.push(newCandle);
                    candleSeries.update(newCandle);

                    // Keep only last 1000 candles
                    if (initialData.length > 1000) {
                        initialData.shift();
                    }
                }, 2000);
            }

            // Initialize secure server polling (no secrets in browser)
            startServerPolling();

            // Listen for timeframe changes
            Livewire.on('timeframeChanged', (event) => {
                currentTf = event.timeframe;
                // Let Livewire re-render DOM, then pull fresh dataset
                setTimeout(() => { rehydrateFromDom(); startServerPolling(); }, 0);
            });

            // Listen for symbol changes
            Livewire.on('symbolChanged', (event) => {
                currentSymbol = event.symbol;
                setTimeout(() => { rehydrateFromDom(); startServerPolling(); }, 0);
            });

            // Also update after any Livewire DOM patch (dropdown change etc.)
            document.addEventListener('livewire:navigated', () => {
                rehydrateFromDom();
                startServerPolling();
            });

            // Cleanup on component destroy
            document.addEventListener('livewire:navigating', () => {
                if (ws) try { ws.close(); } catch (e) {}
                if (simulationInterval) clearInterval(simulationInterval);
                if (pollInterval) clearInterval(pollInterval);
                resizeObserver.disconnect();
            });
        })();
    </script>
    @endscript
</div>
