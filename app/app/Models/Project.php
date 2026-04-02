<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    public const STATUS_OPTIONS = [
        'proposal' => 'Proposal',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'lead_id',
        'name',
        'partner_one_name',
        'partner_two_name',
        'reference_email',
        'primary_phone',
        'secondary_phone',
        'nationality',
        'preferred_language',
        'address',
        'private_notes',
        'documents',
        'region',
        'locality',
        'event_start_date',
        'event_end_date',
        'estimated_guest_count',
        'final_guest_count',
        'status',
        'logistics_notes',
    ];

    protected $casts = [
        'documents' => 'array',
        'event_start_date' => 'date',
        'event_end_date' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
