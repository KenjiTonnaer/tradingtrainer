<?php

namespace App\Http\Controllers;

use App\Models\ReplaySession;
use App\Services\MarketData;
use App\Services\ReplayEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReplayController extends Controller
{
    public function index(Request $request)
    {
        return view('replay', ['sessions' => ReplaySession::where('user_id', $request->user()->id)->latest()->take(12)->get(['id', 'symbol', 'source', 'completed', 'updated_at'])]);
    }

    public function store(Request $request, ReplayEngine $engine, MarketData $market)
    {
        $input = $request->validate(['mode' => 'required|in:scenario,historical', 'scenario' => 'required_if:mode,scenario|in:trend,omkeer,volatiliteit',
            'symbol' => ['required_if:mode,historical', Rule::in($market->symbols())], 'date' => 'nullable|date_format:Y-m-d|before:today|after:2020-01-01']);
        if ($input['mode'] === 'historical') {
            $date = $input['date'] ?? now('UTC')->subDays(random_int(2, 90))->previousWeekday()->toDateString();
            $data = $market->history($input['symbol'], '1D', $date);
            if (count($data['candles']) < 24 || $data['status'] !== 'historical') {
                throw ValidationException::withMessages(['date' => 'Geen complete historische handelsdag beschikbaar. Kies een andere dag of een oefenscenario.']);
            }
            $candles = $data['candles'];
            $source = 'Historische replay · '.$data['source'];
        } else {
            $candles = $engine->scenario($input['scenario']);
            $source = 'Oefenscenario · '.$input['scenario'].' · geen historische koers';
        }
        $session = ReplaySession::create(['user_id' => $request->user()->id, 'symbol' => $input['mode'] === 'historical' ? $input['symbol'] : 'TRAIN',
            'market_date' => $input['mode'] === 'historical' ? $date : null, 'source' => $source, 'candles' => $candles,
            'orders' => [], 'curve' => [12 => ['step' => 12, 'equity' => 10000]]]);

        return response()->json($engine->state($session->fresh()));
    }

    public function show(Request $request, ReplaySession $replay, ReplayEngine $engine)
    {
        abort_unless($replay->user_id === $request->user()->id, 404);

        return response()->json($engine->state($replay));
    }

    public function update(Request $request, ReplaySession $replay, ReplayEngine $engine)
    {
        abort_unless($replay->user_id === $request->user()->id, 404);
        $input = $request->validate(['action' => 'required|in:buy,sell,advance,finish,evaluate',
            'cursor' => 'sometimes|integer|min:0',
            'quantity' => 'required_if:action,buy,sell|numeric|min:0.000001|max:100000000|decimal:0,6',
            'request_id' => 'required_if:action,buy,sell|uuid', 'steps' => 'required_if:action,advance|integer|in:1,5,10',
            'stop_loss' => 'required_if:action,buy|nullable|numeric|min:0.0001', 'take_profit' => 'nullable|numeric|min:0.0001',
            'reason' => 'required_if:action,buy|string|min:5|max:1000', 'evaluation' => 'required_if:action,evaluate|string|max:4000']);

        return DB::transaction(function () use ($request, $replay, $engine, $input) {
            $session = ReplaySession::whereKey($replay->id)->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            if ($input['action'] === 'evaluate') {
                abort_unless($session->completed, 422);
                $session->update(['evaluation' => $input['evaluation']]);
            } else {
                $engine->action($session, $input);
            }

            return response()->json($engine->state($session));
        }, 5);
    }
}
