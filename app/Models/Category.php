<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'categoryID';

    protected $fillable = [
        'categoryname',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope('active', fn(Builder $q) => $q->where('is_active', true));
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'categoryID', 'categoryID');
    }
}