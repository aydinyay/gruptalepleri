<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCelebrationUserState extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'seen_count',
        'first_seen_at',
        'last_seen_at',
        'closed_at',
        'clicked_at',
        'last_action',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'closed_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(AiCelebrationCampaign::class, 'campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

