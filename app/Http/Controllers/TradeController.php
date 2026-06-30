<?php
// app/Http/Controllers/TradeController.php

namespace App\Http\Controllers;

use App\Models\PaperPosition;
use App\Models\PaperTrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TradeController extends Controller
{
    /** GET /trades — alle trades van de ingelogde user */
    public function index(): JsonResponse
    {
        $trades = auth()->user()
            ->paperTrades()
            ->latest()
            ->take(50)
            ->get();

        return response()->json($trades);
    }

    /** GET /portfolio — alle open posities */
    public function portfolio(): JsonResponse
    {
        $positions = auth()->user()
            ->paperPositions()
            ->get();

        return response()->json($positions);
    }

    /** POST /trades/buy */
    public function buy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol'     => 'required|string|max:20',
            'quantity'   => 'required|numeric|min:0.000001',
            'price'      => 'required|numeric|min:0.01',
            'asset_type' => 'sometimes|string|in:stock,crypto',
        ]);

        $user   = auth()->user();
        $wallet = $user->wallet;
        $symbol = strtoupper($validated['symbol']);
        $qty    = (float)$validated['quantity'];
        $price  = (float)$validated['price'];
        $total  = round($qty * $price, 2);
        $assetType = $validated['asset_type'] ?? 'stock';

        if (!$wallet->hasSufficientFunds($total)) {
            return response()->json([
                'success' => false,
                'message' => 'Onvoldoende saldo. Je hebt €' . number_format($wallet->balance, 2, ',', '.') .
                             ' beschikbaar, maar deze order kost €' . number_format($total, 2, ',', '.') . '.',
            ], 422);
        }

        DB::transaction(function () use ($user, $wallet, $symbol, $qty, $price, $total, $assetType) {
            // 1. Wallet verlagen
            $wallet->withdraw($total);

            // 2. Trade registreren
            PaperTrade::create([
                'user_id'              => $user->id,
                'symbol'               => $symbol,
                'type'                 => 'buy',
                'asset_type'           => $assetType,
                'quantity'             => $qty,
                'price_per_unit'       => $price,
                'total_value'          => $total,
                'wallet_balance_after' => $wallet->fresh()->balance,
                'status'               => 'filled',
            ]);

            // 3. Positie bijwerken (of aanmaken)
            $position = PaperPosition::firstOrCreate(
                ['user_id' => $user->id, 'symbol' => $symbol],
                ['asset_type' => $assetType, 'quantity' => 0, 'avg_buy_price' => 0, 'total_invested' => 0]
            );

            // Nieuwe gemiddelde prijs berekenen (weighted average)
            $newTotalQty      = (float)$position->quantity + $qty;
            $newTotalInvested = (float)$position->total_invested + $total;
            $newAvgPrice      = $newTotalQty > 0 ? $newTotalInvested / $newTotalQty : $price;

            $position->update([
                'quantity'       => $newTotalQty,
                'avg_buy_price'  => $newAvgPrice,
                'total_invested' => $newTotalInvested,
            ]);
        });

        return response()->json([
            'success'        => true,
            'message'        => "✅ Gekocht: {$qty} × {$symbol} @ €" . number_format($price, 2, ',', '.'),
            'wallet_balance' => $user->wallet->fresh()->balance,
        ]);
    }

    /** POST /trades/sell */
    public function sell(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symbol'   => 'required|string|max:20',
            'quantity' => 'required|numeric|min:0.000001',
            'price'    => 'required|numeric|min:0.01',
        ]);

        $user     = auth()->user();
        $symbol   = strtoupper($validated['symbol']);
        $qty      = (float)$validated['quantity'];
        $price    = (float)$validated['price'];
        $total    = round($qty * $price, 2);
        $position = PaperPosition::where('user_id', $user->id)->where('symbol', $symbol)->first();

        if (!$position || (float)$position->quantity < $qty) {
            $heeft = $position ? number_format($position->quantity, 6) : '0';
            return response()->json([
                'success' => false,
                'message' => "Je hebt niet genoeg {$symbol}. Je bezit {$heeft}, maar probeert {$qty} te verkopen.",
            ], 422);
        }

        DB::transaction(function () use ($user, $position, $symbol, $qty, $price, $total) {
            $wallet = $user->wallet;

            // 1. Wallet verhogen
            $wallet->deposit($total);

            // 2. Trade registreren
            PaperTrade::create([
                'user_id'              => $user->id,
                'symbol'               => $symbol,
                'type'                 => 'sell',
                'asset_type'           => $position->asset_type,
                'quantity'             => $qty,
                'price_per_unit'       => $price,
                'total_value'          => $total,
                'wallet_balance_after' => $wallet->fresh()->balance,
                'status'               => 'filled',
            ]);

            // 3. Positie verminderen / verwijderen
            $newQty       = (float)$position->quantity - $qty;
            $soldFraction = $qty / (float)$position->quantity;
            $newInvested  = (float)$position->total_invested * (1 - $soldFraction);

            if ($newQty < 0.000001) {
                $position->delete(); // positie volledig gesloten
            } else {
                $position->update([
                    'quantity'       => $newQty,
                    'total_invested' => $newInvested,
                    // avg_buy_price blijft hetzelfde bij partial sell
                ]);
            }
        });

        return response()->json([
            'success'        => true,
            'message'        => "✅ Verkocht: {$qty} × {$symbol} @ €" . number_format($price, 2, ',', '.'),
            'wallet_balance' => $user->wallet->fresh()->balance,
        ]);
    }
}