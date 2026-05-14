<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectionMetric extends Model
{
    protected $connection = 'system_metrics';
    protected $table      = 'connection_metrics';
    public    $timestamps = false;

    protected $fillable = [
        'collected_at',
        'local_ip',
        'total_connections',
        'port_counts',
        'state_counts',
    ];

    protected $casts = [
        'port_counts'       => 'array',
        'state_counts'      => 'array',
        'total_connections' => 'integer',
    ];
}
