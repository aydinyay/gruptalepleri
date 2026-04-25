<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigortaOdeme extends Model
{
    protected $table = 'sigorta_odemeler';

    protected $fillable = [
        'sigorta_police_id',
        'sigorta_batch_job_id',
        'kanal',
        'internal_reference',
        'provider_reference',
        'amount_try',
        'status',
        'request_payload_json',
        'callback_payload_json',
        'failure_reason',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'amount_try'             => 'decimal:2',
        'request_payload_json'   => 'array',
        'callback_payload_json'  => 'array',
        'paid_at'                => 'datetime',
        'failed_at'              => 'datetime',
    ];

    public function police(): BelongsTo
    {
        return $this->belongsTo(SigortaPolice::class, 'sigorta_police_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SigortaBatchJob::class, 'sigorta_batch_job_id');
    }

    public function onaylandi(): bool
    {
        return $this->status === 'approved';
    }
}
