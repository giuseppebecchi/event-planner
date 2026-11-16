<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
        'label',
        'initial_estimated_amount',
        'comparison_amount',
        'final_amount',
        'budget_status',
        'ref_courtesy_messagge_sent_at',
        'notes',
    ];

    protected $casts = [
        'initial_estimated_amount' => 'decimal:2',
        'comparison_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'ref_courtesy_messagge_sent_at' => 'boolean',
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

    public function confirmedProposals()
    {
        return $this->supplierProposals()
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED);
    }

    public function displayLabel(?string $fallback = null): string
    {
        $customLabel = trim((string) $this->label);

        if ($customLabel !== '') {
            return $customLabel;
        }

        $fallbackLabel = trim((string) $fallback);

        if ($fallbackLabel !== '') {
            return $fallbackLabel;
        }

        return trim((string) ($this->category?->label ?: $this->category?->label_it)) ?: 'Category';
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
        $confirmedProposals = $this->supplierProposals()
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)
            ->latest('responded_at')
            ->latest('updated_at')
            ->get();

        if ($confirmedProposals->isNotEmpty()) {
            $confirmedTotal = (float) $confirmedProposals->sum(fn (CategoryBudgetSupplier $proposal): float => (float) ($proposal->proposed_amount ?? 0));
            $hasConfirmedAmount = $confirmedProposals->contains(fn (CategoryBudgetSupplier $proposal): bool => $proposal->proposed_amount !== null);

            $this->forceFill([
                'comparison_amount' => $hasConfirmedAmount ? $confirmedTotal : $this->comparison_amount,
                'final_amount' => $hasConfirmedAmount ? $confirmedTotal : null,
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

    public function applyDefaultSupplier(?float $leadAmount = null): bool
    {
        return DB::transaction(function () use ($leadAmount): bool {
            $budget = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            $category = $budget->category;
            $supplier = $category?->defaultSupplier;
            if (! $supplier || $budget->supplierProposals()->exists()) {
                return false;
            }
            $confirmed = (bool) $category->auto_confirm_default_supplier;
            $budget->supplierProposals()->create([
                'supplier_id' => $supplier->id,
                'proposal_status' => $confirmed ? CategoryBudgetSupplier::STATUS_CONFIRMED : CategoryBudgetSupplier::STATUS_REQUESTED,
                'scouting_status' => $confirmed ? 'chosen' : 'contacted',
                'availability_status' => $confirmed ? 'available' : 'pending',
                'proposed_amount' => $confirmed ? $leadAmount : null,
                'confirmed_at' => $confirmed ? now() : null,
                'proposal_summary' => 'Automatically linked from the category default supplier settings.',
            ]);

            return true;
        });
    }
}
