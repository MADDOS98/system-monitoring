<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApacheLog extends Model
{
    protected $table = 'apache_logs';

    public $timestamps = false;

    protected $fillable = [
        'log_time',
        'remote_host',
        'ident',
        'user',
        'method',
        'uri',
        'protocol',
        'status',
        'bytes_sent',
        'referer',
        'user_agent'
    ];
}
