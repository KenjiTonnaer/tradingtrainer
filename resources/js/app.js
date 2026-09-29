import { createChart } from 'lightweight-charts';
// Expose LightweightCharts globally for inline scripts in Blade views
import * as LightweightChartsAll from 'lightweight-charts';
if (typeof window !== 'undefined') {
	window.LightweightCharts = LightweightChartsAll;
}

const initializeStockCharts = () => {
	document.querySelectorAll('[id^="stock-chart-"]').forEach((container) => {
		const symbol = container.dataset.symbol;
		const volumeContainer = document.getElementById(`volume-chart-${symbol}`);
		if (!symbol || !volumeContainer) return;
		if (container._stockChartData === container.dataset.candles) return;
		container._stockChart?.remove();
		volumeContainer._stockChart?.remove();

		let initialData = [];
		try {
			initialData = JSON.parse(container.dataset.candles || '[]');
		} catch (error) {
			console.warn('Chart data could not be parsed:', error);
			return;
		}
		const timeframe = container.dataset.timeframe || '1D';
		const isIntraday = ['1m', '5m', '15m', '30m', '1h', '6h', '12h', '1D'].includes(timeframe);
		const formatChartTime = (time) => {
			const date = new Date(Number(time) * 1000);
			return isIntraday
				? date.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })
				: date.toLocaleDateString('nl-NL', { day: '2-digit', month: 'short' });
		};

		const chart = createChart(container, {
			layout: { background: { type: 'solid', color: '#ffffff' }, textColor: '#1f2937' },
			grid: { vertLines: { color: '#f3f4f6' }, horzLines: { color: '#f3f4f6' } },
			rightPriceScale: { borderColor: '#e5e7eb' },
			localization: { timeFormatter: formatChartTime },
			timeScale: {
				borderColor: '#e5e7eb',
				timeVisible: isIntraday,
				secondsVisible: false,
				tickMarkFormatter: formatChartTime,
			},
			width: container.clientWidth,
			height: 500,
		});
		const candleSeries = chart.addSeries(LightweightChartsAll.CandlestickSeries, {
			upColor: '#10b981', downColor: '#ef4444', borderVisible: false,
			wickUpColor: '#10b981', wickDownColor: '#ef4444',
		});

		const volumeChart = createChart(volumeContainer, {
			layout: { background: { type: 'solid', color: '#ffffff' }, textColor: '#1f2937' },
			grid: { vertLines: { color: 'transparent' }, horzLines: { color: '#f3f4f6' } },
			rightPriceScale: { borderColor: '#e5e7eb', scaleMargins: { top: 0.1, bottom: 0 } },
			timeScale: { visible: false },
			width: volumeContainer.clientWidth,
			height: 120,
		});
		const volumeSeries = volumeChart.addSeries(LightweightChartsAll.HistogramSeries, {
			color: '#6366f1', priceFormat: { type: 'volume' },
		});
		container._stockChart = chart;
		container._stockChartData = container.dataset.candles;
		volumeContainer._stockChart = volumeChart;

		if (initialData.length) {
			candleSeries.setData(initialData);
			volumeSeries.setData(initialData.map((item) => ({ time: item.time, value: item.volume ?? 0 })));
			chart.timeScale().fitContent();
		}

		let lastKnownPrice = initialData.length ? initialData[initialData.length - 1].close : 0;
		const pollLatestPrice = async () => {
			try {
				const response = await fetch(`/markets/${symbol}/bars/latest`, {
					headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				});
				if (!response.ok) return;
				const data = await response.json();
				const price = parseFloat(data.price ?? data.c ?? data.close ?? 0);
				if (price > 0 && price !== lastKnownPrice) {
					lastKnownPrice = price;
					const time = Math.floor(Date.now() / 1000);
					candleSeries.update({ time, open: price, high: price, low: price, close: price });
					window.dispatchEvent(new CustomEvent('live-price-update', { detail: { symbol, price } }));
				}
			} catch (error) {
				console.warn('Latest chart price failed:', error);
			}
		};

		setInterval(pollLatestPrice, 10000);
		new ResizeObserver(() => {
			chart.applyOptions({ width: container.clientWidth });
			volumeChart.applyOptions({ width: volumeContainer.clientWidth });
		}).observe(container);
	});
};

const registerTradingComponents = () => {
	if (!window.Alpine) return;

	window.Alpine.data('tradePanel', (symbol, initialPrice) => ({
		symbol,
		side: 'buy',
		quantity: 1,
		price: parseFloat(initialPrice) || 0,
		livePrice: parseFloat(initialPrice) || 0,
		walletBalance: 0,
		message: '',
		isError: false,
		loading: false,
		inputMode: 'quantity',
		amountInput: '',

		get total() {
			return (parseFloat(this.quantity) || 0) * (parseFloat(this.price) || 0);
		},

		init() {
			this.fetchWallet();
			this.loadLatestPrice();
			window.addEventListener('live-price-update', (event) => {
				if (event.detail.symbol !== this.symbol) return;
				this.livePrice = event.detail.price;
				if (Math.abs(this.price - this.livePrice) < 0.01 || this.price === 0) {
					this.price = this.livePrice;
				}
			});
		},

		async loadLatestPrice() {
			try {
				const response = await fetch(`/markets/${this.symbol}/bars/latest`, {
					headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				});
				if (!response.ok) return;
				const data = await response.json();
				const latestPrice = parseFloat(data.price ?? data.c ?? data.close ?? 0);
				if (latestPrice > 0) {
					this.livePrice = latestPrice;
					this.price = latestPrice;
				}
			} catch (error) {
				console.warn('Initial live price fetch failed:', error);
			}
		},

		increment() {
			const step = this.isCrypto() ? 0.001 : 1;
			const decimals = this.isCrypto() ? 6 : 0;
			this.quantity = parseFloat(((parseFloat(this.quantity) || 0) + step).toFixed(decimals));
		},

		decrement() {
			const current = parseFloat(this.quantity) || 1;
			this.quantity = Math.max(this.isCrypto() ? 0.000001 : 1, current - 1);
		},

		isCrypto() {
			return ['BTC', 'ETH', 'SOL', 'DOGE', 'XRP', 'ADA', 'LINK', 'DOT', 'MATIC', 'LTC', 'AVAX']
				.includes(this.symbol.toUpperCase());
		},

		fetchWallet() {
			const element = document.querySelector('[data-wallet-balance]');
			if (element) this.walletBalance = parseFloat(element.dataset.walletBalance) || 0;
		},

		setByPercent(percent) {
			if (this.price <= 0 || this.walletBalance <= 0) return;
			const rawQuantity = (this.walletBalance * (percent / 100)) / this.price;
			this.quantity = this.isCrypto() ? parseFloat(rawQuantity.toFixed(6)) : Math.floor(rawQuantity);
			this.amountInput = (this.quantity * this.price).toFixed(2);
		},

		async submitOrder() {
			if (this.loading || this.quantity <= 0 || this.price <= 0) return;
			this.loading = true;
			this.message = '';
			const endpoint = this.side === 'buy' ? '/trades/buy' : '/trades/sell';
			try {
				const response = await fetch(endpoint, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						Accept: 'application/json',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
						'X-Requested-With': 'XMLHttpRequest',
					},
					body: JSON.stringify({
						symbol: this.symbol,
						quantity: this.quantity,
						price: this.price,
						asset_type: this.isCrypto() ? 'crypto' : 'stock',
					}),
				});
				const data = await response.json();
				this.message = data.message ?? (response.ok ? 'Order uitgevoerd.' : 'Er ging iets mis.');
				this.isError = !response.ok || !data.success;
				if (!this.isError) {
					this.quantity = 1;
					this.price = this.livePrice;
					if (data.wallet_balance !== undefined) this.walletBalance = parseFloat(data.wallet_balance);
					window.dispatchEvent(new CustomEvent('trade-executed'));
				}
			} catch (error) {
				this.message = `Netwerkfout: ${error.message}`;
				this.isError = true;
			} finally {
				this.loading = false;
				setTimeout(() => { this.message = ''; }, 5000);
			}
		},
	}));

	window.Alpine.data('portfolio', () => ({
		positions: [],
		livePrices: {},
		loading: false,
		pollTimer: null,

		init() {
			this.load();
			this.pollTimer = setInterval(() => this.fetchLivePrices(), 15000);
			window.addEventListener('trade-executed', () => this.load());
			window.addEventListener('live-price-update', (event) => {
				if (event.detail.price > 0) this.livePrices[event.detail.symbol] = event.detail.price;
			});
		},

		destroy() {
			if (this.pollTimer) clearInterval(this.pollTimer);
		},

		async load() {
			this.loading = true;
			try {
				const response = await fetch('/portfolio', {
					headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				});
				if (response.ok) {
					this.positions = await response.json();
					await this.fetchLivePrices();
				}
			} catch (error) {
				console.error('Portfolio load error:', error);
			} finally {
				this.loading = false;
			}
		},

		async fetchLivePrices() {
			for (const position of this.positions) {
				try {
					const response = await fetch(`/markets/${position.symbol}/bars/latest`, {
						headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
					});
					if (response.ok) {
						const data = await response.json();
						const price = parseFloat(data.price ?? data.c ?? data.close ?? 0);
						if (price > 0) this.livePrices[position.symbol] = price;
					}
				} catch (error) {
					console.warn('Live price failed for', position.symbol);
				}
			}
		},
	}));
};

document.addEventListener('alpine:init', registerTradingComponents);
if (window.Alpine) registerTradingComponents();

document.addEventListener('DOMContentLoaded', () => {
	const el = document.getElementById('market-chart');
	if (!el) return;

	const symbol = el.dataset.symbol || 'AAPL';
	const chart = createChart(el, { width: el.clientWidth || 800, height: 400, layout: { background: { type: 'solid', color: '#0f172a' }, textColor: '#fff' }, grid: { vertLines: { color: '#1e293b' }, horzLines: { color: '#1e293b' } } });
	const lineSeries = chart.addSeries(LightweightChartsAll.LineSeries, { color: '#10b981', lineWidth: 2 });

	const fetchSeries = async () => {
		try {
			const res = await fetch(`/api/markets/${symbol}/prices`);
			if (!res.ok) return;
			const json = await res.json();
			if (Array.isArray(json.data)) {
				lineSeries.setData(json.data);
			}
		} catch (e) {
			console.error('Initial series fetch failed', e);
		}
	};

	const pollLast = async () => {
		try {
			const res = await fetch(`/api/markets/${symbol}/prices`);
			if (!res.ok) return;
			const json = await res.json();
			if (json.last) {
				lineSeries.update(json.last);
			}
		} catch (e) {
			console.error('Polling failed', e);
		}
	};

	fetchSeries();
	setInterval(pollLast, 3000);
});

document.addEventListener('DOMContentLoaded', initializeStockCharts);
document.addEventListener('livewire:navigated', initializeStockCharts);
document.addEventListener('livewire:morphed', initializeStockCharts);

document.addEventListener('DOMContentLoaded', () => {
	if (window.Livewire) {
		window.Livewire.hook('morphed', () => requestAnimationFrame(initializeStockCharts));
	}
});

