<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = 'orderdetails';
    protected $primaryKey = 'orderdetailID';

    protected $fillable = [
        'orderID',
        'menuID',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menuID', 'menuID');
    }
}