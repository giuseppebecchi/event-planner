<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryBudgetSupplier extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CONFIRMED = 'confirmed';

    public const AVAILABILITY_STATUS_OPTIONS = [
        'pending' => 'Pending',
        'available' => 'Available',
        'unavailable' => 'Unavailable',
    ];

    public const SCOUTING_STATUS_OPTIONS = [
        'contacted' => 'Contacted',
        'shortlist' => 'Shortlist',
        'discarded' => 'Discarded',
        'finalist' => 'Finalist',
        'chosen' => 'Chosen',
    ];

    public const PROPOSAL_STATUS_OPTIONS = [
        self::STATUS_REQUESTED => 'Requested',
        self::STATUS_RECEIVED => 'Received',
        'presented' => 'Presented',
        'shortlist' => 'Shortlist',
        'discarded' => 'Discarded',
        'finalist' => 'Finalist',
        'selected' => 'Selected',
        self::STATUS_CONFIRMED => 'Confirmed',
    ];

    protected $fillable = [
        'category_budget_id',
        'project_id',
        'category_id',
        'supplier_id',
        'requested_at',
        'request_text',
        'responded_at',
        'response_text',
        'availability_status',
        'proposed_dates',
        'location_available_dates',
        'costs_and_conditions',
        'planner_notes',
        'scouting_status',
        'proposed_amount',
        'proposal_summary',
        'proposal_status',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'proposed_dates' => 'array',
        'location_available_dates' => 'array',
        'proposed_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CategoryBudgetSupplier $proposal): void {
            if (blank($proposal->category_budget_id)) {
                return;
            }

            $budget = $proposal->categoryBudget()->first();

            if (! $budget) {
                return;
            }

            $proposal->project_id = $budget->project_id;
            $proposal->category_id = $budget->category_id;
        });

        static::saved(function (CategoryBudgetSupplier $proposal): void {
            $proposal->categoryBudget?->syncFromSupplierProposals();
        });

        static::deleted(function (CategoryBudgetSupplier $proposal): void {
            $proposal->categoryBudget?->syncFromSupplierProposals();
        });
    }

    public function categoryBudget(): BelongsTo
    {
        return $this->belongsTo(CategoryBudget::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function projectDocuments(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(ProjectSupplierCommunication::class);
    }

    public function hasResponse(): bool
    {
        return filled($this->responded_at)
            || filled($this->response_text)
            || filled($this->proposed_amount)
            || filled($this->proposal_summary)
            || filled($this->costs_and_conditions)
            || ($this->relationLoaded('projectDocuments')
                ? $this->projectDocuments->where('type', ProjectDocument::TYPE_QUOTE)->isNotEmpty()
                : $this->projectDocuments()->where('type', ProjectDocument::TYPE_QUOTE)->exists());
    }

    public function markAsConfirmed(): void
    {
        if (! $this->category_budget_id) {
            return;
        }

        static::query()
            ->where('category_budget_id', $this->category_budget_id)
            ->whereKeyNot($this->getKey())
            ->where('proposal_status', self::STATUS_CONFIRMED)
            ->update([
                'proposal_status' => self::STATUS_RECEIVED,
                'confirmed_at' => null,
                'updated_at' => now(),
            ]);

        $this->forceFill([
            'proposal_status' => self::STATUS_CONFIRMED,
            'scouting_status' => 'chosen',
            'availability_status' => $this->availability_status === 'pending' ? 'available' : $this->availability_status,
            'confirmed_at' => now(),
        ])->save();
    }
}
