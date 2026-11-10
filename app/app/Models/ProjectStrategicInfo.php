<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ProjectStrategicInfo extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_NOT_REQUIRED = 'not_required';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'In progress',
        self::STATUS_COMPLETED => 'Complete',
        self::STATUS_NOT_REQUIRED => 'Do not fill',
    ];

    protected $fillable = [
        'project_id',
        'strategic_info_id',
        'category_id',
        'title',
        'supplier_id',
        'separate_by_day',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'strategic_info_id' => 'integer',
        'category_id' => 'integer',
        'supplier_id' => 'integer',
        'separate_by_day' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function strategicInfo(): BelongsTo
    {
        return $this->belongsTo(StrategicInfo::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function hasCompiledContent(): bool
    {
        return $this->activeDetails()->contains(fn (ProjectStrategicInfoDetail $detail): bool => $detail->hasCompiledContent());
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProjectStrategicInfoDetail::class);
    }

    public function activeDetails(): Collection
    {
        return $this->details->filter(fn (ProjectStrategicInfoDetail $detail): bool => $this->separate_by_day
            ? $detail->day_key !== 'common'
            : $detail->day_key === 'common')->sortBy('day_key')->values();
    }

    public function commonDetail(): ?ProjectStrategicInfoDetail
    {
        return $this->details->firstWhere('day_key', 'common');
    }

    public function dayKeys(): array
    {
        $project = $this->project;
        $start = $project->event_start_date ?: $project->event_date;
        $end = $project->event_end_date ?: $start;
        if (! $start) {
            return [];
        }
        $keys = [];
        for ($date = $start->copy()->startOfDay(); $date->lte($end); $date->addDay()) {
            $keys[] = $date->toDateString();
        }
        $proposals = CategoryBudgetSupplier::query()->where('project_id', $this->project_id)
            ->where('category_id', $this->category_id)->where('supplier_id', $this->supplier_id)
            ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)->get();
        if ($proposals->isEmpty() || $proposals->contains(fn ($proposal): bool => empty($proposal->event_day_allocations))) {
            return $keys;
        }
        $selected = $proposals->flatMap(fn ($proposal): array => array_column($proposal->event_day_allocations ?? [], 'date'))->all();

        return array_values(array_intersect($keys, $selected));
    }

    public function contentVersion(): string
    {
        return hash('sha256', json_encode([$this->id, $this->separate_by_day, $this->details()->orderBy('day_key')->get()
            ->map->only(['day_key', 'content', 'image_paths', 'status', 'completed_at'])->all(), $this->dayKeys()]));
    }

    public function isPristine(?string $expectedDefaultValue = null): bool
    {
        $detail = $this->commonDetail();
        if ($this->separate_by_day || $this->details->contains(fn ($item): bool => $item->day_key !== 'common')
            || ($detail && ($detail->status !== self::STATUS_DRAFT || collect($detail->image_paths ?? [])->filter()->isNotEmpty()))) {
            return false;
        }

        if (! $this->hasCompiledContent()) {
            return true;
        }

        $expectedDefaultValue ??= $this->strategicInfo?->default_value;

        return filled($expectedDefaultValue)
            && trim((string) $detail?->content) === trim($expectedDefaultValue);
    }

    public function displayState(): string
    {
        $details = $this->activeDetails();
        if ($this->separate_by_day) {
            $dates = $this->dayKeys();
            $details = $details->whereIn('day_key', $dates);
            if (array_diff($dates, $details->pluck('day_key')->all())) {
                return $this->hasCompiledContent() ? 'in_progress' : 'empty';
            }
        }
        if ($details->isNotEmpty() && $details->every(fn ($detail): bool => $detail->status === self::STATUS_NOT_REQUIRED)) {
            return 'not_required';
        }
        if ($details->isNotEmpty() && $details->every(fn ($detail): bool => in_array($detail->status, [self::STATUS_COMPLETED, self::STATUS_NOT_REQUIRED], true))) {
            return 'completed';
        }

        return $this->hasCompiledContent() ? 'in_progress' : 'empty';
    }

    public function displayStateLabel(): string
    {
        return match ($this->displayState()) {
            'completed' => 'Complete',
            'not_required' => 'Do not fill',
            'in_progress' => 'In progress',
            default => 'To fill',
        };
    }
}
