<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickReplySession extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_NEEDS_REVIEW = 'needs_review';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    public const MEMBERSHIP_AUTO = 'auto';
    public const MEMBERSHIP_MEMBER = 'member';
    public const MEMBERSHIP_NON_MEMBER = 'non_member';
    public const MEMBERSHIP_UNKNOWN = 'unknown';

    protected $fillable = [
        'user_id',
        'manual_agency_id',
        'selected_agency_id',
        'selected_request_id',
        'selected_user_id',
        'confirmed_by_user_id',
        'final_offer_id',
        'status',
        'membership_mode',
        'resolved_membership',
        'match_confidence',
        'requires_manual_review',
        'requires_new_account',
        'raw_text',
        'error_message',
        'confirmation_summary',
        'parsed_payload',
        'edited_payload',
        'agency_candidates',
        'request_candidates',
        'new_account_payload',
        'meta',
        'confirmed_at',
    ];

    protected $casts = [
        'match_confidence' => 'decimal:2',
        'requires_manual_review' => 'boolean',
        'requires_new_account' => 'boolean',
        'parsed_payload' => 'array',
        'edited_payload' => 'array',
        'agency_candidates' => 'array',
        'request_candidates' => 'array',
        'new_account_payload' => 'array',
        'meta' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(QuickReplyLog::class, 'session_id')->orderBy('created_at');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function manualAgency()
    {
        return $this->belongsTo(Agency::class, 'manual_agency_id');
    }

    public function selectedAgency()
    {
        return $this->belongsTo(Agency::class, 'selected_agency_id');
    }

    public function selectedRequest()
    {
        return $this->belongsTo(Request::class, 'selected_request_id');
    }

    public function selectedUser()
    {
        return $this->belongsTo(User::class, 'selected_user_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function finalOffer()
    {
        return $this->belongsTo(Offer::class, 'final_offer_id');
    }
}
