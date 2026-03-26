<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferSupplierCoverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'airport_id',
        'zone_id',
        'direction',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TransferSupplier::class, 'supplier_id');
    }

    public function airport(): BelongsTo
    {
        return $this->belongsTo(TransferAirport::class, 'airport_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(TransferZone::class, 'zone_id');
    }
}

