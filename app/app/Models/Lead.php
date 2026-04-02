<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Lead extends Model
{
    public const SOURCE_OPTIONS = [
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'wedding_portals' => 'Wedding portals',
        'la_lista_portal' => 'La Lista portal',
        'website_form' => 'Website form',
        'past_wedding_guest' => 'Guest from a past wedding',
        'word_of_mouth' => 'Word of mouth',
        'other' => 'Other',
    ];

    public const CEREMONY_TYPE_OPTIONS = [
        'religious' => 'Religious',
        'civil' => 'Civil',
        'symbolic' => 'Symbolic',
    ];

    public const LOCATION_REQUEST_TYPE_OPTIONS = [
        'stay_and_event' => 'Stay + event',
        'event_only' => 'Event only',
    ];

    public const STATUS_OPTIONS = [
        'new' => 'New',
        'under_review' => 'Under review',
        'proposal_sent' => 'Proposal sent',
        'call_scheduled' => 'Call scheduled',
        'confirmed' => 'Confirmed',
        'lost' => 'Lost',
    ];

    public const EVALUATION_OUTCOME_OPTIONS = [
        'yes' => 'Yes',
        'no' => 'No',
        'maybe' => 'Maybe',
    ];

    public const PROPOSAL_RESPONSE_OPTIONS = [
        'awaiting' => 'Awaiting reply',
        'changes_requested' => 'Changes requested',
        'accepted' => 'Accepted',
        'approved' => 'Approved',
    ];

    protected $fillable = [
        'requested_at',
        'source',
        'couple_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'nationality',
        'estimated_guest_count',
        'wedding_period',
        'desired_region',
        'ceremony_type',
        'ceremony_details',
        'location_request_type',
        'additional_events',
        'budget_amount',
        'style_description',
        'status',
        'evaluation_outcome',
        'public_form_hash',
        'form_sent_at',
        'form_completed_at',
        'form_payload',
        'budget_vendors',
        'budget_wedding_planner',
        'budget_wedding_planner_extra_services',
        'budget_wedding_planner_special_packages',
        'proposal_sent_at',
        'proposal_response_status',
        'proposal_response_at',
        'proposal_notes_log',
        'contract_sent_at',
        'contract_received_at',
        'signed_contract_document_id',
        'internal_notes',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'budget_amount' => 'decimal:2',
        'form_sent_at' => 'datetime',
        'form_completed_at' => 'datetime',
        'form_payload' => 'array',
        'budget_vendors' => 'array',
        'budget_wedding_planner' => 'array',
        'budget_wedding_planner_extra_services' => 'array',
        'budget_wedding_planner_special_packages' => 'array',
        'proposal_sent_at' => 'datetime',
        'proposal_response_at' => 'datetime',
        'proposal_notes_log' => 'array',
        'contract_sent_at' => 'datetime',
        'contract_received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Lead $lead): void {
            if (blank($lead->public_form_hash)) {
                $lead->public_form_hash = Str::lower(Str::random(32));
            }
        });
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LeadDocument::class);
    }

    public function signedContractDocument(): BelongsTo
    {
        return $this->belongsTo(LeadDocument::class, 'signed_contract_document_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(LeadFollowUp::class);
    }

    protected function publicFormUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('public.lead-form.show', $this->public_form_hash),
        );
    }
}
