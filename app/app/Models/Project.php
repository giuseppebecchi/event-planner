<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Project extends Model
{
    use SoftDeletes;

    protected bool $shouldInitializeDefaultTimeline = false;

    public const STATUS_OPTIONS = [
        'proposal' => 'Proposal',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
    ];

    public const DEFAULT_TIMELINE_TITLES = [
        'Hair and make-up for guests starts',
        'Caterer arrives',
        'Planner gets to the venue',
        'Bride starts to get ready',
        'Groom starts to get ready',
        'Bride is ready for the dress and has portraits',
        'Guests arrive',
        'Ceremony',
        'Aperitivo',
        'Couple session',
        'Grand entrance to dinner area',
        'Dinner',
        'Speeches',
        'Cake cutting',
        'First dance',
        'Party',
    ];

    protected $fillable = [
        'lead_id',
        'name',
        'alias',
        'partner_one_name',
        'partner_two_name',
        'reference_email',
        'partner_2_reference_email',
        'primary_phone',
        'secondary_phone',
        'nationality',
        'preferred_language',
        'address',
        'private_notes',
        'region',
        'locality',
        'event_date',
        'event_start_date',
        'event_end_date',
        'estimated_guest_count',
        'final_guest_count',
        'budget_amount',
        'status',
        'logistics_notes',
        'cover_image_path',
        'rsvp_configuration',
        'rsvp_submissions_locked',
        'website_json',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_start_date' => 'date',
        'event_end_date' => 'date',
        'budget_amount' => 'decimal:2',
        'rsvp_configuration' => 'array',
        'rsvp_submissions_locked' => 'boolean',
        'website_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (blank($project->alias)) {
                $project->alias = static::generateUniqueAlias($project->name);
            }

            $project->normalizeEventDates();
        });

        static::saving(function (Project $project): void {
            $project->normalizeEventDates();
            $project->shouldInitializeDefaultTimeline = blank($project->getOriginal('event_date')) && filled($project->event_date);
        });

        static::created(function (Project $project): void {
            $project->syncChecklistOptionsFromTemplates();
        });

        static::saved(function (Project $project): void {
            if ($project->projectChecklistOptions()->doesntExist()) {
                $project->syncChecklistOptionsFromTemplates();
            }

            if ($project->wasChanged('event_date')) {
                $project->refreshChecklistOptionDueDates();
            }

            if ($project->shouldInitializeDefaultTimeline) {
                $project->initializeDefaultTimelineForEventDate();
            }
        });
    }

    public function normalizeEventDates(): void
    {
        if (! $this->event_date && $this->event_start_date) {
            $this->event_date = $this->event_start_date;
        }

        if (! $this->event_date) {
            return;
        }

        if (! $this->event_start_date) {
            $this->event_start_date = $this->event_date;
        }

        if (! $this->event_end_date) {
            $this->event_end_date = $this->event_start_date;
        }
    }

    public function getEventSpansMultipleDaysAttribute(): bool
    {
        return (bool) (
            $this->event_start_date
            && $this->event_end_date
            && ! $this->event_start_date->isSameDay($this->event_end_date)
        );
    }

    public function initializeDefaultTimelineForEventDate(): int
    {
        if (! $this->event_date || $this->projectTimelineItems()->exists()) {
            return 0;
        }

        $rows = collect(self::DEFAULT_TIMELINE_TITLES)
            ->values()
            ->map(fn (string $title, int $index): array => [
                'timeline_date' => $this->event_date,
                'start_time' => null,
                'end_time' => null,
                'sunset_time' => null,
                'is_surprise' => false,
                'cover_activity' => false,
                'cover_activity_type' => null,
                'location' => null,
                'location_plan_b' => null,
                'supplier_id' => null,
                'title' => $title,
                'description' => null,
                'has_extended_description' => false,
                'extended_description' => null,
                'notes' => null,
                'image_paths' => [],
                'sort_order' => $index + 1,
            ]);

        $rows->each(fn (array $row) => $this->projectTimelineItems()->create($row));

        $this->unsetRelation('projectTimelineItems');

        return $rows->count();
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
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

    public function seatingPlans(): HasMany
    {
        return $this->hasMany(ProjectSeatingPlan::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supplierCommunications(): HasMany
    {
        return $this->hasMany(ProjectSupplierCommunication::class);
    }

    public function rsvpConfigurationFields(): array
    {
        $fields = $this->rsvp_configuration['fields'] ?? null;

        if (! is_array($fields) || $fields === []) {
            return static::defaultRsvpConfiguration()['fields'];
        }

        return collect($fields)
            ->map(fn (array $field): array => [
                'key' => trim((string) ($field['key'] ?? Str::uuid())),
                'enabled' => (bool) ($field['enabled'] ?? false),
                'label' => trim((string) ($field['label'] ?? 'RSVP field')),
                'help_text' => trim((string) ($field['help_text'] ?? '')),
                'type' => in_array(($field['type'] ?? 'text'), ['text', 'select', 'checkbox'], true) ? $field['type'] : 'text',
                'response_scope' => in_array(($field['response_scope'] ?? null), ['aggregate', 'per_guest'], true)
                    ? $field['response_scope']
                    : (($field['key'] ?? null) === 'food_allergies' ? 'per_guest' : 'aggregate'),
                'options' => array_values(array_filter(array_map(
                    fn ($option): string => trim((string) $option),
                    is_array($field['options'] ?? null) ? $field['options'] : preg_split('/\r\n|\r|\n|,/', (string) ($field['options'] ?? ''))
                ))),
                'order' => (int) ($field['order'] ?? 0),
            ])
            ->sortBy('order')
            ->values()
            ->all();
    }

    public static function defaultRsvpConfiguration(): array
    {
        return [
            'fields' => [
                [
                    'key' => 'ceremony_attendance',
                    'enabled' => true,
                    'label' => 'Ceremony attendance',
                    'help_text' => 'Confirm whether this guest will attend the ceremony.',
                    'type' => 'select',
                    'response_scope' => 'aggregate',
                    'options' => ['Yes', 'No'],
                    'order' => 1,
                ],
                [
                    'key' => 'food_allergies',
                    'enabled' => true,
                    'label' => 'Food Allergies',
                    'help_text' => 'List allergies, intolerances or dietary restrictions.',
                    'type' => 'text',
                    'response_scope' => 'per_guest',
                    'options' => [],
                    'order' => 2,
                ],
                [
                    'key' => 'mobility_issues',
                    'enabled' => true,
                    'label' => 'Mobility issues',
                    'help_text' => 'Tell us if accessibility support is needed.',
                    'type' => 'text',
                    'response_scope' => 'aggregate',
                    'options' => [],
                    'order' => 3,
                ],
                [
                    'key' => 'notes',
                    'enabled' => true,
                    'label' => 'Notes',
                    'help_text' => 'Any extra information for the planner.',
                    'type' => 'text',
                    'response_scope' => 'aggregate',
                    'options' => [],
                    'order' => 4,
                ],
                [
                    'key' => 'accommodation',
                    'enabled' => false,
                    'label' => 'Accommodation',
                    'help_text' => 'Ask if the guest needs overnight accommodation.',
                    'type' => 'checkbox',
                    'response_scope' => 'aggregate',
                    'options' => [],
                    'order' => 5,
                ],
                [
                    'key' => 'makeup_interest',
                    'enabled' => false,
                    'label' => 'Interested in makeup',
                    'help_text' => 'Ask if the guest is interested in makeup service.',
                    'type' => 'checkbox',
                    'response_scope' => 'aggregate',
                    'options' => [],
                    'order' => 6,
                ],
                [
                    'key' => 'transfer_from',
                    'enabled' => false,
                    'label' => 'Interested in transfer from',
                    'help_text' => 'Ask where the guest would need transport from.',
                    'type' => 'text',
                    'response_scope' => 'aggregate',
                    'options' => [],
                    'order' => 7,
                ],
                [
                    'key' => 'extra_activities',
                    'enabled' => false,
                    'label' => 'Interested in extra activities',
                    'help_text' => 'Trips, tastings or other extra activities.',
                    'type' => 'checkbox',
                    'response_scope' => 'aggregate',
                    'options' => [],
                    'order' => 8,
                ],
                [
                    'key' => 'menu_choice',
                    'enabled' => false,
                    'label' => 'Menu choice',
                    'help_text' => 'Let guests choose between available menus.',
                    'type' => 'select',
                    'response_scope' => 'aggregate',
                    'options' => ['Menu 1', 'Menu 2', 'Menu 3'],
                    'order' => 9,
                ],
            ],
        ];
    }

    public function websiteConfiguration(): array
    {
        return array_replace_recursive(static::defaultWebsiteConfiguration($this), is_array($this->website_json) ? $this->website_json : []);
    }

    public static function generateUniqueAlias(string $name, ?int $ignoreProjectId = null): string
    {
        $baseAlias = Str::slug($name) ?: 'event';
        $alias = $baseAlias;
        $suffix = 2;

        while (static::query()
            ->when($ignoreProjectId, fn ($query) => $query->whereKeyNot($ignoreProjectId))
            ->where('alias', $alias)
            ->exists()) {
            $alias = $baseAlias . '-' . $suffix;
            $suffix++;
        }

        return $alias;
    }

    public static function defaultWebsiteConfiguration(?Project $project = null): array
    {
        $partners = trim(collect([$project?->partner_one_name, $project?->partner_two_name])->filter()->implode(' & '));
        $date = $project?->event_date?->format('F j, Y') ?? '';
        $location = trim(collect([$project?->locality, $project?->region])->filter()->implode(', '));

        return [
            'settings' => [
                'published' => true,
                'palette_preset' => 'champagne_rose',
                'accent_color' => '#b9838f',
                'background_color' => '#fbf6f1',
                'text_color' => '#3f3434',
                'font_preset' => 'allura',
                'signature' => $partners,
                'footer_text' => 'With love, ' . ($partners ?: 'the couple'),
            ],
            'home' => [
                'enabled' => true,
                'title' => $partners ?: ($project?->name ?? ''),
                'eyebrow' => "We're getting married",
                'subtitle' => 'We cannot wait to celebrate with you.',
                'date' => $date,
                'location' => $location,
                'hero_image' => $project?->cover_image_path ? '/storage/' . $project->cover_image_path : '',
                'hero_images' => $project?->cover_image_path ? [['url' => '/storage/' . $project->cover_image_path, 'caption' => '']] : [],
                'intro_title' => $partners,
                'intro_text' => '',
                'intro_image' => '',
            ],
            'schedule' => ['enabled' => false, 'title' => 'Schedule', 'intro' => '', 'items' => []],
            'travel' => ['enabled' => false, 'title' => 'Travel', 'intro' => '', 'image' => '', 'hotels' => [], 'transportation' => []],
            'registry' => ['enabled' => false, 'title' => 'Registry', 'intro' => '', 'button_label' => 'View registry', 'url' => ''],
            'wedding_party' => ['enabled' => false, 'title' => 'Wedding Party', 'intro' => '', 'people' => []],
            'gallery' => ['enabled' => false, 'title' => 'Gallery', 'intro' => '', 'images' => []],
            'things_to_do' => ['enabled' => false, 'title' => 'Things To Do', 'intro' => '', 'items' => []],
            'faqs' => ['enabled' => false, 'title' => 'FAQs', 'items' => []],
            'events' => ['enabled' => false, 'title' => 'Welcome Party & Wedding Event', 'intro' => '', 'items' => []],
            'rsvp' => ['enabled' => true, 'title' => 'RSVP', 'intro' => 'Please use the personal RSVP link you received with your invitation.'],
        ];
    }

    public function syncChecklistOptionsFromTemplates(): void
    {
        if (! $this->exists) {
            return;
        }

        $this->loadMissing('categoryBudgets.supplierProposals');

        $categoryBudgets = $this->categoryBudgets->keyBy('category_id');
        $eventDate = $this->event_date;

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
                    'to_be_filled' => (bool) ($option['to_be_filled'] ?? false),
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

        $eventDate = $this->event_date;

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
