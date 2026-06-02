<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessName extends Model
{
    protected $connection = 'process_metrics';
    protected $table      = 'process_names';
    public    $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function commands(): HasMany
    {
        return $this->hasMany(ProcessCommand::class, 'process_name_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ProcessMetric::class, 'process_name_id');
    }
}
