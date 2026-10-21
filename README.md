# TradingTrainer

Laravel 12 / Livewire paper-tradingplatform met een EUR-oefenrekening, handelsdagboek en een aparte replay-studio. Er worden geen echte effectenorders verstuurd.

## Lokaal gebruiken

Herd serveert dit project op http://tradingtrainer.test. Start geen extra webserver.

```sh
composer install
npm install
php artisan migrate
npm run build
```

Nieuwe registraties krijgen automatisch € 10.000 virtueel saldo. Bestaande wallets en handelsgeschiedenis blijven behouden. De normale migraties resetten geen gegevens. `db:seed` is alleen bedoeld voor lokale demodata; de bestaande WalletSeeder maakt bij herhaling extra demoaccounts aan.

## Marktdata en valuta

- `TRADING_DATA_MODE=auto` is standaard. Aandelen gebruiken Alpaca IEX met `ALPACA_KEY` en `ALPACA_SECRET`. Zonder ingestelde sleutels krijgen aandelen een expliciet gelabelde, consistente oefensimulatie.
- `TRADING_DATA_MODE=simulated` gebruikt voor alle ondersteunde instrumenten oefenkoersen, zonder externe marktdata.
- Crypto gebruikt Binance EUR-paren. Een ontbrekend paar, een storing of een verlopen koers blokkeert orders; de applicatie verzint dan geen vervangende live koers.
- Aandelenkoersen worden op de server van USD naar EUR omgerekend via de dagelijkse Frankfurter-referentiekoers. Bron, tijdstip, oorspronkelijke prijs, FX-koers en referentiedatum worden bij elke nieuwe uitvoering bewaard. De referentiekoers is geen intraday FX-feed.
- Koersen ouder dan drie minuten zijn niet uitvoerbaar. Daardoor kunnen aandelen buiten beursuren niet verhandeld worden met een oude koers.
- Historische aandelengrafieken en replay worden met de huidige EUR-referentiekoers omgerekend, expliciet als zodanig gelabeld. Crypto gebruikt historische EUR-prijzen rechtstreeks.
- Bestaande trades zijn niet achteraf omgerekend: de oorspronkelijke invoer en bedragen zijn behouden en in het dagboek als niet geverifieerde historie aangeduid.

Referenties: [Alpaca historische bars](https://docs.alpaca.markets/us/reference/stockbars), [Binance marktdata](https://developers.binance.com/docs/binance-spot-api-docs/rest-api/market-data-endpoints), [Frankfurter valuta-API](https://frankfurter.dev/v1/).

## Orders, risico en dagboek

De server kiest de prijs; een ingestuurde browserprijs wordt niet gebruikt. Bedragen worden op centen afgerekend, aantallen ondersteunen maximaal zes decimalen en prijzen vier decimalen. De minimale orderwaarde is € 0,01. Gelijktijdige orders worden met transacties en accountvergrendeling verwerkt. Een uniek ordernummer voorkomt een dubbele uitvoering bij opnieuw versturen.

Verkopen gebruiken FIFO-aankoopkosten. Gedeeltelijk gebruikte aankooplots blijven vooraan staan. Resterende aankoopkosten en gerealiseerd resultaat sluiten op dezelfde methode aan. Onvolledige aankoophistorie blokkeert verkopen.

Bij kopen kun je strategie, instapreden, stop-loss en take-profit vastleggen. De calculator helpt een aantal op basis van gepland risico te kiezen. Bescherming geldt voor de hele positie en is op het handelsscherm te wijzigen of te verwijderen. Na afloop kun je reden, gevoel, discipline en evaluatie bijwerken in het dagboek. Filters ondersteunen instrument, type, actie, strategie en datumbereik; statistieken groeperen verkopen per vastgelegde strategie.

### Automatische positiebescherming

```sh
php artisan schedule:work
```

Op Windows/Herd kan de scheduler ook verborgen worden gestart:

```powershell
powershell -NoProfile -File scripts/start-scheduler.ps1
```

De helper voorkomt een tweede scheduler voor dit project. Na een herstart van Windows moet de scheduler opnieuw worden gestart. In productie gebruik je de gebruikelijke minuut-cron voor `php artisan schedule:run`.

De taak `trading:check-protection` controleert elke minuut. Een stop of doel is geen gegarandeerde uitvoeringsprijs: bij een overschrijding wordt tegen de recente serverkoers gesloten. Bij ontbrekende marktdata wordt niet uitgevoerd. Het handelsscherm toont een waarschuwing wanneer de bewaking geen recente heartbeat heeft. Handmatig controleren:

```sh
php artisan trading:check-protection
php artisan schedule:list
```

## Dashboard

Actueel vermogen is saldo plus actuele positiewaarde. Ontbrekende of verouderde koersen geven geen nulwaardering: de totalen blijven dan onbekend. De vermogensgrafiek gebruikt uitsluitend opgeslagen, complete dagsnapshots vanaf het moment dat het dashboard gebruikt wordt; historische punten worden niet afgeleid uit fictieve koersen. Elke dag bewaart de laatste complete waardering. Simulatiegegevens worden als oefenwaardering opgeslagen.

Kapitaalblootstelling gebruikt de resterende aankoopkosten, zodat deze indicator ook zonder actuele koersen een duidelijk gedefinieerde basis houdt. De watchlist en grafiektypevoorkeur worden alleen in de betreffende browser bewaard. Rekeninggegevens en open posities worden op de achtergrond vernieuwd.

## Replay-studio

Replay heeft een eigen persistente oefenrekening met € 10.000 per sessie. Orders raken de normale wallet, posities en trades niet.

- Historische handelsdagen gebruiken beschikbare providergegevens. Laat de datum leeg voor een verrassingsdag. De oorspronkelijke datum wordt pas na afloop getoond.
- Drie expliciet fictieve scenario's werken zonder API-sleutels: trend, omkeer en volatiliteit.
- Alleen zichtbare candles worden verstuurd; toekomstige candles blijven op de server. Sessies zijn uitsluitend toegankelijk voor de eigenaar.
- Speel 1, 5 of 10 candles verder, koop met een onderbouwde reden en stop, sluit je positie of speel door tot het einde.
- Stops en doelen worden tijdens het afspelen getoetst aan OHLC. Bij gaps geldt de openingskoers. Als stop en doel in dezelfde candle geraakt worden, wordt conservatief de stop eerst uitgevoerd. Aan het einde wordt een open positie gesloten.
- Resultaat, maximale terugval op candle-sluitingen, vergelijking met kopen-en-vasthouden en plandiscipline helpen bij de evaluatie. De oefendoelen zijn maximaal 1% gepland risico en maximaal 20% positieomvang per instap.
- Een afgeronde sessie is niet meer verhandelbaar. De evaluatie blijft bewerkbaar.

## Verificatie

```sh
php artisan test
npm test
npm run build
```

Tests gebruiken SQLite in het geheugen. De twee procestests voor gelijktijdige orders gebruiken een eigen tijdelijke SQLite-database, die na de test wordt opgeruimd. Externe marktproviders worden in tests vervangen door gecontroleerde antwoorden. De clienttests gebruiken de echte Alpine-componenten zonder een desktopbrowser te bedienen. Ze controleren onder meer onzekere netwerkantwoorden, stabiele ordernummers, risicoberekening en behoud van invoer bij achtergrondverversing. De backend-suite controleert onder meer registratie, autorisatie, valutaconversie, prijsmanipulatie, idempotentie, gelijktijdig uitgeven, FIFO-deelverkopen, positiebescherming, ontbrekende waardering, schermrendering en replay zonder toekomstige prijslekken.

De nieuwe pagina's zijn bovendien lokaal via HTTP gecontroleerd. De daadwerkelijke interactie en vormgeving in een browser zijn in deze sessie niet visueel geverifieerd; de eigen browser van de gebruiker is niet bediend.