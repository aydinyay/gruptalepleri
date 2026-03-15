<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'emoji',
        'target',
        'target_user_ids',
        'channels',
        'status',
        'scheduled_at',
        'sent_at',
        'sent_count',
        'sender_id',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'channels'        => 'array',
        'scheduled_at'    => 'datetime',
        'sent_at'         => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function targetLabel(): string
    {
        return match($this->target) {
            'all'       => 'Tüm Kullanıcılar',
            'acenteler' => 'Sadece Acenteler',
            'adminler'  => 'Sadece Adminler',
            'secili'    => 'Seçili Kullanıcılar',
            default     => $this->target,
        };
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'sent'      => 'Gönderildi',
            'scheduled' => 'Zamanlandı',
            'draft'     => 'Taslak',
            'cancelled' => 'İptal',
            default     => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'sent'      => '#198754',
            'scheduled' => '#ffc107',
            'draft'     => '#6c757d',
            'cancelled' => '#dc3545',
            default     => '#6c757d',
        };
    }

    /** Kanal listesini insan-okunur etiket dizisi olarak döndür. */
    public function channelLabels(): array
    {
        $map = ['push' => '🔔 Push', 'sms' => '💬 SMS', 'email' => '📧 E-posta'];
        $channels = $this->channels ?? ['push'];
        return array_values(array_filter(array_map(fn($c) => $map[$c] ?? null, $channels)));
    }
}
