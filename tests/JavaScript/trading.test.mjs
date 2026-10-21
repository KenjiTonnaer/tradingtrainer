import { test } from 'node:test';
import assert from 'node:assert/strict';

// Exercise the real Alpine component factories without operating a desktop browser.
const factories = new Map();
globalThis.window = Object.assign(new EventTarget(), {Alpine: {data(name, factory) {factories.set(name, factory);}}});
globalThis.document = {addEventListener() {}, documentElement: {classList: {contains() {return false;}}}, querySelector() {return {content: 'test-csrf'};}};
globalThis.MutationObserver = class {observe() {}};
await import('../../resources/js/app.js');

const response = body => new Response(JSON.stringify(body), {headers: {'Content-Type': 'application/json'}});
const root = {querySelector() {return null;}, querySelectorAll() {return [];}};
const component = (name, ...args) => Object.assign(factories.get(name)(...args), {$root: root, $nextTick(fn) {fn();}});
const quote = {price: 100, tradable: true, status: 'simulated', as_of: new Date().toISOString()};

test('sidebar preference survives navigation while the mobile menu starts closed', () => {
    const previousStorage = globalThis.localStorage;
    const values = new Map();
    globalThis.localStorage = {getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value)};
    try {
        const shell = component('workspaceShell');
        assert.equal(shell.sidebarCollapsed, false);
        shell.toggleSidebar();
        const nextPage = component('workspaceShell');
        assert.equal(nextPage.sidebarCollapsed, true);
        assert.equal(nextPage.menuOpen, false);
        nextPage.toggleSidebar();
        assert.equal(component('workspaceShell').sidebarCollapsed, false);
    } finally {
        if (previousStorage === undefined) delete globalThis.localStorage;
        else globalThis.localStorage = previousStorage;
    }
});

test('sidebar remains usable when browser storage is unavailable', () => {
    const previousStorage = globalThis.localStorage;
    globalThis.localStorage = {getItem() {throw Error('Storage blocked');}, setItem() {throw Error('Storage blocked');}};
    try {
        const shell = component('workspaceShell');
        assert.equal(shell.sidebarCollapsed, false);
        shell.toggleSidebar();
        assert.equal(shell.sidebarCollapsed, true);
        shell.toggleSidebar();
        assert.equal(shell.sidebarCollapsed, false);
    } finally {
        if (previousStorage === undefined) delete globalThis.localStorage;
        else globalThis.localStorage = previousStorage;
    }
});

test('public demo advances only within its fictional data without submitting requests', () => {
    const candles = Array.from({length: 20}, (_, index) => ({open: 170, close: 170 + index, high: 191, low: 169}));
    const demo = component('landingDemo', candles);
    const oldFetch = globalThis.fetch;
    globalThis.fetch = () => {throw Error('A public illustration must not send orders');};
    try {
        assert.equal(demo.cursor, 17);
        assert.equal(demo.current.close, 187);
        assert.equal((demo.linePath.match(/[ML]/g) || []).length, 18);
        demo.next(); demo.next(); demo.next();
        assert.equal(demo.cursor, 19);
        assert.equal(demo.current.close, 189);
        assert.equal((demo.linePath.match(/[ML]/g) || []).length, 20);
        assert.equal(demo.playing, false);
    } finally { globalThis.fetch = oldFetch; }
});

test('public demo playback stops on completion and releases its timer', () => {
    const candles = Array.from({length: 20}, (_, index) => ({close: 170 + index}));
    const demo = component('landingDemo', candles);
    const oldSetInterval = globalThis.setInterval;
    const oldClearInterval = globalThis.clearInterval;
    let tick, cleared = false;
    globalThis.setInterval = callback => {tick = callback; return 42;};
    globalThis.clearInterval = id => {if (id === 42) cleared = true;};
    try {
        demo.togglePlay(); assert.equal(demo.playing, true);
        tick(); tick();
        assert.equal(demo.playing, false);
        assert.equal(cleared, true);
        demo.togglePlay(); assert.equal(demo.cursor, 17);
        demo.destroy(); assert.equal(demo.timer, null);
    } finally { globalThis.setInterval = oldSetInterval; globalThis.clearInterval = oldClearInterval; }
});

test('risk sizing respects both available cash and the stop distance', () => {
    const desk = component('tradeDesk', 'AAPL', quote, 1000);
    desk.stopLoss = 95; desk.riskPercent = 1; desk.sizeByRisk();
    assert.equal(desk.quantity, 2);
    assert.equal(desk.risk, 10);
    desk.riskPercent = 100; desk.sizeByRisk();
    assert.equal(desk.quantity, 10);
});

test('ambiguous order failures keep the same order key until a confirmed fill', async () => {
    const desk = component('tradeDesk', 'AAPL', quote, 1000);
    const key = desk.orderKey;
    const attempts = [];
    globalThis.fetch = async (url, options) => {
        if (url === '/trades/buy') {
            attempts.push(JSON.parse(options.body));
            if (attempts.length === 1) throw Error('Connection lost');
            return response({success: true, wallet_balance: 900, price: 100, message: 'Filled'});
        }
        if (url === '/account/state') return response({cash: 900, positions: [], protection_active: true});
        return response(quote);
    };
    await desk.submitOrder();
    assert.equal(desk.orderKey, key);
    await desk.submitOrder();
    assert.equal(attempts[0].order_key, attempts[1].order_key);
    assert.equal(attempts[0].price, undefined);
    assert.notEqual(desk.orderKey, key);
    assert.equal(desk.walletBalance, 900);
});

test('missing and delayed prices never produce a usable portfolio valuation', () => {
    const positions = [{id: 1, symbol: 'AAPL', quantity: 2, total_invested: 150, asset_type: 'stock'}];
    const account = component('accountOverview', positions, 1000, []);
    assert.equal(account.complete, false);
    account.refreshing = true;
    assert.equal(account.valuationLabel, 'Koersgegevens ophalen…');
    account.refreshing = false;
    assert.equal(account.value(positions[0]), null);
    account.quotes.AAPL = {price: 100, status: 'delayed', tradable: false};
    assert.equal(account.complete, false);
    assert.match(account.valuationLabel, /Onvolledige waardering/);
    account.quotes.AAPL = quote;
    assert.equal(account.complete, true);
    assert.equal(account.valuationLabel, 'Saldo + actuele positiewaarde');
    assert.equal(account.equity, 1200);
    assert.equal(account.unrealized, 50);
});

test('background account refresh keeps an unfinished protection edit intact', async () => {
    const desk = component('tradeDesk', 'AAPL', quote, 1000);
    desk.protectionStop = '91';
    globalThis.fetch = async () => response({cash: 700, positions: [{symbol: 'AAPL', quantity: 3, stop_loss: 80}], protection_active: true});
    await desk.loadPosition(false);
    assert.equal(desk.protectionStop, '91');
    assert.equal(desk.availableQuantity, 3);
    assert.equal(desk.walletBalance, 700);
});

test('replay actions carry the current cursor and preserve an uncertain order key', async () => {
    const studio = component('replayStudio');
    studio.session = {id: 1, cursor: 12, completed: false};
    studio.stopLoss = 95; studio.reason = 'Een helder plan';
    const key = studio.orderKey;
    let sent;
    globalThis.fetch = async (url, options) => {sent = JSON.parse(options.body); throw Error('Connection lost');};
    await studio.act('buy', studio.orderInput());
    assert.equal(sent.cursor, 12);
    assert.equal(sent.request_id, key);
    assert.equal(studio.orderInput().request_id, key);
    assert.equal(studio.busy, false);
});

test('component teardown removes its background polling timer', async () => {
    const desk = component('tradeDesk', 'AAPL', quote, 1000);
    globalThis.fetch = async () => response({cash: 1000, positions: [], protection_active: true});
    desk.init();
    const timer = desk.timer;
    desk.destroy();
    assert.equal(timer._destroyed, true);
});
