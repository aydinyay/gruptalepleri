<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SistemAyar extends Model
{
    protected $table      = 'sistem_ayarlari';
    protected $primaryKey = 'key';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = ['key', 'value', 'label'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("sistem_ayar_{$key}", 60, fn () =>
            static::where('key', $key)->value('value') ?? $default
        );
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("sistem_ayar_{$key}");
    }
}
