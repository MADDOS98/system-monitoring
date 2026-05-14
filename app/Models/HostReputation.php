<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostReputation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'host',
        'status',
        'reason',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public const STATUS_TRUSTED = 1;
    public const STATUS_WARNING = 2;
    public const STATUS_DANGER  = 3;

    public const STATUS_LABELS = [
        self::STATUS_TRUSTED => 'trusted',
        self::STATUS_WARNING => 'warning',
        self::STATUS_DANGER  => 'danger',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'unknown';
    }
}
