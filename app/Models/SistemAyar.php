<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SistemAyar extends Model
{
    public const KEY_SMS_ENABLED = 'sms_enabled';
    public const KEY_EMAIL_ENABLED = 'email_enabled';
    public const KEY_PUSH_ENABLED = 'push_enabled';
    public const KEY_BROADCAST_ENABLED = 'broadcast_enabled';
    public const KEY_AI_CELEBRATION_ENABLED = 'ai_celebration_enabled';
    public const KEY_CHARTER_RFQ_MAX_SUPPLIERS = 'charter_rfq_max_suppliers';
    public const KEY_TRANSFER_SUPPLIER_TERMS_TEXT = 'transfer_supplier_terms_text';
    public const KEY_TRANSFER_SUPPLIER_TERMS_VERSION = 'transfer_supplier_terms_version';

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

    public static function bool(string $key, bool $default = true): bool
    {
        $value = static::get($key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        if ($normalized === '') {
            return $default;
        }

        return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    }

    public static function smsEnabled(): bool
    {
        return static::bool(static::KEY_SMS_ENABLED, true);
    }

    public static function emailEnabled(): bool
    {
        return static::bool(static::KEY_EMAIL_ENABLED, true);
    }

    public static function pushEnabled(): bool
    {
        return static::bool(static::KEY_PUSH_ENABLED, true);
    }

    public static function broadcastEnabled(): bool
    {
        return static::bool(static::KEY_BROADCAST_ENABLED, true);
    }

    public static function aiCelebrationEnabled(): bool
    {
        return static::bool(static::KEY_AI_CELEBRATION_ENABLED, true);
    }

    public static function charterRfqMaxSuppliers(int $default = 10): int
    {
        $value = static::get(static::KEY_CHARTER_RFQ_MAX_SUPPLIERS, (string) $default);

        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return max(1, $default);
    }

    public static function transferSupplierTermsVersion(int $default = 1): int
    {
        $value = static::get(static::KEY_TRANSFER_SUPPLIER_TERMS_VERSION, (string) $default);

        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return max(1, $default);
    }

    public static function transferSupplierTermsText(): string
    {
        $default = trim(<<<'TEXT'
Transfer tedarikcisi olarak listeleyecegim fiyat, kural ve kapasite bilgilerinin dogrulugundan sorumluyum.
Rezervasyon kabul ettigimde hizmeti eksiksiz saglamayi ve iptal/iade kurallarina uymayi kabul ederim.
Platform komisyon ve mutabakat kurallarini okudum, kabul ediyorum.
TEXT);

        return (string) static::get(static::KEY_TRANSFER_SUPPLIER_TERMS_TEXT, $default);
    }
}
