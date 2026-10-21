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

    private function order(Request $request, ExecuteTrade $executor, string $side): JsonResponse
    {
        $input = $request->validate([
            'symbol' => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0.000001|max:100000000|decimal:0,6',
            'order_key' => 'required|uuid',
        ]);
        $trade = $executor->execute($request->user(), $side, $input);
        return response()->json(['success' => true, 'message' => 'Order uitgevoerd tegen de serverkoers in EUR.',
            'trade_id' => $trade->id, 'price' => $trade->price_per_unit, 'wallet_balance' => $request->user()->wallet()->value('balance')]);
    }
}
