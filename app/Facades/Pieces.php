<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool ping()
 * @method static string getBaseUrl()
 * @method static array chat(string $message, array $context = [])
 * @method static array query(string $endpoint, array $params = [])
 *
 * @see \App\Services\PiecesService
 */
class Pieces extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\PiecesService::class;
    }
}
