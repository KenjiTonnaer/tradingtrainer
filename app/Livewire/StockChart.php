<?php

namespace App\Livewire;

use App\Services\MarketData;
use Livewire\Attributes\Locked;
use Livewire\Component;

class StockChart extends Component
{
    #[Locked]
    public string $symbol = 'AAPL';

    public string $timeframe = '1D';

    public function mount(string $symbol = 'AAPL'): void
    {
        $this->symbol = app(MarketData::class)->symbol($symbol);
    }

    public function changeTimeframe(string $timeframe): void
    {
        if (in_array($timeframe, ['1h', '1D', '30D', '6M', '1Y'], true)) {
            $this->timeframe = $timeframe;
        }
    }

    public function render()
    {
        $market = app(MarketData::class);

        return view('livewire.stock-chart', [
            'historical' => $market->history($this->symbol, $this->timeframe),
            'quote' => $market->quote($this->symbol),
            'symbols' => $market->symbols(),
        ]);
    }
}
