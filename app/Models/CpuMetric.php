<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpuMetric extends Model
{
    protected $connection = 'system_metrics';
    protected $table      = 'cpu_metrics';
    public    $timestamps = false;

    protected $fillable = [
        'collected_at',
        'total_usage',
        'per_core_usage',
        'stolen_usage',
    ];

    protected $casts = [
        'per_core_usage' => 'array',
        'total_usage'    => 'float',
        'stolen_usage'   => 'float',
    ];
}
