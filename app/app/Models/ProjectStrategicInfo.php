<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'content',
        'image_paths',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'strategic_info_id' => 'integer',
        'category_id' => 'integer',
        'image_paths' => 'array',
        'completed_at' => 'datetime',
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
        $text = trim(strip_tags(html_entity_decode((string) $this->content)));

        return $text !== '' || collect($this->image_paths ?? [])->filter()->isNotEmpty();
    }

    public function isPristine(?string $expectedDefaultValue = null): bool
    {
        if ($this->status !== self::STATUS_DRAFT || collect($this->image_paths ?? [])->filter()->isNotEmpty()) {
            return false;
        }

        if (! $this->hasCompiledContent()) {
            return true;
        }

        $expectedDefaultValue ??= $this->strategicInfo?->default_value;

        return filled($expectedDefaultValue)
            && trim((string) $this->content) === trim($expectedDefaultValue);
    }

    public function displayState(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'completed',
            self::STATUS_NOT_REQUIRED => 'not_required',
            default => $this->hasCompiledContent() ? 'in_progress' : 'empty',
        };
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
