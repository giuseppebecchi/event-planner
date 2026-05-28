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

    public const COMMISSION_MODE_NONE = 'NONE';
    public const COMMISSION_MODE_FIXED = 'FIXED';
    public const COMMISSION_MODE_PERCENTAGE = 'PERCENTAGE';

    public const COMMISSION_MODE_OPTIONS = [
        self::COMMISSION_MODE_NONE => 'None',
        self::COMMISSION_MODE_FIXED => 'Fixed amount',
        self::COMMISSION_MODE_PERCENTAGE => 'Percentage',
    ];

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
        'cost_items_json',
        'commission_mode',
        'commission_percentage',
        'commission_amount',
        'commission_total_amount_payed',
        'commission_payments_json',
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
        'cost_items_json' => 'array',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_total_amount_payed' => 'decimal:2',
        'commission_payments_json' => 'array',
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

            $proposal->applyDefaultCommissionIfNeeded();
            $proposal->normalizeCommissionFields();
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
            || collect($this->cost_items_json ?? [])->contains(fn ($item): bool => is_array($item) && filled($item['label'] ?? null))
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

        $this->forceFill([
            'proposal_status' => self::STATUS_CONFIRMED,
            'scouting_status' => 'chosen',
            'availability_status' => $this->availability_status === 'pending' ? 'available' : $this->availability_status,
            'confirmed_at' => $this->confirmed_at ?? now(),
        ])->save();
    }

    public function commissionBaseAmount(): float
    {
        return (float) ($this->proposed_amount ?? 0);
    }

    public function calculatedCommissionAmount(): float
    {
        if ($this->commission_mode !== self::COMMISSION_MODE_PERCENTAGE) {
            return (float) ($this->commission_amount ?? 0);
        }

        return round($this->commissionBaseAmount() * ((float) ($this->commission_percentage ?? 0) / 100), 2);
    }

    public function normalizeCommissionFields(): void
    {
        if (! array_key_exists((string) $this->commission_mode, self::COMMISSION_MODE_OPTIONS)) {
            $this->commission_mode = self::COMMISSION_MODE_NONE;
        }

        if ($this->commission_mode === self::COMMISSION_MODE_PERCENTAGE) {
            $this->commission_percentage = $this->commission_percentage !== null
                ? max(0, min(100, (float) $this->commission_percentage))
                : 0;
            $this->commission_amount = $this->calculatedCommissionAmount();
        } elseif ($this->commission_mode === self::COMMISSION_MODE_FIXED) {
            $this->commission_percentage = null;
            $this->commission_amount = max(0, (float) ($this->commission_amount ?? 0));
        } else {
            $this->commission_percentage = null;
            $this->commission_amount = 0;
        }

        $this->commission_payments_json = $this->normalizedCommissionPayments();
        $this->commission_total_amount_payed = $this->calculateCommissionPaidTotal($this->commission_payments_json);
    }

    public function normalizedCommissionPayments(): array
    {
        return collect($this->commission_payments_json ?? [])
            ->filter(fn ($payment): bool => is_array($payment))
            ->map(fn (array $payment): array => [
                'invoice_date' => filled($payment['invoice_date'] ?? null) ? (string) $payment['invoice_date'] : null,
                'due_date' => filled($payment['due_date'] ?? null) ? (string) $payment['due_date'] : null,
                'amount' => round(max(0, (float) ($payment['amount'] ?? 0)), 2),
                'paid_at' => filled($payment['paid_at'] ?? null) ? (string) $payment['paid_at'] : null,
            ])
            ->values()
            ->all();
    }

    public static function calculateCommissionPaidTotal(?array $payments): float
    {
        return round(collect($payments ?? [])
            ->filter(fn (array $payment): bool => filled($payment['paid_at'] ?? null))
            ->sum(fn (array $payment): float => (float) ($payment['amount'] ?? 0)), 2);
    }

    protected function applyDefaultCommissionIfNeeded(): void
    {
        if ($this->exists || ! $this->supplier_id) {
            return;
        }

        if (filled($this->commission_mode) && $this->commission_mode !== self::COMMISSION_MODE_NONE) {
            return;
        }

        $supplier = $this->relationLoaded('supplier')
            ? $this->supplier
            : Supplier::query()->find($this->supplier_id);

        if (! $supplier?->default_commission_enabled) {
            return;
        }

        $this->commission_mode = self::COMMISSION_MODE_PERCENTAGE;
        $this->commission_percentage = (float) ($supplier->default_commission_percentage ?? 0);
    }
}
