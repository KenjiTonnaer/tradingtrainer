export const resultClass = value => {
    if (value === null || value === undefined || !Number.isFinite(Number(value))) return 'neutral';
    return Number(value) > 0 ? 'positive' : Number(value) < 0 ? 'negative' : 'neutral';
};

export const price = value => value === null || value === undefined || !Number.isFinite(Number(value))
    ? '—'
    : new Intl.NumberFormat('nl-NL', {style: 'currency', currency: 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 4}).format(Number(value));

export const signedMoney = value => value === null || value === undefined || !Number.isFinite(Number(value))
    ? '—'
    : new Intl.NumberFormat('nl-NL', {style: 'currency', currency: 'EUR', signDisplay: 'exceptZero', maximumFractionDigits: 2}).format(Number(value));

export const candleDirection = candle => candle.close > candle.open ? 'up' : candle.close < candle.open ? 'down' : 'flat';

export function candleReadout(candle) {
    if (!candle || !['open', 'high', 'low', 'close'].every(key => Number.isFinite(candle[key]))) return 'Geen candlegegevens beschikbaar';
    const change = candle.open > 0 ? (candle.close - candle.open) / candle.open * 100 : null;
    const percent = change === null ? '—' : new Intl.NumberFormat('nl-NL', {maximumFractionDigits: 2, signDisplay: 'exceptZero'}).format(change) + '%';
    const volume = Number.isFinite(candle.volume) && candle.volume > 0
        ? ` · Volume ${new Intl.NumberFormat('nl-NL', {maximumFractionDigits: 2}).format(candle.volume)}` : '';
    return `Open ${price(candle.open)} · Hoog ${price(candle.high)} · Laag ${price(candle.low)} · Slot ${price(candle.close)} · ${percent}${volume}`;
}
