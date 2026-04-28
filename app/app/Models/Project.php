<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'region',
        'locality',
        'event_start_date',
        'event_end_date',
        'estimated_guest_count',
        'final_guest_count',
        'budget_amount',
        'status',
        'logistics_notes',
    ];

    protected $casts = [
        'event_start_date' => 'date',
        'event_end_date' => 'date',
        'budget_amount' => 'decimal:2',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function initBudget(): array
    {
        $lead = $this->lead;

        if (! $lead) {
            return [
                'project_id' => $this->id,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'no_lead' => true,
            ];
        }

        if (($this->budget_amount === null) && ($lead->budget_amount !== null)) {
            $this->forceFill([
                'budget_amount' => $lead->budget_amount,
            ])->saveQuietly();
        }

        $categories = Category::query()->get()->keyBy('id');

        $stats = [
            'project_id' => $this->id,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'no_lead' => false,
        ];

        $rows = collect($lead->budget_vendors ?? [])
            ->map(function (array $row) use ($categories): ?array {
                $categoryId = $row['category_id'] ?? null;

                if (! $categoryId && filled($row['label'] ?? null)) {
                    $matchedCategory = $categories->first(
                        fn (Category $category): bool => in_array(
                            mb_strtolower(trim((string) ($row['label'] ?? ''))),
                            [
                                mb_strtolower(trim((string) $category->label)),
                                mb_strtolower(trim((string) $category->label_it)),
                            ],
                            true,
                        )
                    );

                    $categoryId = $matchedCategory?->id;
                }

                if (! $categoryId) {
                    return null;
                }

                return [
                    'category_id' => $categoryId,
                    'amount' => blank($row['amount'] ?? null) ? 0 : (float) str_replace(',', '.', (string) $row['amount']),
                    'notes' => filled($row['notes'] ?? null) ? trim((string) $row['notes']) : null,
                ];
            })
            ->filter()
            ->unique('category_id')
            ->values();

        /** @var Collection<int, array{category_id:int, amount:float, notes:?string}> $rows */
        foreach ($rows as $row) {
            $budget = $this->categoryBudgets()->firstOrNew([
                'category_id' => $row['category_id'],
            ]);

            $isNew = ! $budget->exists;

            $budget->initial_estimated_amount = $row['amount'];

            if ($isNew && blank($budget->budget_status)) {
                $budget->budget_status = CategoryBudget::STATUS_HYPOTHETICAL;
            }

            if (filled($row['notes']) && blank($budget->notes)) {
                $budget->notes = $row['notes'];
            }

            if ($budget->isDirty()) {
                $budget->save();
                $stats[$isNew ? 'created' : 'updated']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    public function categoryBudgets(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
    }

    public function categoryBudgetSuppliers(): HasMany
    {
        return $this->hasMany(CategoryBudgetSupplier::class);
    }

    public function projectDocuments(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function projectImages(): HasMany
    {
        return $this->hasMany(ProjectImage::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supplierCommunications(): HasMany
    {
        return $this->hasMany(ProjectSupplierCommunication::class);
    }
}
