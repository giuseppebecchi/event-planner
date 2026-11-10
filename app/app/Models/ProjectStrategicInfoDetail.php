<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStrategicInfoDetail extends Model
{
    protected $fillable = ['day_key', 'content', 'image_paths', 'status', 'completed_at'];

    protected $casts = ['image_paths' => 'array', 'completed_at' => 'datetime'];

    public function info(): BelongsTo
    {
        return $this->belongsTo(ProjectStrategicInfo::class, 'project_strategic_info_id');
    }

    public function hasCompiledContent(): bool
    {
        return trim(strip_tags(html_entity_decode((string) $this->content))) !== ''
            || collect($this->image_paths ?? [])->filter()->isNotEmpty();
    }

    public function displayState(): string
    {
        return match ($this->status) {
            ProjectStrategicInfo::STATUS_COMPLETED => 'completed',
            ProjectStrategicInfo::STATUS_NOT_REQUIRED => 'not_required',
            default => $this->hasCompiledContent() ? 'in_progress' : 'empty',
        };
    }

    public function dateLabel(): string
    {
        return $this->day_key === 'common'
            ? 'All supplier event days'
            : \Illuminate\Support\Carbon::parse($this->day_key)->format('D j M Y');
    }

    public function getTitleAttribute(): string
    {
        return $this->info->title;
    }

    public function getCategoryAttribute(): ?Category
    {
        return $this->info->category;
    }
}
