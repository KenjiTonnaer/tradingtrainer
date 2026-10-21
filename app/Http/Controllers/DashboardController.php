<?php

namespace App\Http\Controllers;

use App\Models\PortfolioSnapshot;
use App\Services\TradeLedger;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TradeLedger $ledger)
    {
        $user = $request->user();
        $user->wallet()->firstOrCreate([], ['balance' => config('trading.starting_balance'), 'currency' => 'EUR']);
        $trades = $user->paperTrades()->orderBy('created_at')->orderBy('id')->get();
        $profits = $ledger->calculate($trades)['profits'];
        $sells = $trades->where('type', 'sell');
        $realized = $sells->sum(fn ($t) => (float) ($t->realized_pnl ?? $profits[$t->id] ?? 0));
        $winRate = $sells->count() ? round($sells->filter(fn ($t) => ($t->realized_pnl ?? $profits[$t->id] ?? 0) > 0)->count() / $sells->count() * 100) : null;
        $positions = $user->paperPositions()->orderBy('symbol')->get();
        $invested = (float) $positions->sum('total_invested');
        $cash = (float) $user->wallet()->value('balance');
        $largest = $positions->sortByDesc('total_invested')->first();

        return view('dashboard', compact('cash', 'positions', 'trades', 'profits', 'realized', 'winRate', 'invested', 'largest') + [
            'history' => PortfolioSnapshot::where('user_id', $user->id)->orderBy('date')->get(['date', 'equity', 'data_status']),
            'exposure' => $cash + $invested > 0 ? $invested / ($cash + $invested) * 100 : 0,
        ]);
    }
}
