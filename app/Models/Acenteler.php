<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acenteler extends Model
{
    protected $table = 'acenteler';

    // Sadece okuma — bu tabloya yazma yapılmaz
    public $timestamps = false;

    protected $fillable = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn() => false);
        static::updating(fn() => false);
        static::deleting(fn() => false);
    }
}
