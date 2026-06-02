<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessMetric extends Model
{
    protected $connection = 'process_metrics';
    protected $table      = 'process_metrics';
    public    $timestamps = false;

    protected $fillable = [
        'process_name_id',
        'collected_at',
        'count',
        'ram_kb',
        'cpu_pct',
        'read_bytes',
        'write_bytes',
    ];

    protected $casts = [
        'process_name_id' => 'integer',
        'collected_at'    => 'integer',
        'count'           => 'integer',
        'ram_kb'          => 'integer',
        'cpu_pct'         => 'float',
        'read_bytes'      => 'integer',
        'write_bytes'     => 'integer',
    ];

    public function processName(): BelongsTo
    {
        return $this->belongsTo(ProcessName::class, 'process_name_id');
    }
}
