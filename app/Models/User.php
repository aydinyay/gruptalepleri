<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'show_iade_badge',
        'can_send_broadcast',
        'email_unsubscribed',
        'parent_agency_id',
        'acente_rolu',
        'davet_token',
        'davet_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'davet_expires_at'  => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Çoklu acente kullanıcısı ---

    public function isAcenteOwner(): bool
    {
        return $this->role === 'acente' && is_null($this->parent_agency_id);
    }

    public function acenteRootId(): int
    {
        return $this->parent_agency_id ?? $this->id;
    }

    public function canDo(string $yetki): bool
    {
        $paket = $this->acente_rolu ?? 'owner';
        $yetkiler = [
            'owner'     => ['talep', 'teklif', 'odeme', 'finans', 'yolcu', 'ayarlar', 'calisanlar'],
            'tam'       => ['talep', 'teklif', 'odeme', 'finans', 'yolcu'],
            'operasyon' => ['talep', 'teklif', 'yolcu'],
            'muhasebe'  => ['finans', 'odeme'],
        ];
        return in_array($yetki, $yetkiler[$paket] ?? []);
    }

    public function calisanlar()
    {
        return $this->hasMany(User::class, 'parent_agency_id');
    }

    public function parentAgency()
    {
        return $this->belongsTo(User::class, 'parent_agency_id');
    }

public function agency()
{
    return $this->hasOne(Agency::class);
}


public function requests()
{
    return $this->hasMany(\App\Models\Request::class);
}

public function bildirimleri()
{
    return $this->hasMany(KullaniciBildirimi::class);
}

public function okunmamisBildirimSayisi(): int
{
    return $this->bildirimleri()->where('is_read', false)->count();
}
}