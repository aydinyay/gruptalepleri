<?php

namespace App\Models\B2C;

use App\Models\TransferSupplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2cAgencySubscription extends Model
{
    protected $fillable = [
        'user_id',
        'transfer_supplier_id',
        'status',
        'service_types_json',
        'setup_fee',
        'fee_currency',
        'fee_payment_ref',
        'fee_paid_at',
        'commission_pct',
        'reviewed_by_user_id',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'admin_note',
        'expires_at',
        'suspended_at',
    ];

    protected $casts = [
        'service_types_json' => 'array',
        'setup_fee'          => 'decimal:2',
        'commission_pct'     => 'decimal:2',
        'fee_paid_at'        => 'datetime',
        'approved_at'        => 'datetime',
        'rejected_at'        => 'datetime',
        'expires_at'         => 'datetime',
        'suspended_at'       => 'datetime',
    ];

    // ── Status sabitler ──────────────────────────────────────────
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    // ── İlişkiler ────────────────────────────────────────────────
    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transferSupplier(): BelongsTo
    {
        return $this->belongsTo(TransferSupplier::class, 'transfer_supplier_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    // ── Scope'lar ────────────────────────────────────────────────
    public function scopeApproved($q)   { return $q->where('status', self::STATUS_APPROVED); }
    public function scopePending($q)    { return $q->where('status', self::STATUS_PENDING); }
    public function scopeForTransfer($q){ return $q->whereNotNull('transfer_supplier_id'); }

    // ── Yardımcı ─────────────────────────────────────────────────
    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED; }
    public function isPending(): bool  { return $this->status === self::STATUS_PENDING; }

    /** Geçerli komisyon oranı: özel oran varsa onu, yoksa config'deki varsayılanı döner */
    public function effectiveCommissionPct(string $productType = 'transfer'): float
    {
        if ($this->commission_pct !== null) {
            return (float) $this->commission_pct;
        }
        return (float) (config("b2c.markup.{$productType}", 0.20) * 100);
    }
}
