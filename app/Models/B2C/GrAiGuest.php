<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GrAiGuest extends Model
{
    protected $table    = 'gr_ai_guests';
    protected $fillable = ['uuid', 'city', 'first_seen_at', 'last_seen_at'];
    protected $casts    = ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];

    public static function findOrCreateByUuid(string $uuid): self
    {
        return static::firstOrCreate(
            ['uuid' => $uuid],
            ['first_seen_at' => now(), 'last_seen_at' => now()]
        );
    }

    public function touchSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function memories()
    {
        return $this->hasMany(GrAiMemory::class, 'guest_uuid', 'uuid');
    }

    public function sessions()
    {
        return $this->hasMany(GrAiSession::class, 'guest_uuid', 'uuid');
    }
}
