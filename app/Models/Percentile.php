<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Percentile extends Model
{
    protected $table = 'percentiles';

    protected $fillable = [
        'name',
        'metric',
        'percentile',
        'window_minutes',
        'is_active',
    ];

    protected $casts = [
        'percentile'     => 'decimal:2',
        'window_minutes' => 'integer',
        'is_active'      => 'boolean',
    ];
}
