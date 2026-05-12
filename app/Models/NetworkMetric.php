<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkMetric extends Model
{
    protected $connection = 'system_metrics';
    protected $table      = 'network_metrics';
    public    $timestamps = false;

    protected $fillable = [
        'collected_at',
        'rx_bytes',
        'tx_bytes',
    ];
}
