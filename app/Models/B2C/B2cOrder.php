<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class B2cOrder extends Model
{
    protected $table = 'b2c_orders';

    protected $fillable = [
        'order_ref',
        'b2c_user_id',
        'catalog_item_id',
        'item_title',
        'product_type',
        'guest_name',
        'guest_phone',
        'guest_email',
        'status',
        'pax_count',
        'service_date',
        'event_type',
        'notes',
        'unit_price',
        'total_price',
        'currency',
        'payment_status',
        'paid_at',
        'supplier_notified_at',
        'confirmed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'service_date'          => 'date',
            'paid_at'               => 'datetime',
            'supplier_notified_at'  => 'datetime',
            'confirmed_at'          => 'datetime',
            'unit_price'            => 'decimal:2',
            'total_price'           => 'decimal:2',
        ];
    }

    // ── İlişkiler ──────────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(B2cUser::class, 'b2c_user_id');
    }

    public function item()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function passengers()
    {
        return $this->hasMany(B2cOrderPassenger::class, 'b2c_order_id');
    }

    public function payments()
    {
        return $this->hasMany(B2cPayment::class, 'b2c_order_id');
    }

    public function latestPayment()
    {
        return $this->hasOne(B2cPayment::class, 'b2c_order_id')->latestOfMany();
    }

    // ── Erişimciler ────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'       => 'Ödeme Bekleniyor',
            'pending_quote' => 'Fiyat Bekleniyor',
            'quote_sent'    => 'Fiyat Gönderildi',
            'confirmed'     => 'Onaylandı',
            'in_operation'  => 'Operasyonda',
            'completed'     => 'Tamamlandı',
            'cancelled'     => 'İptal Edildi',
            'refunded'      => 'İade Edildi',
            default         => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending', 'pending_quote', 'quote_sent' => 'warning',
            'confirmed', 'in_operation'              => 'primary',
            'completed'                              => 'success',
            'cancelled', 'refunded'                  => 'danger',
            default                                  => 'secondary',
        };
    }
}
