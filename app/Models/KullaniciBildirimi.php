<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KullaniciBildirimi extends Model
{
    protected $table = 'kullanici_bildirimleri';

    protected $fillable = ['user_id', 'type', 'title', 'message', 'url', 'is_read', 'read_at'];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function okunduIsaretle(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }

    public static function typeIcon(string $type): string
    {
        return match($type) {
            'new_request'    => '📋',
            'new_agency'     => '🏢',
            'offer_added'    => '💰',
            'offer_accepted' => '✅',
            'opsiyon_uyarisi'=> '⚠️',
            default          => '🔔',
        };
    }
}
