<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $table = 'alerts';

    protected $fillable = [
        'alert_rule_id',
        'level',
        'metric',
        'threshold',
        'operator',
        'ratio_required',
        'ratio_observed',
        'sample_count',
        'matched_count',
        'peak_value',
        'window_start',
        'window_end',
        'message',
        'read_at',
    ];

    protected $casts = [
        'threshold'      => 'float',
        'ratio_required' => 'float',
        'ratio_observed' => 'float',
        'sample_count'   => 'integer',
        'matched_count'  => 'integer',
        'peak_value'     => 'float',
        'window_start'   => 'integer',
        'window_end'     => 'integer',
        'read_at'        => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markRead(): void
    {
        $this->read_at = time();
        $this->save();
    }
}
