<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailAbone extends Model
{
    protected $table = 'email_aboneler';

    protected $fillable = ['email', 'token', 'ip', 'aktif'];
}
