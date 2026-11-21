<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class Account extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'accountID';

    protected $fillable = ['email', 'password', 'role', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', fn(Builder $q) => $q->where('is_active', true));
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public static function validationRules($accountId = null)
    {
        $uniqueRule = 'unique:accounts,email';
        if ($accountId) {
            $uniqueRule .= ',' . $accountId . ',accountID';
        }
        return [
            'email' => 'required|email|max:255|' . $uniqueRule,
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,staff',
            'is_active' => 'boolean',
        ];
    }
}