<x-layouts.app :title="$symbol . ' · Handelen · TradingTrainer'">
    @livewire('stock-chart', ['symbol' => $symbol])
</x-layouts.app>