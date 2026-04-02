<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowUp extends Model
{
    public const TYPE_OPTIONS = [
        'generic' => 'Generic',
        'call' => 'Call',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'meeting' => 'Meeting',
        'proposal' => 'Proposal follow-up',
        'reminder' => 'Reminder',
        'other' => 'Other',
    ];

    public const CHANNEL_OPTIONS = [
        'phone' => 'Phone',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'video_call' => 'Video call',
        'in_person' => 'In person',
        'other' => 'Other',
    ];

    public const STATUS_OPTIONS = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'missed' => 'Missed',
    ];

    public const PRIORITY_OPTIONS = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const OUTCOME_OPTIONS = [
        'interested' => 'Interested',
        'awaiting_reply' => 'Awaiting reply',
        'call_booked' => 'Call booked',
        'proposal_sent' => 'Proposal sent',
        'converted' => 'Converted',
        'not_interested' => 'Not interested',
        'other' => 'Other',
    ];

    protected $fillable = [
        'lead_id',
        'subject',
        'follow_up_type',
        'contact_channel',
        'status',
        'priority',
        'due_at',
        'remind_at',
        'completed_at',
        'outcome',
        'notes',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'remind_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
