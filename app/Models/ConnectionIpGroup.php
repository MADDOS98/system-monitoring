<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectionIpGroup extends Model
{
    protected $connection = 'system_metrics';
    protected $table      = 'connection_ip_groups';
    public    $timestamps = false;

    protected $fillable = [
        'ip',
        'group_name',
    ];
}
