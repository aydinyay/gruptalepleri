<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastEmailTrack extends Model
{
    protected $fillable = [
        'broadcast_id',
        'user_id',
        'token',
        'type',
        'destination_url',
        'ip',
        'user_agent',
        'triggered_at',
        'hit_count',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(BroadcastNotification::class, 'broadcast_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Token üret: broadcast_id + user_id + type + secret karışımından sha256
     */
    public static function makeToken(int $broadcastId, int $userId, string $type, string $url = ''): string
    {
        return hash('sha256', implode('|', [
            $broadcastId,
            $userId,
            $type,
            $url,
            config('app.key'),
        ]));
    }
}
