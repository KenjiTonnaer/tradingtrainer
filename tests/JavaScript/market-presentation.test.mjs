import {test} from 'node:test';
import assert from 'node:assert/strict';
import {candleDirection, candleReadout, price, resultClass, signedMoney} from '../../resources/js/market-presentation.js';

test('missing valuations and break-even results have no profit or loss colour', () => {
    for (const value of [null, undefined, NaN, Infinity, 0, '0']) assert.equal(resultClass(value), 'neutral');
    assert.equal(resultClass(-1), 'negative');
    assert.equal(resultClass(1), 'positive');
});

test('candle direction compares its own close with its open, independently of the previous candle', () => {
    assert.equal(candleDirection({open: 101, close: 100}), 'down');
    assert.equal(candleDirection({open: 99, close: 100}), 'up');
    assert.equal(candleDirection({open: 100, close: 100}), 'flat');
});

test('small price moves remain visible and unavailable prices are not shown as zero', () => {
    assert.notEqual(price(.1001), price(.1002));
    assert.match(price(.1001), /0,1001/);
    assert.equal(price(null), '—');
    assert.equal(price(NaN), '—');
    assert.match(signedMoney(10), /\+10/);
    assert.match(signedMoney(-10), /-10/);
    assert.doesNotMatch(signedMoney(0), /[+-]/);
});

test('candle readout exposes OHLC values and movement relative to opening', () => {
    const text = candleReadout({open: 100, high: 103, low: 99, close: 102});
    for (const label of ['Open', 'Hoog', 'Laag', 'Slot', '+2%']) assert.ok(text.includes(label));
    assert.match(candleReadout({open: 100, high: 101, low: 97, close: 98}), /-2%/);
    assert.equal(candleReadout(null), 'Geen candlegegevens beschikbaar');
});

test('volume is shown only when it is present in the candle data', () => {
    const candle = {open: 100, high: 103, low: 99, close: 102};
    assert.doesNotMatch(candleReadout(candle), /Volume/);
    assert.doesNotMatch(candleReadout({...candle, volume: 0}), /Volume/);
    assert.match(candleReadout({...candle, volume: 12000}), /Volume 12\.000/);
});
