<?php

namespace App\Models;

use App\Models\B2C\B2cAgencySubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransferSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'contact_name',
        'phone',
        'email',
        'city',
        'commission_rate',
        'is_active',
        'is_approved',
        'approved_at',
        'terms_accepted_at',
        'terms_version_accepted',
        'meta',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'terms_version_accepted' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(TransferPricingRule::class, 'supplier_id');
    }

    public function coverages(): HasMany
    {
        return $this->hasMany(TransferSupplierCoverage::class, 'supplier_id');
    }

    public function cancellationPolicy(): HasOne
    {
        return $this->hasOne(TransferCancellationPolicy::class, 'supplier_id');
    }

    public function hasAcceptedVersion(int $version): bool
    {
        return $this->terms_accepted_at !== null
            && (int) $this->terms_version_accepted === max(1, $version);
    }

    public function canOperate(int $version): bool
    {
        return $this->is_approved
            && $this->is_active
            && $this->hasAcceptedVersion($version);
    }

    public function fleet(): HasMany
    {
        return $this->hasMany(TransferVehicleFleet::class, 'supplier_id');
    }

    public function b2cSubscription(): HasOne
    {
        return $this->hasOne(B2cAgencySubscription::class, 'transfer_supplier_id');
    }

    /** Bu supplier B2C'ye onaylı şekilde katılıyor mu? */
    public function isB2CApproved(): bool
    {
        return $this->b2cSubscription?->isApproved() ?? false;
    }
}
