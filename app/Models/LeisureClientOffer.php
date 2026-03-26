<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class LeisureClientOffer extends Model
{
    protected $fillable = [
        'leisure_request_id',
        'supplier_quote_id',
        'package_template_id',
        'package_label',
        'total_price',
        'per_person_price',
        'currency',
        'includes_snapshot',
        'excludes_snapshot',
        'extras_snapshot',
        'media_snapshot',
        'timeline_tr',
        'timeline_en',
        'offer_note_tr',
        'offer_note_en',
        'status',
        'shared_at',
        'accepted_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'per_person_price' => 'decimal:2',
        'includes_snapshot' => 'array',
        'excludes_snapshot' => 'array',
        'extras_snapshot' => 'array',
        'media_snapshot' => 'array',
        'shared_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LeisureRequest::class, 'leisure_request_id');
    }

    public function supplierQuote(): BelongsTo
    {
        return $this->belongsTo(LeisureSupplierQuote::class, 'supplier_quote_id');
    }

    public function packageTemplate(): BelongsTo
    {
        return $this->belongsTo(LeisurePackageTemplate::class, 'package_template_id');
    }

    public function shareUrl(string $lang = 'tr'): string
    {
        return URL::temporarySignedRoute(
            'leisure.share',
            now()->addDays(14),
            ['offer' => $this->id, 'lang' => $lang]
        );
    }
}
