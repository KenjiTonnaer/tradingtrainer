<?php

namespace App\Http\Controllers;

use App\Models\PaperTrade;
use App\Services\TradeLedger;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:20', 'strategy' => 'nullable|string|max:80',
            'type' => 'nullable|in:buy,sell', 'asset' => 'nullable|in:stock,crypto',
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d', 'sort' => 'nullable|in:newest,oldest,amount']);
        $query = $request->user()->paperTrades();
        if ($search = $filters['search'] ?? null) {
            $query->where('symbol', 'like', '%'.$search.'%');
        }
        if ($strategy = $filters['strategy'] ?? null) {
            $query->where('strategy', $strategy);
        }
        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($asset = $filters['asset'] ?? null) {
            $query->where('asset_type', $asset);
        }
        if ($from = $filters['from'] ?? null) {
            $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($from, 'Europe/Amsterdam')->startOfDay()->utc());
        }
        if ($to = $filters['to'] ?? null) {
            $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($to, 'Europe/Amsterdam')->endOfDay()->utc());
        }
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'amount' => $query->orderByDesc('total_value')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
        $profits = app(TradeLedger::class)->forUser($request->user()->id)['profits'];
        $stats = $request->user()->paperTrades()->where('type', 'sell')->get()->groupBy(fn ($trade) => $trade->strategy ?: 'Zonder strategie')->map(function ($trades) use ($profits) {
            $values = $trades->map(fn ($t) => (float) ($t->realized_pnl ?? $profits[$t->id] ?? 0));

            return ['count' => $values->count(), 'pnl' => $values->sum(), 'win_rate' => round($values->filter(fn ($p) => $p > 0)->count() / $values->count() * 100)];
        });

        return view('journal', ['trades' => $query->paginate(15)->withQueryString(), 'profits' => $profits, 'stats' => $stats,
            'strategies' => $request->user()->paperTrades()->whereNotNull('strategy')->distinct()->pluck('strategy')]);
    }

    public function update(Request $request, PaperTrade $trade)
    {
        abort_unless($trade->user_id === $request->user()->id, 404);
        $trade->update($request->validate([
            'strategy' => 'nullable|string|max:80', 'entry_reason' => 'nullable|string|max:2000',
            'evaluation' => 'nullable|string|max:4000', 'emotion' => 'nullable|in:rustig,twijfel,FOMO,frustratie,vertrouwen',
            'discipline' => 'nullable|integer|min:1|max:5',
        ]));

        return back()->with('status', 'Je evaluatie is opgeslagen.');
    }
}
