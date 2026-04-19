<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class GrAiMemory extends Model
{
    protected $table    = 'gr_ai_memories';
    protected $fillable = ['b2c_user_id', 'guest_uuid', 'key', 'value', 'confidence'];

    /**
     * Bir kullanıcı/misafir için tüm hafızayı key => value dizisi olarak döner.
     */
    public static function getFor(?int $userId, ?string $guestUuid): array
    {
        $query = static::query();

        if ($userId) {
            $query->where('b2c_user_id', $userId);
        } elseif ($guestUuid) {
            $query->where('guest_uuid', $guestUuid);
        } else {
            return [];
        }

        return $query->pluck('value', 'key')->all();
    }

    /**
     * Bir key'i güncelle veya oluştur.
     */
    public static function upsertFor(?int $userId, ?string $guestUuid, string $key, string $value, int $confidence = 70): void
    {
        if (! $userId && ! $guestUuid) return;

        $where = $userId
            ? ['b2c_user_id' => $userId, 'key' => $key]
            : ['guest_uuid'  => $guestUuid, 'key' => $key];

        static::updateOrCreate($where, [
            'value'      => $value,
            'confidence' => $confidence,
        ]);
    }

    /**
     * Guest hafızasını user_id'ye taşı (login sonrası).
     */
    public static function migrateGuestToUser(string $guestUuid, int $userId): void
    {
        $guestMemories = static::where('guest_uuid', $guestUuid)->get();

        foreach ($guestMemories as $mem) {
            // Kullanıcıda zaten varsa düşük confidence'lı olanı üzerine yaz
            $existing = static::where('b2c_user_id', $userId)->where('key', $mem->key)->first();
            if (! $existing || $existing->confidence < $mem->confidence) {
                static::updateOrCreate(
                    ['b2c_user_id' => $userId, 'key' => $mem->key],
                    ['value' => $mem->value, 'confidence' => $mem->confidence]
                );
            }
        }

        static::where('guest_uuid', $guestUuid)->delete();
    }
}
