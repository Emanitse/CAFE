<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'categoryID';

    protected $fillable = [
        'categoryname',
    ];

    public $timestamps = false;

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'categoryID', 'categoryID');
    }
}