<x-layouts.guest title="Jouw handelsdesk. Jouw leercurve. · TradingTrainer">
    @php
        // Deliberately fictional EUR prices; this public demonstration never submits an order.
        $closes = [172, 173.2, 172.6, 174.8, 176, 175.1, 174.4, 176.8, 178, 177.3, 179.4, 181.2, 180.3, 178.8, 179.6, 182, 183.4, 182.6, 181.7, 184.1, 185.5, 184.8, 183.6, 185, 186.2, 185.2, 187.4, 188, 186.7, 185.4, 186.1, 188.4, 190.2, 189.1, 191, 190.3];
        $candles = [];
        foreach ($closes as $i => $close) {
            $open = $closes[max(0, $i - 1)];
            $candles[] = ['open' => $open, 'close' => $close, 'high' => max($open, $close) + .8, 'low' => min($open, $close) - .7];
        }
        $y = fn ($price) => 320 - ($price - 168) * 10;
    @endphp

    <section class="market-ribbon" aria-label="Wat je kunt oefenen">
        <span><b>AAPL</b> AANDELEN</span><span><b>BTC</b> CRYPTO</span><span><b>EUR</b> REKENINGVALUTA</span><span><b>€ 10.000</b> VIRTUEEL STARTSALDO</span><span class="ribbon-label">JOUW LEERCURVE BEGINT HIER</span>
    </section>

    <section class="landing-hero">
        <div class="hero-kicker"><span class="orange-square" aria-hidden="true"></span> THE PRACTICE DESK <span>VOOR JE EERSTE ÉN JE VOLGENDE TRADE</span></div>
        <h1>De markt wacht niet.<br><span>Jij kunt wél oefenen.</span></h1>
        <div class="hero-intro">
            <p>Niet elke trade wordt winst. Elke trade kan een les worden.<br> Ontwikkel je aanpak met virtueel geld, een handelsplan en de ruimte om opnieuw te beginnen.</p>
            <a href="{{ auth()->check() ? route('markets.show', 'AAPL') : route('register') }}" class="button primary hero-cta">Neem plaats aan je handelsdesk <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <section class="demo-section" aria-labelledby="demo-title" x-data="landingDemo(@js($candles))">
        <div class="demo-terminal">
            <div class="terminal-bar"><div class="terminal-title"><span class="terminal-code">TT / 001</span><h2 id="demo-title">De oefenronde</h2></div><span class="demo-disclaimer"><span class="orange-square" aria-hidden="true"></span> FICTIEVE KOERSEN · GEEN LIVE DATA</span></div>
            <div class="terminal-content">
                <div class="demo-market">
                    <div class="demo-market-heading"><div><span class="mono-label">AAPL / VOORBEELDSCENARIO</span><div class="demo-price"><span x-text="money(current.close)">€ 182,60</span><small>EUR</small></div></div><div class="demo-chart-types" role="group" aria-label="Grafiektype"><button @click="chartType = 'candles'" :aria-pressed="chartType === 'candles'">Kaarsen</button><button @click="chartType = 'line'" :aria-pressed="chartType === 'line'">Lijn</button></div></div>
                    <x-chart-legend />
                    <p class="chart-readout" x-text="candleReadout(current)" aria-label="Candlewaarden"></p>
                    <div class="demo-chart-scroll">
                        <svg class="demo-chart" viewBox="0 0 970 390" role="img" aria-label="Interactief fictief koersverloop. Gebruik de knoppen onder de grafiek om candles af te spelen.">
                            <defs><linearGradient id="demo-line-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#60a5fa" stop-opacity=".2"/><stop offset="1" stop-color="#60a5fa" stop-opacity="0"/></linearGradient></defs>
                            @foreach([170, 175, 180, 185, 190] as $level)
                                <line x1="25" x2="906" y1="{{ $y($level) }}" y2="{{ $y($level) }}" class="demo-grid-line" />
                                <text x="925" y="{{ $y($level) + 4 }}" class="demo-axis-text">{{ $level }}</text>
                            @endforeach
                            @foreach([0, 8, 16, 24, 32] as $tick)
                                <line x1="{{ 45 + $tick * 24 }}" x2="{{ 45 + $tick * 24 }}" y1="35" y2="330" class="demo-grid-line vertical" />
                                <text x="{{ 45 + $tick * 24 }}" y="373" class="demo-axis-text">{{ $tick + 1 }}</text>
                            @endforeach
                            <g x-show="chartType === 'candles'">
                                @foreach($candles as $i => $candle)
                                    <g x-show="cursor >= {{ $i }}" @if($i > 17) style="display:none" @endif class="{{ $candle['close'] > $candle['open'] ? 'demo-candle-up' : ($candle['close'] < $candle['open'] ? 'demo-candle-down' : 'demo-candle-flat') }}">
                                        <line x1="{{ 45 + $i * 24 }}" x2="{{ 45 + $i * 24 }}" y1="{{ $y($candle['high']) }}" y2="{{ $y($candle['low']) }}" />
                                        <rect x="{{ 39 + $i * 24 }}" y="{{ min($y($candle['open']), $y($candle['close'])) }}" width="12" height="{{ max(2, abs($y($candle['open']) - $y($candle['close']))) }}" />
                                    </g>
                                @endforeach
                            </g>
                            <g x-show="chartType === 'line'" style="display:none"><path :d="areaPath" fill="url(#demo-line-fill)"/><path :d="linePath" fill="none" class="demo-line" stroke-width="2.5"/></g>
                            <line x1="25" x2="906" y1="{{ $y(178) }}" y2="{{ $y(178) }}" class="demo-entry-line" />
                            <rect x="34" y="{{ $y(178) + 8 }}" width="164" height="26" fill="#222222"/><text x="45" y="{{ $y(178) + 25 }}" class="demo-entry-label">INSTAPIDEE · € 178,00</text>
                            <g class="demo-price-marker" transform="translate(453,174)" :transform="'translate(' + (45 + cursor * 24) + ',' + (320 - (current.close - 168) * 10) + ')'">
                                <circle r="5" fill="currentColor"/><circle r="11" fill="none" stroke="currentColor" opacity=".4"/>
                            </g>
                            <text x="906" y="386" text-anchor="end" class="demo-axis-text">CANDLE →</text>
                        </svg>
                    </div>
                    <div class="demo-playback"><div class="playback-buttons"><button class="demo-play" @click="togglePlay()" :aria-label="playing ? 'Afspelen pauzeren' : 'Oefengrafiek afspelen'" x-text="playing ? 'Ⅱ Pauze' : cursor === candles.length - 1 ? '↺ Opnieuw' : '▷ Speel af'">▷ Speel af</button><button class="demo-next" @click="next()" :disabled="cursor === candles.length - 1">Volgende candle <span aria-hidden="true">→</span></button></div><span class="mono-label"><span x-text="cursor + 1">18</span> / {{ count($candles) }} CANDLES</span></div>
                </div>
                <aside class="demo-plan"><span class="mono-label">EERST EEN PLAN.</span><h3>Dan pas<br>de knop.</h3><dl><div><dt>Instapidee</dt><dd>€ 178,00</dd></div><div><dt>Stop-loss</dt><dd>€ 174,00</dd></div><div><dt>Gepland risico</dt><dd>€ 40,00<small>10 stuks × € 4,00</small></dd></div></dl><p>Een koerslijn vertelt wat er gebeurde. Je dagboek vertelt waarom je meedeed.</p><a href="{{ auth()->check() ? route('replay.index') : route('register') }}">Probeer een hele sessie <span aria-hidden="true">→</span></a></aside>
            </div>
            <div class="terminal-status"><span><span class="status-dot"></span> OEFENSTAND</span><span>ECHTE BESLISSINGEN. VIRTUEEL GELD.</span><span>JOUW TEMPO / JOUW PLAN</span></div>
        </div>
        <p class="demo-footnote">Dit is een interactieve illustratie met fictieve EUR-prijzen, geen AAPL-notering in de oorspronkelijke handelsvaluta. Er wordt geen order geplaatst en het voorbeeld is geen beleggingsadvies.</p>
    </section>

    <section class="practice-section" id="werkwijze" aria-labelledby="practice-title">
        <div class="practice-inner">
            <div class="practice-heading"><p class="eyebrow">EEN SYSTEEM VOOR JE LEERCURVE</p><h2 id="practice-title">Minder gissen.<br><span>Meer begrijpen.</span></h2><p>Een koopknop is het begin.<br>Wat je ervoor en erna doet, maakt het verschil.</p></div>
            <div class="practice-steps">
                <article><span class="step-index">01 / VOORBEREIDEN</span><div><h3>Geef je idee een grens.</h3><p>Kies een instrument. Schrijf je reden op. Bepaal vooraf hoeveel je wilt riskeren met je positiegrootte en stop-loss.</p><a href="{{ route('markets.show', 'AAPL') }}">Naar de handelsdesk →</a></div></article>
                <article><span class="step-index">02 / UITPROBEREN</span><div><h3>Maak de beslissing zelf.</h3><p>Handel met virtueel geld of speel een historische dag stap voor stap af. De volgende candle zie je pas wanneer je verder speelt.</p><a href="{{ route('replay.index') }}">Ontdek de oefenstudio →</a></div></article>
                <article><span class="step-index">03 / TERUGKIJKEN</span><div><h3>Zoek de les in je trade.</h3><p>Beoordeel je aanpak, gevoel en discipline. Je resultaat per strategie helpt je patronen in je eigen beslissingen te herkennen.</p><a href="{{ route('journal.index') }}">Bekijk het handelsdagboek →</a></div></article>
            </div>
        </div>
    </section>

    <section class="landing-closing">
        <span class="closing-index">VOLGENDE SESSIE / JIJ</span>
        <h2>Je hoeft het nog<br>niet te kunnen.</h2>
        <div><p>Daar is oefenen voor.<br>Begin met € 10.000 virtueel startkapitaal.</p><a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="button primary">Begin aan je leercurve <span aria-hidden="true">→</span></a></div>
        <details class="data-disclosure"><summary>Over het koersmateriaal en virtuele geld <span aria-hidden="true">+</span></summary><p>Je oefent met beschikbare marktdata of duidelijk gelabelde simulatiekoersen. Bij ieder instrument staan de bron en actualiteit. Bij ontbrekende recente koersen worden orders gepauzeerd. Het platform verstuurt geen echte effectenorders.</p></details>
    </section>
</x-layouts.guest>
