<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessCommand extends Model
{
    protected $connection = 'process_metrics';
    protected $table      = 'process_commands';
    public    $timestamps = false;

    protected $fillable = [
        'process_name_id',
        'command',
    ];

    protected $casts = [
        'process_name_id' => 'integer',
    ];

    public function processName(): BelongsTo
    {
        return $this->belongsTo(ProcessName::class, 'process_name_id');
    }
}
