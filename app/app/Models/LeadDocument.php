<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadDocument extends Model
{
    public const TYPE_OPTIONS = [
        'brochure' => 'Brochure',
        'presentation' => 'Presentation',
        'proposal' => 'Proposal',
        'contract' => 'Contract draft',
        'signed_contract' => 'Signed contract',
        'price_list' => 'Price list',
        'reference_material' => 'Reference material',
        'other' => 'Other',
    ];

    protected $fillable = [
        'lead_id',
        'title',
        'document_type',
        'file_path',
        'description',
        'is_shared_with_client',
        'uploaded_at',
    ];

    protected $casts = [
        'is_shared_with_client' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
