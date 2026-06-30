<?php

use App\Services\PiecesService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function () {
    Config::set('services.pieces.base_url', 'http://127.0.0.1:39300');
    Config::set('services.pieces.host', '127.0.0.1');
    Config::set('services.pieces.port', 39300);
});

test('ping returns true when Pieces OS responds successfully', function () {
    Http::fake([
        '127.0.0.1:39300/assets' => Http::response(['iterable' => []], 200),
    ]);

    $service = app(PiecesService::class);

    expect($service->ping())->toBeTrue();
});

test('ping returns false when Pieces OS is unreachable', function () {
    Http::fake([
        '127.0.0.1:39300/assets' => Http::response(null, 500),
    ]);

    $service = app(PiecesService::class);

    expect($service->ping())->toBeFalse();
});

test('ping returns false on connection exception', function () {
    Http::fake([
        '127.0.0.1:39300/assets' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        },
    ]);

    $service = app(PiecesService::class);

    expect($service->ping())->toBeFalse();
});

test('chat sends message to Pieces OS and returns response', function () {
    Http::fake([
        '127.0.0.1:39300/api/qgpt/conversation' => Http::response([
            'answer' => 'Hello from Pieces!',
        ], 200),
    ]);

    $service = app(PiecesService::class);
    $result = $service->chat('Say hello');

    expect($result)
        ->toHaveKey('success', true)
        ->toHaveKey('response', 'Hello from Pieces!');
});

test('chat returns error when Pieces OS fails', function () {
    Http::fake([
        '127.0.0.1:39300/api/qgpt/conversation' => Http::response(null, 502),
    ]);

    $service = app(PiecesService::class);
    $result = $service->chat('Say hello');

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error');
});

test('getBaseUrl returns configured URL', function () {
    $service = app(PiecesService::class);

    expect($service->getBaseUrl())->toBe('http://127.0.0.1:39300');
});

test('service is bound as singleton in container', function () {
    $instance1 = app(PiecesService::class);
    $instance2 = app(PiecesService::class);

    expect($instance1)->toBe($instance2);
});
