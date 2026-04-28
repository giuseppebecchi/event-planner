<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSupplierCommunication extends Model
{
    public const TYPE_OPTIONS = [
        'quote_request' => 'Quote request',
        'quote_response' => 'Quote response',
        'email' => 'Email',
        'call' => 'Call',
        'meeting' => 'Meeting',
        'site_visit' => 'Site visit',
        'whatsapp' => 'WhatsApp',
        'other' => 'Other',
    ];

    public const DIRECTION_OPTIONS = [
        'outgoing' => 'Outgoing',
        'incoming' => 'Incoming',
        'internal' => 'Internal',
    ];

    protected $fillable = [
        'project_id',
        'category_budget_supplier_id',
        'supplier_id',
        'communication_type',
        'direction',
        'communication_at',
        'subject',
        'message',
        'notes',
    ];

    protected $casts = [
        'communication_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function categoryBudgetSupplier(): BelongsTo
    {
        return $this->belongsTo(CategoryBudgetSupplier::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
