<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiskIoMetric extends Model
{
    protected $connection = 'system_metrics';
    protected $table      = 'disk_io_metrics';
    public    $timestamps = false;

    protected $fillable = [
        'collected_at',
        'read_bytes',
        'write_bytes',
    ];
}
