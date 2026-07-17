<?php

namespace App\Models;

use App\Services\StoreContent;
use Database\Factories\StoreSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    /** @use HasFactory<StoreSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value_json ?? $default;
    }

    public static function set(string $key, mixed $value): static
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value_json' => $value],
        );

        StoreContent::flush();

        return $setting;
    }
}
