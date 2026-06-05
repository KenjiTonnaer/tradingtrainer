import { createChart } from 'lightweight-charts';
// Expose LightweightCharts globally for inline scripts in Blade views
import * as LightweightChartsAll from 'lightweight-charts';
if (typeof window !== 'undefined') {
	window.LightweightCharts = LightweightChartsAll;
}

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

