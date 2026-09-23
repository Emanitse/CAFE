<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'orderID';

    protected $fillable = [
        'accountID',
        'orderdate',
        'totalamount',
    ];

    protected $casts = [
        'orderdate' => 'datetime',
        'totalamount' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'accountID', 'accountID');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'orderID', 'orderID');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'orderID', 'orderID');
    }
}