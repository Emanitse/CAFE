<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $table = 'menuitems';
    protected $primaryKey = 'menuID';

    protected $fillable = [
        'categoryID',
        'itemname',
        'price',
        'stock',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryID', 'categoryID');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'menuID', 'menuID');
    }
}