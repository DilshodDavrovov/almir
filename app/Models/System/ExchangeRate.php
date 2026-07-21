<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'usd_price_rate',
        'eur_price_rate',
        'rub_price_rate',
        'rate_date',
    ];
}
