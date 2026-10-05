<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * Cache key for branding settings.
     */
    public const BRANDING_CACHE_KEY = 'app_branding_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Retrieve a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting !== null ? $setting->value : $default;
    }

    /**
     * Store or update a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value !== null ? (string) $value : null]
        );

        Cache::forget(self::BRANDING_CACHE_KEY);
    }

    /**
     * Remove a setting by key.
     */
    public static function forget(string $key): void
    {
        static::query()->where('key', $key)->delete();

        Cache::forget(self::BRANDING_CACHE_KEY);
    }

    /**
     * Get the consolidated branding attributes.
     *
     * @return array{name: string, logo: string, favicon: string, has_custom_logo: bool, has_custom_favicon: bool}
     */
    public static function getBranding(): array
    {
        $settings = Cache::rememberForever(self::BRANDING_CACHE_KEY, function (): array {
            return static::query()
                ->whereIn('key', ['branding_name', 'branding_logo', 'branding_favicon'])
                ->pluck('value', 'key')
                ->all();
        });

        $customLogo = $settings['branding_logo'] ?? null;
        $customFavicon = $settings['branding_favicon'] ?? null;
        $name = ! empty($settings['branding_name']) ? $settings['branding_name'] : 'PRPIMS-SDN';

        return [
            'name' => $name,
            'logo' => $customLogo ? '/storage/'.ltrim($customLogo, '/') : '/images/sdn-logo.png',
            'favicon' => $customFavicon ? '/storage/'.ltrim($customFavicon, '/') : '/favicon.ico',
            'has_custom_logo' => ! empty($customLogo),
            'has_custom_favicon' => ! empty($customFavicon),
        ];
    }
}
