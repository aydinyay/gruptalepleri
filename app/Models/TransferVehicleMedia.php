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

        if (Str::startsWith($this->file_path, ['http://', 'https://'])) {
            return $this->file_path;
        }

        // asset() request domain'ini kullanır; config('app.url') her zaman sabit domain döner
        $base = rtrim(config('app.url', 'https://gruptalepleri.com'), '/');
        return $base . '/' . ltrim($this->file_path, '/');
    }
}
