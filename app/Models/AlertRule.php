<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    protected $table = 'alert_rules';

    public const LEVELS = ['info', 'warning', 'critical'];

    public const OPERATORS = ['>', '<'];

    public const METRICS = [
        'cpu',
        'ram',
        'disk_io_read',
        'disk_io_write',
        'network_in',
        'network_out',
    ];

    public const LEVEL_PRIORITY = [
        'critical' => 3,
        'warning'  => 2,
        'info'     => 1,
    ];

    protected $fillable = [
        'name',
        'metric',
        'operator',
        'threshold',
        'level',
        'window_sec',
        'ratio',
        'inactive_reset_sec',
        'is_active',
        'last_evaluated_at',
    ];

    protected $casts = [
        'threshold'          => 'float',
        'ratio'              => 'float',
        'window_sec'         => 'integer',
        'inactive_reset_sec' => 'integer',
        'is_active'          => 'boolean',
        'last_evaluated_at'  => 'integer',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
