<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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

    public const TIME_FORMAT_OPTIONS = [
        '12h' => '12h',
        '24h' => '24h',
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
        'first_name',
        'last_name',
        'email',
        'phone',
        'secondary_first_name',
        'secondary_last_name',
        'secondary_email',
        'secondary_phone',
        'nationality',
        'city',
        'preferred_language',
        'address',
        'internal_notes',
        'region',
        'locality',
        'location_request_type',
        'venue_id',
        'venue',
        'ceremony_type',
        'ceremony_details',
        'ceremony_location',
        'estimated_timings',
        'additional_events',
        'wedding_period',
        'style_description',
        'event_date',
        'event_start_date',
        'event_end_date',
        'time_format',
        'estimated_guest_count',
        'final_guest_count',
        'budget_amount',
        'venue_included_in_budget',
        'status',
        'logistics_notes',
        'cover_image_path',
        'recap_left_rail_image_path',
        'rsvp_configuration',
        'rsvp_submissions_locked',
        'website_json',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_start_date' => 'date',
        'event_end_date' => 'date',
        'venue_id' => 'integer',
        'budget_amount' => 'decimal:2',
        'venue_included_in_budget' => 'boolean',
        'rsvp_configuration' => 'array',
        'rsvp_submissions_locked' => 'boolean',
        'website_json' => 'array',
    ];

    public function getTimeFormatAttribute(?string $value): string
    {
        return array_key_exists((string) $value, self::TIME_FORMAT_OPTIONS) ? (string) $value : '12h';
    }

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

    public function mainContactName(): string
    {
        return trim(collect([$this->first_name, $this->last_name])->filter()->implode(' '));
    }

    public function secondaryContactName(): string
    {
        return trim(collect([$this->secondary_first_name, $this->secondary_last_name])->filter()->implode(' '));
    }

    public function coupleNames(): string
    {
        return collect([$this->mainContactName(), $this->secondaryContactName()])
            ->filter()
            ->implode(' & ');
    }

    public function getEventSpansMultipleDaysAttribute(): bool
    {
        return (bool) (
            $this->event_start_date
            && $this->event_end_date
            && ! $this->event_start_date->isSameDay($this->event_end_date)
        );
    }

    public function timeDisplayFormat(): string
    {
        return $this->time_format === '24h' ? 'H:i' : 'g:i A';
    }

    public function formatTimeForDisplay(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format($this->timeDisplayFormat());
        }

        $value = (string) $value;

        try {
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value) === 1) {
                $format = substr_count($value, ':') === 2 ? 'H:i:s' : 'H:i';

                return Carbon::createFromFormat($format, $value)->format($this->timeDisplayFormat());
            }

            return Carbon::parse($value)->format($this->timeDisplayFormat());
        } catch (\Throwable) {
            return $value;
        }
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

    public function resetDefaultTimelineForEventDate(bool $deleteImages = true): array
    {
        if (! $this->event_date) {
            return [
                'deleted' => 0,
                'created' => 0,
                'missing_event_date' => true,
            ];
        }

        $items = $this->projectTimelineItems()->get();
        $deleted = $items->count();

        if ($deleteImages) {
            $imagePaths = $items
                ->flatMap(fn (ProjectTimeline $item): array => $item->image_paths ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($imagePaths) {
                Storage::disk('public')->delete($imagePaths);
            }
        }

        $this->projectTimelineItems()->delete();
        $this->unsetRelation('projectTimelineItems');

        return [
            'deleted' => $deleted,
            'created' => $this->initializeDefaultTimelineForEventDate(),
            'missing_event_date' => false,
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function venueRecord(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'venue_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function initBudget(): array
    {
        $lead = $this->lead;

        $venueStats = $this->syncVenueBudgetFromVenueId();

        if (! $lead) {
            return [
                'project_id' => $this->id,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                ...$venueStats,
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
            'supplier_proposals_created' => 0,
            'supplier_proposals_updated' => 0,
            'no_lead' => false,
            ...$venueStats,
        ];

        $defaultWeddingSupplier = $this->defaultWeddingSupplier();
        $weddingPlannerCategoryId = $defaultWeddingSupplier?->category_id
            ?: $this->weddingPlannerCategoryId($categories);
        $weddingPlannerAmount = $this->leadWeddingPlannerBudgetAmount($lead);

        $rows = collect($lead->budget_vendors ?? [])
            ->reject(fn (array $row): bool => $this->isWeddingPlannerBudgetRow($row, $weddingPlannerCategoryId))
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
            ->when(
                $weddingPlannerCategoryId && $weddingPlannerAmount > 0,
                fn (Collection $rows): Collection => $rows->push([
                    'category_id' => $weddingPlannerCategoryId,
                    'amount' => $weddingPlannerAmount,
                    'notes' => 'Wedding planner fee',
                ])
            )
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

            if (
                $defaultWeddingSupplier
                && $weddingPlannerCategoryId
                && (int) $row['category_id'] === (int) $weddingPlannerCategoryId
                && (float) $row['amount'] > 0
            ) {
                $proposal = $budget->supplierProposals()->firstOrNew([
                    'supplier_id' => $defaultWeddingSupplier->id,
                ]);
                $proposalIsNew = ! $proposal->exists;

                $proposal->fill([
                    'supplier_id' => $defaultWeddingSupplier->id,
                    'responded_at' => now(),
                    'availability_status' => 'available',
                    'scouting_status' => 'chosen',
                    'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
                    'proposed_amount' => (float) $row['amount'],
                    'cost_items_json' => [[
                        'label' => 'Wedding planner fee',
                        'amount' => (float) $row['amount'],
                    ]],
                    'proposal_summary' => 'Automatically created from the lead wedding planner budget.',
                    'confirmed_at' => $proposal->confirmed_at ?? now(),
                ]);

                if ($proposal->isDirty()) {
                    $proposal->save();
                    $stats[$proposalIsNew ? 'supplier_proposals_created' : 'supplier_proposals_updated']++;
                }
            }
        }

        $this->syncChecklistOptionsFromTemplates();

        return $stats;
    }

    public function syncVenueBudgetFromVenueId(): array
    {
        $stats = [
            'venue_budget_created' => 0,
            'venue_budget_updated' => 0,
            'venue_supplier_proposals_created' => 0,
            'venue_supplier_proposals_updated' => 0,
        ];

        if (blank($this->venue_id)) {
            return $stats;
        }

        $venue = $this->venueRecord()->first();

        if (! $venue) {
            return $stats;
        }

        $category = $this->venueBudgetCategory();

        if (! $category) {
            return $stats;
        }

        $budget = $this->categoryBudgets()->firstOrNew([
            'category_id' => $category->id,
        ]);
        $budgetIsNew = ! $budget->exists;

        if (blank($budget->budget_status)) {
            $budget->budget_status = CategoryBudget::STATUS_CONFIRMED;
        }

        if (blank($budget->notes)) {
            $budget->notes = 'Automatically created from the project venue.';
        }

        if ($budget->isDirty()) {
            $budget->save();
            $stats[$budgetIsNew ? 'venue_budget_created' : 'venue_budget_updated']++;
        }

        $proposal = $budget->supplierProposals()->firstOrNew([
            'supplier_id' => $venue->id,
        ]);
        $proposalIsNew = ! $proposal->exists;
        $amount = $venue->loc_rental_fee !== null ? (float) $venue->loc_rental_fee : null;

        $proposal->fill([
            'supplier_id' => $venue->id,
            'responded_at' => $proposal->responded_at ?? now(),
            'availability_status' => 'available',
            'scouting_status' => 'chosen',
            'proposal_status' => CategoryBudgetSupplier::STATUS_CONFIRMED,
            'proposed_amount' => $proposal->proposed_amount ?? $amount,
            'cost_items_json' => $proposal->cost_items_json ?: ($amount !== null ? [[
                'label' => 'Venue rental fee',
                'amount' => $amount,
            ]] : null),
            'proposal_summary' => $proposal->proposal_summary ?: 'Automatically approved from the project venue.',
            'confirmed_at' => $proposal->confirmed_at ?? now(),
        ]);

        if ($proposal->isDirty()) {
            $proposal->save();
            $stats[$proposalIsNew ? 'venue_supplier_proposals_created' : 'venue_supplier_proposals_updated']++;
        }

        return $stats;
    }

    protected function venueBudgetCategory(): ?Category
    {
        return Category::query()->find(Supplier::LOCATION_CATEGORY_ID)
            ?: Category::query()
                ->where(function ($query): void {
                    $query
                        ->where('label', 'Venue')
                        ->orWhere('label_it', 'Venue');
                })
                ->first();
    }

    protected function defaultWeddingSupplier(): ?Supplier
    {
        $supplierId = (int) config('services.default_wedding_id');

        if ($supplierId <= 0) {
            return null;
        }

        return Supplier::query()->find($supplierId);
    }

    protected function weddingPlannerCategoryId(Collection $categories): ?int
    {
        $category = $categories->first(fn (Category $category): bool => $this->isWeddingPlannerCategory($category));

        return $category?->id;
    }

    protected function leadWeddingPlannerBudgetAmount(Lead $lead): float
    {
        $plannerTotal = collect($lead->budget_wedding_planner ?? [])
            ->sum(fn (array $row): float => blank($row['amount'] ?? null) ? 0.0 : (float) str_replace(',', '.', (string) $row['amount']));

        $selectedExtrasTotal = collect([
            ...$this->selectedLeadBudgetRows($lead->budget_wedding_planner_extra_services),
            ...$this->selectedLeadBudgetRows($lead->budget_wedding_planner_special_packages),
        ])->sum(fn (array $row): float => blank($row['amount'] ?? null) ? 0.0 : (float) str_replace(',', '.', (string) $row['amount']));

        return $plannerTotal + $selectedExtrasTotal;
    }

    protected function selectedLeadBudgetRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn (array $row): bool => (bool) ($row['add_to_budget'] ?? false))
            ->values()
            ->all();
    }

    protected function isWeddingPlannerBudgetRow(array $row, ?int $weddingPlannerCategoryId): bool
    {
        if ($weddingPlannerCategoryId && (int) ($row['category_id'] ?? 0) === (int) $weddingPlannerCategoryId) {
            return true;
        }

        $label = mb_strtolower(trim((string) ($row['label'] ?? '')));

        return in_array($label, ['wedding planner', 'wedding planning'], true);
    }

    protected function isWeddingPlannerCategory(Category $category): bool
    {
        return in_array(mb_strtolower(trim((string) $category->label)), ['wedding planner', 'wedding planning'], true)
            || in_array(mb_strtolower(trim((string) $category->label_it)), ['wedding planner', 'wedding planning'], true);
    }

    public function categoryBudgets(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
    }

    public function categoryBudgetSuppliers(): HasMany
    {
        return $this->hasMany(CategoryBudgetSupplier::class);
    }

    public function confirmedVenueProposal(): ?CategoryBudgetSupplier
    {
        return $this->categoryBudgetSuppliers()
            ->with(['category', 'supplier'])
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)
            ->where(function ($query): void {
                $query
                    ->where('category_id', Supplier::LOCATION_CATEGORY_ID)
                    ->orWhereHas('category', function ($categoryQuery): void {
                        $categoryQuery
                            ->where('label_it', 'Location')
                            ->orWhere('label', 'Venue');
                    });
            })
            ->latest('confirmed_at')
            ->latest('updated_at')
            ->first();
    }

    public function displayLocationLabel(): string
    {
        $venueName = $this->confirmedVenueProposal()?->supplier?->name;

        if (filled($venueName)) {
            return $venueName;
        }

        return collect([$this->locality, $this->region])
            ->filter()
            ->implode(', ') ?: 'Venue to be defined';
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
        $partners = $project?->coupleNames() ?: '';
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
                    'insert_into_recap' => (bool) ($option['insert_into_recap'] ?? false),
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
