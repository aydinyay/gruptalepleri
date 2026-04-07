<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class B2cOrderPassenger extends Model
{
    protected $table = 'b2c_order_passengers';

    protected $fillable = [
        'b2c_order_id', 'type', 'ad', 'soyad',
        'kimlik_no', 'kimlik_tipi', 'dogum_tarihi', 'uyruk', 'cinsiyet',
    ];

    protected function casts(): array
    {
        return ['dogum_tarihi' => 'date'];
    }

    public function order()
    {
        return $this->belongsTo(B2cOrder::class, 'b2c_order_id');
    }
}
