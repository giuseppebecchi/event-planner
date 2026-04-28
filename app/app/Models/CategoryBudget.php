<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryBudget extends Model
{
    public const STATUS_HYPOTHETICAL = 'hypothetical';
    public const STATUS_IN_EVALUATION = 'in_evaluation';
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_OPTIONS = [
        self::STATUS_HYPOTHETICAL => 'Hypothetical',
        self::STATUS_IN_EVALUATION => 'In evaluation',
        self::STATUS_CONFIRMED => 'Confirmed',
    ];

    protected $fillable = [
        'project_id',
        'category_id',
        'initial_estimated_amount',
        'comparison_amount',
        'final_amount',
        'budget_status',
        'notes',
    ];

    protected $casts = [
        'initial_estimated_amount' => 'decimal:2',
        'comparison_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplierProposals(): HasMany
    {
        return $this->hasMany(CategoryBudgetSupplier::class);
    }

    public function confirmedProposal(): ?CategoryBudgetSupplier
    {
        return $this->supplierProposals
            ->firstWhere('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED);
    }

    public function currentWorkingAmount(): ?float
    {
        return $this->final_amount !== null
            ? (float) $this->final_amount
            : ($this->comparison_amount !== null ? (float) $this->comparison_amount : null);
    }

    public function amountDifference(): float
    {
        return (float) ($this->currentWorkingAmount() ?? 0) - (float) ($this->initial_estimated_amount ?? 0);
    }

    public function syncFromSupplierProposals(): void
    {
        $confirmedProposal = $this->supplierProposals()
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)
            ->latest('responded_at')
            ->latest('updated_at')
            ->first();

        if ($confirmedProposal) {
            $this->forceFill([
                'comparison_amount' => $confirmedProposal->proposed_amount ?? $this->comparison_amount,
                'final_amount' => $confirmedProposal->proposed_amount,
                'budget_status' => self::STATUS_CONFIRMED,
            ])->saveQuietly();

            return;
        }

        $latestQuotedProposal = $this->supplierProposals()
            ->whereNotNull('proposed_amount')
            ->latest('responded_at')
            ->latest('updated_at')
            ->first();

        $hasSupplierProposals = $this->supplierProposals()->exists();

        $this->forceFill([
            'comparison_amount' => $latestQuotedProposal?->proposed_amount,
            'final_amount' => null,
            'budget_status' => $hasSupplierProposals ? self::STATUS_IN_EVALUATION : self::STATUS_HYPOTHETICAL,
        ])->saveQuietly();
    }
}
