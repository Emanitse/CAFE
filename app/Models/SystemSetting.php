<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'system_name',
        'system_logo',
    ];

    protected static ?self $cachedInstance = null;

    public static function current(): self
    {
        if (static::$cachedInstance !== null) {
            return static::$cachedInstance;
        }

        try {
            $setting = static::first();
            if (!$setting) {
                $setting = static::create([
                    'system_name' => 'Cafe Ordering System',
                    'system_logo' => null,
                ]);
            }
            static::$cachedInstance = $setting;
            return $setting;
        } catch (\Throwable $e) {
            return new static([
                'system_name' => 'Cafe Ordering System',
                'system_logo' => null,
            ]);
        }
    }

    public static function clearCache(): void
    {
        static::$cachedInstance = null;
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->system_logo && file_exists(public_path($this->system_logo))) {
            return asset($this->system_logo);
        }

        return asset('images/cofee-bean.png');
    }

    public function getLogoPublicPathAttribute(): string
    {
        if ($this->system_logo && file_exists(public_path($this->system_logo))) {
            return public_path($this->system_logo);
        }

        return public_path('images/cofee-bean.png');
    }
}