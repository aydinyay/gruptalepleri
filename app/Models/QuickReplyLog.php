<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickReplyLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'level',
        'action',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(QuickReplySession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
