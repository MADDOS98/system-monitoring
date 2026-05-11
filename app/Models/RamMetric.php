<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RamMetric extends Model
{
    protected $connection = 'system_metrics';
    protected $table      = 'ram_metrics';
    public    $timestamps = false;

    protected $fillable = [
        'collected_at',
        'total_kb',
        'used_kb',
    ];

    public function getFreeKbAttribute(): int
    {
        return $this->total_kb - $this->used_kb;
    }

    public function getUsedPctAttribute(): float
    {
        return $this->total_kb > 0
            ? round(($this->used_kb / $this->total_kb) * 100, 2)
            : 0;
    }
}
