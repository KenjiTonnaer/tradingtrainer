<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplaySession extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['candles'];

    protected $casts = ['candles' => 'array', 'orders' => 'array', 'curve' => 'array', 'completed' => 'boolean'];
}
