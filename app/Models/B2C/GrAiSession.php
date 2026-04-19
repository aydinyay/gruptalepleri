<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class GrAiSession extends Model
{
    protected $table    = 'gr_ai_sessions';
    protected $fillable = ['b2c_user_id', 'guest_uuid', 'role', 'message', 'suggested_slugs'];
    protected $casts    = ['suggested_slugs' => 'array'];

    /**
     * Son N mesajı (kullanıcı + asistan karışık) döner.
     */
    public static function historyFor(?int $userId, ?string $guestUuid, int $limit = 12): array
    {
        $query = static::query()->orderByDesc('id')->limit($limit);

        if ($userId) {
            $query->where('b2c_user_id', $userId);
        } elseif ($guestUuid) {
            $query->where('guest_uuid', $guestUuid);
        } else {
            return [];
        }

        return $query->get(['role', 'message'])->reverse()->values()->toArray();
    }

    /**
     * Mesaj kaydet.
     */
    public static function addMessage(
        ?int $userId,
        ?string $guestUuid,
        string $role,
        string $message,
        ?array $slugs = null
    ): void {
        static::create([
            'b2c_user_id'     => $userId,
            'guest_uuid'      => $guestUuid,
            'role'            => $role,
            'message'         => $message,
            'suggested_slugs' => $slugs,
        ]);
    }
}
