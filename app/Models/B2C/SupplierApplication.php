<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class SupplierApplication extends Model
{
    protected $table = 'supplier_applications';

    protected $fillable = [
        'applicant_name', 'company_name', 'email', 'phone',
        'service_types_json', 'notes', 'status',
        'reviewed_by_user_id', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'service_types_json' => 'array',
            'reviewed_at'        => 'datetime',
        ];
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by_user_id');
    }
}
