<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiskUsageMetric extends Model
{
    protected $connection = 'system_metrics';
    protected $table      = 'disk_usage_metrics';
    public    $timestamps = false;

    protected $fillable = [
        'collected_at',
        'total_bytes',
        'used_bytes',
    ];

    public function getFreebytesAttribute(): int
    {
        return $this->total_bytes - $this->used_bytes;
    }

    public function getUsedPctAttribute(): float
    {
        return $this->total_bytes > 0
            ? round(($this->used_bytes / $this->total_bytes) * 100, 2)
            : 0;
    }
}
