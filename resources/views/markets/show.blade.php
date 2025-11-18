<x-layouts.app :title="'Trading - ' . $symbol">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        @livewire('stock-chart', ['symbol' => $symbol])
    </div>
</x-layouts.app>
