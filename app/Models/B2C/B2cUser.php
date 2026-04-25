<?php

namespace App\Models\B2C;

use App\Notifications\B2cResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class B2cUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'b2c_users';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new B2cResetPasswordNotification($token));
    }

    // ── İlişkiler ──────────────────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(B2cOrder::class, 'b2c_user_id');
    }
}
