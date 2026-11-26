<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $table = 'access_logs';
    protected $primaryKey = 'accesslogID';
    public $timestamps = false;

    protected $fillable = [
        'accountID',
        'username',
        'email',
        'role',
        'event',
        'status',
        'ip_address',
        'user_agent',
        'details',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}