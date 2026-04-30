<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::created(function (Project $project): void {
            $project->syncChecklistOptionsFromTemplates();
        });

        static::saved(function (Project $project): void {
            if ($project->projectChecklistOptions()->doesntExist()) {
                $project->syncChecklistOptionsFromTemplates();
            }

            if ($project->wasChanged('event_start_date')) {
                $project->refreshChecklistOptionDueDates();
            }
        });
    }

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

        $this->syncChecklistOptionsFromTemplates();

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

    public function projectChecklistOptions(): HasMany
    {
        return $this->hasMany(ProjectChecklistOption::class);
    }

    public function projectDocuments(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function projectEvents(): HasMany
    {
        return $this->hasMany(ProjectEvent::class);
    }

    public function projectTimelineItems(): HasMany
    {
        return $this->hasMany(ProjectTimeline::class);
    }

    public function projectImages(): HasMany
    {
        return $this->hasMany(ProjectImage::class);
    }

    public function projectMoodboards(): HasMany
    {
        return $this->hasMany(ProjectMoodboard::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supplierCommunications(): HasMany
    {
        return $this->hasMany(ProjectSupplierCommunication::class);
    }

    public function syncChecklistOptionsFromTemplates(): void
    {
        if (! $this->exists) {
            return;
        }

        $this->loadMissing('categoryBudgets.supplierProposals');

        $categoryBudgets = $this->categoryBudgets->keyBy('category_id');
        $eventDate = $this->event_start_date;

        foreach (Checklist::query()->with('category')->orderBy('title')->get() as $checklist) {
            foreach (array_values($checklist->options ?? []) as $index => $option) {
                $order = max(1, (int) ($option['order'] ?? ($index + 1)));
                $isDefault = (bool) ($option['default'] ?? false);

                //if not default continue
                if(! $isDefault) {
                    continue;
                }


                $categoryBudget = $checklist->category_id ? $categoryBudgets->get($checklist->category_id) : null;

                $item = $this->projectChecklistOptions()->firstOrNew([
                    'checkbox_id' => $checklist->id,
                    'order' => $order,
                ]);

                $item->fill([
                    'supplier_id' => $categoryBudget?->confirmedProposal()?->supplier_id,
                    'category_budget_id' => $categoryBudget?->id,
                    'title' => trim((string) ($option['title'] ?? '')),
                    'details' => null,
                    'default' => $isDefault,
                    'anticipation' => filled($option['anticipation'] ?? null) ? trim((string) $option['anticipation']) : null,
                    'assigned_to' => ProjectChecklistOption::normalizeAssignedTo($option['assigned_to'] ?? null),
                    'due_date' => static::calculateChecklistDueDate($eventDate, $option['anticipation'] ?? null),
                ]);

                if (! $item->exists) {
                    $item->enabled = $isDefault;
                    $item->completed = false;
                    $item->completed_at = null;
                }

                if ($item->isDirty()) {
                    $item->save();
                }
            }
        }
    }

    public function refreshChecklistOptionDueDates(): void
    {
        if (! $this->exists) {
            return;
        }

        $eventDate = $this->event_start_date;

        $this->projectChecklistOptions()->get()->each(function (ProjectChecklistOption $item) use ($eventDate): void {
            $dueDate = static::calculateChecklistDueDate($eventDate, $item->anticipation);

            if (($item->due_date?->format('Y-m-d')) !== ($dueDate?->format('Y-m-d'))) {
                $item->forceFill([
                    'due_date' => $dueDate,
                ])->saveQuietly();
            }
        });
    }

    public static function calculateChecklistDueDate(?Carbon $eventDate, mixed $anticipation): ?Carbon
    {
        if (! $eventDate || blank($anticipation)) {
            return null;
        }

        $parts = preg_split('/\s+/', trim((string) $anticipation), 2);

        if (! is_array($parts) || count($parts) < 2 || ! is_numeric($parts[0])) {
            return null;
        }

        $amount = (int) $parts[0];
        $unit = Str::singular(Str::lower(trim((string) $parts[1])));

        return match ($unit) {
            'day' => $eventDate->copy()->subDays($amount),
            'week' => $eventDate->copy()->subWeeks($amount),
            'month' => $eventDate->copy()->subMonths($amount),
            default => null,
        };
    }
}
