<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeisureSupplierQuote extends Model
{
    protected $fillable = [
        'leisure_request_id',
        'supplier_name',
        'supplier_contact_name',
        'supplier_email',
        'supplier_phone',
        'supplier_package_name',
        'cost_total',
        'currency',
        'includes_json',
        'excludes_json',
        'supplier_note',
        'operation_note',
        'status',
    ];

    protected $casts = [
        'cost_total' => 'decimal:2',
        'includes_json' => 'array',
        'excludes_json' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LeisureRequest::class, 'leisure_request_id');
    }
}
