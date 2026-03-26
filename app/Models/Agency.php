<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    protected $fillable = [
        'user_id',
        'tourism_title',
        'company_title',
        'tax_number',
        'tax_office',
        'phone',
        'email',
        'address',
        'contact_name',
        'tursab_no',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}