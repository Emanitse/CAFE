<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $table = 'error_logs';
    protected $primaryKey = 'errorlogID';
    public $timestamps = false;

    protected $fillable = [
        'accountID',
        'username',
        'level',
        'exception',
        'message',
        'file',
        'line',
        'url',
        'method',
        'ip_address',
        'trace',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}