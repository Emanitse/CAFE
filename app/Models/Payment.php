<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'paymentID';

    protected $fillable = [
        'orderID',
        'paymentmethod',
        'amountpaid',
        'paymentdate',
    ];

    protected $casts = [
        'amountpaid' => 'decimal:2',
        'paymentdate' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }
}