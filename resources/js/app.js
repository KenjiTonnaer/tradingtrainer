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

		const chart = createChart(container, {
			layout: { background: { type: 'solid', color: '#ffffff' }, textColor: '#1f2937' },
			grid: { vertLines: { color: '#f3f4f6' }, horzLines: { color: '#f3f4f6' } },
			rightPriceScale: { borderColor: '#e5e7eb' },
			timeScale: { borderColor: '#e5e7eb', timeVisible: true, secondsVisible: false },
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

