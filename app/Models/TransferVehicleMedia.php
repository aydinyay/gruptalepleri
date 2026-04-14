<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TransferVehicleMedia extends Model
{
    protected $table = 'transfer_vehicle_media';

    protected $fillable = [
        'vehicle_type_id',
        'media_type',
        'source_type',
        'file_path',
        'external_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(TransferVehicleType::class, 'vehicle_type_id');
    }

    public function resolvedUrl(): ?string
    {
        if ($this->source_type === 'link') {
            return $this->external_url;
        }

        if (! $this->file_path) {
            return null;
        }

        if (Str::startsWith($this->file_path, ['http://', 'https://', '/'])) {
            return $this->file_path;
        }

        return asset($this->file_path);
    }
}
