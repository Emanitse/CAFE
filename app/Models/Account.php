<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class Account extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'accountID';

    protected $fillable = ['username',
        'email',
        'password',
        'role',
        'is_active',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
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

    public static function registrationRules()
    {
        return [
            'username' => 'required|string|max:255|unique:accounts,username',
            'email' => 'required|email|max:255|unique:accounts,email',
            'password' => 'required|string|min:8|confirmed',
            'captcha' => 'required|numeric',
        ];
    }
}