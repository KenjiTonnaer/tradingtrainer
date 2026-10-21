import { createChart, CandlestickSeries, LineSeries, HistogramSeries, ColorType } from 'lightweight-charts';

const money = value => new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 }).format(Number(value || 0));
const number = (value, decimals = 2) => Number(value || 0).toLocaleString('nl-NL', { maximumFractionDigits: decimals });
const statusLabel = status => ({live: 'Live', simulated: 'Gesimuleerd', delayed: 'Verouderd', unavailable: 'Niet beschikbaar', historical: 'Historisch'})[status] || 'Koers ophalen…';
const uuid = () => {
    if (globalThis.crypto.randomUUID) return globalThis.crypto.randomUUID();
    const bytes = globalThis.crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = bytes[6] & 15 | 64; bytes[8] = bytes[8] & 63 | 128;
    const hex = [...bytes].map(b => b.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
};
const readStored = (key, fallback) => { try { return JSON.parse(localStorage.getItem(key)) ?? fallback; } catch { return fallback; } };
const writeStored = (key, value) => { try { localStorage.setItem(key, JSON.stringify(value)); } catch {} };
async function api(url, method = 'GET', data) {
    const response = await fetch(url, { method, headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}, ...(data ? {body: JSON.stringify(data)} : {}) });
    if (response.status === 401 || response.status === 419) throw Error('Je sessie is verlopen. Vernieuw de pagina en log opnieuw in.');
    const body = await response.json();
    if (!response.ok) throw Error(Object.values(body.errors || {}).flat()[0] || body.message || 'Dit verzoek kon niet worden verwerkt.');
    return body;
}

const charts = new Map();
const chartOptions = element => ({
    width: element.clientWidth, height: element.classList.contains('equity-chart') ? 240 : 400,
    layout: {background: {type: ColorType.Solid, color: 'transparent'}, textColor: document.documentElement.classList.contains('dark') ? '#b9b5af' : '#65615c', fontFamily: 'IBM Plex Sans, sans-serif', fontSize: 13, attributionLogo: true},
    grid: {vertLines: {color: document.documentElement.classList.contains('dark') ? '#363636' : '#e9e5db'}, horzLines: {color: document.documentElement.classList.contains('dark') ? '#363636' : '#e9e5db'}},
    rightPriceScale: {borderVisible: false}, timeScale: {borderVisible: false, timeVisible: true, secondsVisible: false},
    localization: {locale: 'nl-NL', priceFormatter: value => money(value)},
});
function destroyChart(element) {
    const existing = charts.get(element);
    if (!existing) return;
    existing.observer.disconnect();
    if (existing.quoteListener) element.removeEventListener('quote-update', existing.quoteListener);
    existing.chart.remove(); charts.delete(element);
}
function drawChart(element, candles, type = 'candles') {
    const signature = JSON.stringify(candles) + type;
    if (charts.get(element)?.signature === signature) return;
    destroyChart(element);
    const chart = createChart(element, chartOptions(element));
    const bullishColor = document.documentElement.classList.contains('dark') ? '#e3ddd3' : '#8f5737';
    const series = type === 'candles' ? chart.addSeries(CandlestickSeries, {upColor: bullishColor, downColor: '#c76b70', wickUpColor: bullishColor, wickDownColor: '#c76b70', borderVisible: false}) : chart.addSeries(LineSeries, {color: '#ce793f', lineWidth: 2, priceLineVisible: false});
    if (candles.length) series.setData(type === 'candles' ? candles : candles.map(c => ({time: c.time, value: c.close})));
    if (candles.some(c => c.volume > 0)) {
        const volume = chart.addSeries(HistogramSeries, {priceScaleId: 'volume', priceFormat: {type: 'volume'}, priceLineVisible: false, lastValueVisible: false});
        volume.priceScale().applyOptions({scaleMargins: {top: .85, bottom: 0}, visible: false});
        chart.priceScale('right').applyOptions({scaleMargins: {top: .1, bottom: .2}});
        volume.setData(candles.map(c => ({time: c.time, value: c.volume || 0, color: c.close >= c.open ? `${bullishColor}55` : '#c76b7055'})));
    }
    chart.timeScale().fitContent();
    const observer = new ResizeObserver(() => chart.applyOptions({width: element.clientWidth}));
    observer.observe(element);
    const state = {chart, series, observer, signature};
    if (element.dataset.symbol && candles.length) {
        let last = {...candles[candles.length - 1]};
        state.quoteListener = event => {
            const quote = event.detail;
            if (!quote.tradable || !quote.price || !quote.as_of) return;
            const step = { '1h': 60, '1D': 900, '30D': 86400, '6M': 86400, '1Y': 86400 }[element.dataset.range] || 900;
            const time = Math.floor(new Date(quote.as_of).getTime() / 1000 / step) * step;
            if (time < last.time) return;
            last = time === last.time ? {...last, close: quote.price, high: Math.max(last.high, quote.price), low: Math.min(last.low, quote.price)} : {time, open: quote.price, high: quote.price, low: quote.price, close: quote.price};
            series.update(type === 'candles' ? last : {time, value: quote.price});
        };
        element.addEventListener('quote-update', state.quoteListener);
    }
    charts.set(element, state);
}
function drawEquity(element, points) {
    if (!element) return;
    drawChart(element, points.map(p => ({time: p.date, close: Number(p.equity)})), 'line');
}
function initializeCharts() {
    for (const element of charts.keys()) if (!element.isConnected) destroyChart(element);
    document.querySelectorAll('[data-price-chart]').forEach(element => {
        try { drawChart(element, JSON.parse(element.dataset.candles || '[]'), readStored('tt.chartType', 'candles')); } catch (e) { console.warn('Grafiek kon niet laden', e); }
    });
    document.querySelectorAll('[data-equity-chart]').forEach(element => {
        if (!charts.has(element)) drawEquity(element, JSON.parse(element.dataset.points || '[]'));
    });
}

function registerComponents() {
    const Alpine = window.Alpine;
    if (!Alpine || window.ttComponentsRegistered) return;
    window.ttComponentsRegistered = true;
    Alpine.data('landingDemo', (candles) => ({
        candles, cursor: Math.min(17, candles.length - 1), chartType: 'candles', playing: false, timer: null,
        money,
        get current() { return this.candles[this.cursor]; },
        get linePath() {
            return this.candles.slice(0, this.cursor + 1).map((candle, index) => `${index === 0 ? 'M' : 'L'}${45 + index * 24},${320 - (candle.close - 168) * 10}`).join(' ');
        },
        get areaPath() { return `${this.linePath} L${45 + this.cursor * 24},340 L45,340 Z`; },
        next() {
            if (this.cursor < this.candles.length - 1) this.cursor++;
            if (this.cursor === this.candles.length - 1) this.pause();
        },
        pause() { clearInterval(this.timer); this.timer = null; this.playing = false; },
        togglePlay() {
            if (this.playing) { this.pause(); return; }
            if (this.cursor === this.candles.length - 1) this.cursor = Math.min(17, this.candles.length - 1);
            this.playing = true;
            this.timer = setInterval(() => this.next(), 650);
        },
        destroy() { this.pause(); },
    }));
    Alpine.data('tradeDesk', (symbol, quote, balance) => ({
        symbol, quote, walletBalance: balance, side: 'buy', quantity: 1, availableQuantity: 0, position: null,
        strategy: '', reason: '', stopLoss: '', takeProfit: '', protectionStop: '', protectionTake: '', riskPercent: 1,
        loading: false, message: '', error: false, timer: null, refreshing: false, orderKey: uuid(), protectionActive: false,
        chartType: readStored('tt.chartType', 'candles'), watchlist: readStored('tt.watchlist', ['AAPL', 'MSFT', 'BTC']),
        money, number, statusLabel,
        get total() { return Number(this.quantity || 0) * Number(this.quote.price || 0); },
        get validStop() { return Number(this.stopLoss) > 0 && Number(this.stopLoss) < Number(this.quote.price); },
        get risk() { return this.validStop ? (this.quote.price - Number(this.stopLoss)) * Number(this.quantity || 0) : 0; },
        get canSubmit() { return !this.loading && this.quote.tradable && Number(this.quantity) > 0 && this.total >= .01 && (this.side === 'buy' ? this.total <= this.walletBalance : Number(this.quantity) <= this.availableQuantity); },
        init() {
            this.loadPosition();
            this.timer = setInterval(() => this.refreshQuote(), 15000);
            this.themeListener = () => this.$nextTick(initializeCharts);
            window.addEventListener('tt-theme-change', this.themeListener);
        },
        destroy() { clearInterval(this.timer); window.removeEventListener('tt-theme-change', this.themeListener); },
        toggleWatch() {
            this.watchlist = this.watchlist.includes(this.symbol) ? this.watchlist.filter(s => s !== this.symbol) : [...this.watchlist, this.symbol];
            writeStored('tt.watchlist', this.watchlist);
        },
        setChartType(type) {
            this.chartType = type; writeStored('tt.chartType', type);
            this.$root.querySelectorAll('[data-price-chart]').forEach(el => { destroyChart(el); });
            initializeCharts();
        },
        async refreshQuote() {
            if (this.refreshing) return;
            this.refreshing = true;
            try {
                this.quote = await api(`/markets/${this.symbol}/bars/latest`);
                this.$root.querySelector('[data-price-chart]')?.dispatchEvent(new CustomEvent('quote-update', {detail: this.quote}));
                await this.loadPosition(false);
            } catch { this.quote = {...this.quote, status: 'unavailable', tradable: false}; }
            finally { this.refreshing = false; }
        },
        async loadPosition(syncProtection = true) {
            try {
                const state = await api('/account/state');
                this.walletBalance = Number(state.cash);
                this.protectionActive = state.protection_active;
                this.position = state.positions.find(p => p.symbol === this.symbol) || null;
                this.availableQuantity = Number(this.position?.quantity || 0);
                if (syncProtection) { this.protectionStop = this.position?.stop_loss || ''; this.protectionTake = this.position?.take_profit || ''; }
            } catch (e) { this.error = true; this.message = e.message; }
        },
        setPercent(pct) {
            const raw = this.side === 'buy' ? this.walletBalance * pct / 100 / Number(this.quote.price || Infinity) : this.availableQuantity * pct / 100;
            this.quantity = Math.floor(raw * 1000000) / 1000000;
        },
        sizeByRisk() {
            if (!this.validStop || this.riskPercent <= 0 || this.riskPercent > 100) return;
            const riskBudget = this.walletBalance * this.riskPercent / 100;
            this.quantity = Math.floor(Math.min(riskBudget / (this.quote.price - this.stopLoss), this.walletBalance / this.quote.price) * 1000000) / 1000000;
        },
        async saveProtection() {
            if (!this.position) return;
            this.loading = true;
            try {
                await api(`/portfolio/${this.position.id}/protection`, 'PATCH', {stop_loss: this.protectionStop || null, take_profit: this.protectionTake || null});
                await this.loadPosition(); this.error = false; this.message = 'Bescherming opgeslagen.';
            } catch (e) { this.error = true; this.message = e.message; }
            finally { this.loading = false; }
        },
        async submitOrder() {
            if (!this.canSubmit) return;
            this.loading = true; this.message = '';
            try {
                const data = await api(`/trades/${this.side}`, 'POST', {symbol: this.symbol, quantity: Number(this.quantity), order_key: this.orderKey,
                    strategy: this.strategy || null, entry_reason: this.reason || null,
                    stop_loss: this.side === 'buy' && this.stopLoss ? Number(this.stopLoss) : null,
                    take_profit: this.side === 'buy' && this.takeProfit ? Number(this.takeProfit) : null});
                this.orderKey = uuid(); this.walletBalance = Number(data.wallet_balance);
                this.error = false; this.message = data.message + ' Uitvoering: ' + money(data.price);
                await this.loadPosition(); await this.refreshQuote();
            } catch (e) { this.error = true; this.message = e.message; }
            finally { this.loading = false; }
        },
    }));
    Alpine.data('accountOverview', (positions, cash, history) => ({
        positions, cash, history, quotes: {}, search: '', asset: 'all', sort: 'symbol', timer: null, refreshing: false,
        snapshotMessage: 'De grafiek bouwt vanaf nu op met accountwaarderingen. Historische punten worden niet verzonnen.', money, number, statusLabel,
        get complete() { return this.positions.every(p => this.quotes[p.symbol]?.tradable && this.quotes[p.symbol]?.price > 0); },
        value(p) { const q = this.quotes[p.symbol]; return q?.tradable && q.price > 0 ? Number(p.quantity) * q.price : null; },
        get equity() { return Number(this.cash) + this.positions.reduce((sum, p) => sum + (this.value(p) || 0), 0); },
        get unrealized() { return this.equity - this.cash - this.positions.reduce((sum, p) => sum + Number(p.total_invested), 0); },
        get filtered() {
            return this.positions.filter(p => p.symbol.toLowerCase().includes(this.search.toLowerCase()) && (this.asset === 'all' || p.asset_type === this.asset)).sort((a, b) => this.sort === 'value' ? (this.value(b) || 0) - (this.value(a) || 0) : this.sort === 'pnl' ? ((this.value(b) ?? b.total_invested) - b.total_invested) - ((this.value(a) ?? a.total_invested) - a.total_invested) : a.symbol.localeCompare(b.symbol));
        },
        init() { this.refresh(); this.timer = setInterval(() => this.refresh(), 30000); },
        destroy() { clearInterval(this.timer); },
        async refresh() {
            if (this.refreshing) return;
            this.refreshing = true;
            try {
                const state = await api('/account/state'); this.positions = state.positions; this.cash = Number(state.cash);
                await Promise.all(this.positions.map(async p => {
                    try { this.quotes[p.symbol] = await api(`/markets/${p.symbol}/bars/latest`); }
                    catch { this.quotes[p.symbol] = {status: 'unavailable', tradable: false}; }
                }));
                if (this.complete) {
                    const result = await api('/account/snapshot', 'POST', {});
                    if (result.complete) {
                        this.history = result.history; drawEquity(this.$root.querySelector('[data-equity-chart]'), result.history);
                        this.snapshotMessage = result.history.length < 2 ? 'Eerste dagsnapshot opgeslagen. Vanaf de volgende dag ontstaat je vermogenslijn.' : 'Dagelijkse accountwaarderingen. Simulatiekoersen tellen als oefenwaardering.';
                    } else this.snapshotMessage = result.message;
                } else this.snapshotMessage = 'Geen complete actuele waardering. Er wordt geen misleidend punt aan de grafiek toegevoegd.';
            } catch (e) { this.snapshotMessage = e.message; }
            finally { this.refreshing = false; }
        },
    }));
    Alpine.data('replayStudio', () => ({
        mode: 'scenario', scenario: 'trend', symbol: 'BTC', date: '', session: null,
        orderKey: uuid(),
        quantity: 1, stopLoss: '', takeProfit: '', reason: '', evaluation: '', message: '', busy: false, saved: false,
        money, number, uuid,
        init() { this.themeListener = () => { if (this.session) this.$nextTick(() => this.draw()); }; window.addEventListener('tt-theme-change', this.themeListener); },
        async start() {
            if (this.busy) return;
            this.busy = true; this.message = '';
            try {
                this.session = await api('/replay', 'POST', {mode: this.mode, scenario: this.scenario, ...(this.mode === 'historical' ? {symbol: this.symbol, date: this.date} : {})});
                this.evaluation = ''; this.saved = false; this.reason = ''; this.stopLoss = ''; this.takeProfit = ''; this.orderKey = uuid();
                this.$nextTick(() => this.draw());
            } catch (e) { this.message = e.message; }
            finally { this.busy = false; }
        },
        async resume(id) {
            if (this.busy) return;
            this.busy = true; this.message = '';
            try { this.session = await api(`/replay/${id}`); this.orderKey = uuid(); this.evaluation = this.session.evaluation || ''; this.$nextTick(() => this.draw()); }
            catch (e) { this.message = e.message; }
            finally { this.busy = false; }
        },
        draw() { const el = this.$root.querySelector('[data-replay-chart]'); if (el) drawChart(el, this.session.candles); },
        destroy() { const el = this.$root.querySelector('[data-replay-chart]'); if (el) destroyChart(el); window.removeEventListener('tt-theme-change', this.themeListener); },
        orderInput() { return {quantity: Number(this.quantity), stop_loss: this.stopLoss ? Number(this.stopLoss) : null, take_profit: this.takeProfit ? Number(this.takeProfit) : null, reason: this.reason, request_id: this.orderKey}; },
        sizeByRisk() {
            const stop = Number(this.stopLoss); const price = this.session.price;
            if (stop <= 0 || stop >= price) {this.message = 'Vul een stop onder de huidige koers in.'; return;}
            const maxValue = Math.max(0, this.session.equity * .2 - this.session.quantity * price);
            this.quantity = Math.floor(Math.min(this.session.equity * .01 / (price - stop), maxValue / price, this.session.cash / price) * 1000000) / 1000000;
        },
        async act(action, data = {}) {
            if (this.busy || !this.session) return;
            this.busy = true; this.message = ''; this.saved = false;
            try {
                this.session = await api(`/replay/${this.session.id}`, 'PATCH', {action, cursor: this.session.cursor, ...data});
                if (['buy', 'sell'].includes(action)) this.orderKey = uuid();
                this.evaluation = this.session.evaluation || this.evaluation;
                if (action === 'evaluate') this.saved = true;
                this.$nextTick(() => this.draw());
            } catch (e) {this.message = e.message;}
            finally {this.busy = false;}
        },
    }));
}
document.addEventListener('alpine:init', registerComponents);
registerComponents();
document.addEventListener('DOMContentLoaded', initializeCharts);
document.addEventListener('livewire:navigated', initializeCharts);
document.addEventListener('livewire:navigating', () => {for (const element of [...charts.keys()]) destroyChart(element);});
const setupLivewire = () => {
    if (!window.Livewire || window.ttChartHookRegistered) return;
    window.ttChartHookRegistered = true;
    window.Livewire.hook('morphed', () => requestAnimationFrame(initializeCharts));
};
document.addEventListener('livewire:initialized', setupLivewire);
setupLivewire();
new MutationObserver(() => {
    for (const element of [...charts.keys()]) destroyChart(element);
    initializeCharts();
    window.dispatchEvent(new CustomEvent('tt-theme-change'));
}).observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
