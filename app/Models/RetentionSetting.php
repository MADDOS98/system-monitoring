<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionSetting extends Model
{
    protected $connection   = 'system_metrics';
    protected $table        = 'retention_settings';
    protected $primaryKey   = 'constant';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    protected $fillable = [
        'constant',
        'minutes',
    ];

    protected $casts = [
        'minutes' => 'integer',
    ];
}
