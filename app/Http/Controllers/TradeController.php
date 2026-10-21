<?php

namespace App\Http\Controllers;

use App\Services\ExecuteTrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(auth()->user()->paperTrades()->latest()->take(50)->get());
    }

    public function portfolio(): JsonResponse
    {
        return response()->json(auth()->user()->paperPositions()->get());
    }

    public function buy(Request $request, ExecuteTrade $executor): JsonResponse
    {
        return $this->order($request, $executor, 'buy');
    }

    public function sell(Request $request, ExecuteTrade $executor): JsonResponse
    {
        return $this->order($request, $executor, 'sell');
    }

    public function protection(Request $request, \App\Models\PaperPosition $position): JsonResponse
    {
        abort_unless($position->user_id === $request->user()->id, 404);
        $input = $request->validate(['stop_loss' => 'nullable|numeric|min:0.0001|max:999999999', 'take_profit' => 'nullable|numeric|min:0.0001|max:999999999']);
        $quote = app(\App\Services\MarketData::class)->quote($position->symbol);
        if (! $quote['tradable'] || (! empty($input['stop_loss']) && $input['stop_loss'] >= $quote['price']) || (! empty($input['take_profit']) && $input['take_profit'] <= $quote['price'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['protection' => 'Gebruik een recente koers, een stop onder de koers en een doel erboven.']);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $position, $input) {
            $request->user()->wallet()->lockForUpdate()->firstOrFail();
            $locked = $request->user()->paperPositions()->whereKey($position->id)->lockForUpdate()->firstOrFail();
            $locked->update(['stop_loss' => $input['stop_loss'] ?? null, 'take_profit' => $input['take_profit'] ?? null]);
        });

        return response()->json(['success' => true]);
    }

    private function order(Request $request, ExecuteTrade $executor, string $side): JsonResponse
    {
        $input = $request->validate([
            'symbol' => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0.000001|max:100000000|decimal:0,6',
            'order_key' => 'required|uuid',
            'strategy' => 'nullable|string|max:80',
            'entry_reason' => 'nullable|string|max:2000',
            'stop_loss' => 'nullable|numeric|min:0.0001|max:999999999',
            'take_profit' => 'nullable|numeric|min:0.0001|max:999999999',
        ]);
        $trade = $executor->execute($request->user(), $side, $input);

        return response()->json(['success' => true, 'message' => 'Order uitgevoerd tegen de serverkoers in EUR.',
            'trade_id' => $trade->id, 'price' => $trade->price_per_unit, 'wallet_balance' => $request->user()->wallet()->value('balance')]);
    }
}
