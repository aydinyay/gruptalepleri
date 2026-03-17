<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiCelebrationCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DISMISSED = 'dismissed';

    public const DISPLAY_BANNER = 'banner';
    public const DISPLAY_POPUP = 'popup';
    public const DISPLAY_CARD = 'card';

    protected $fillable = [
        'source_key',
        'event_name',
        'event_date',
        'category',
        'status',
        'title',
        'message',
        'cta_text',
        'cta_url',
        'topic_prompt',
        'visual_prompt',
        'ai_payload',
        'image_path',
        'is_ai_generated',
        'display_mode',
        'show_on_public',
        'show_on_authenticated',
        'frequency_cap',
        'priority',
        'publish_starts_at',
        'publish_ends_at',
        'approved_by',
        'approved_at',
        'published_by',
        'published_at',
        'dismissed_by',
        'dismissed_at',
        'dismiss_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'publish_starts_at' => 'datetime',
        'publish_ends_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'is_ai_generated' => 'boolean',
        'show_on_public' => 'boolean',
        'show_on_authenticated' => 'boolean',
        'ai_payload' => 'array',
    ];

    public function scopePublishedActive(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $checkAt = $at ?? now();

        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $windowQuery) use ($checkAt): void {
                $windowQuery
                    ->whereNull('publish_starts_at')
                    ->orWhere('publish_starts_at', '<=', $checkAt);
            })
            ->where(function (Builder $windowQuery) use ($checkAt): void {
                $windowQuery
                    ->whereNull('publish_ends_at')
                    ->orWhere('publish_ends_at', '>=', $checkAt);
            });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function dismisser()
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function userStates()
    {
        return $this->hasMany(AiCelebrationUserState::class, 'campaign_id');
    }
}

