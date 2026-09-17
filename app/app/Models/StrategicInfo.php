<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategicInfo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'default_value',
        'order',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn (StrategicInfo $strategicInfo): bool => $strategicInfo->syncProjectInstances());

        static::deleted(function (StrategicInfo $strategicInfo): void {
            $strategicInfo->projectInstances()
                ->get()
                ->filter(fn (ProjectStrategicInfo $instance): bool => $instance->isPristine())
                ->each->delete();
        });

        static::restored(fn (StrategicInfo $strategicInfo): bool => $strategicInfo->syncProjectInstances());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function projectInstances(): HasMany
    {
        return $this->hasMany(ProjectStrategicInfo::class);
    }

    public function syncProjectInstances(): bool
    {
        $previousDefaultValue = $this->wasChanged('default_value')
            ? $this->getOriginal('default_value')
            : $this->default_value;

        Project::query()->select('id')->eachById(function (Project $project) use ($previousDefaultValue): void {
            $instance = ProjectStrategicInfo::query()->firstOrNew([
                'project_id' => $project->id,
                'strategic_info_id' => $this->id,
            ]);

            if (! $instance->exists || $instance->isPristine($previousDefaultValue)) {
                $instance->fill([
                    'category_id' => $this->category_id,
                    'title' => $this->title,
                    'content' => $this->default_value,
                ]);
            }

            if (! $instance->exists) {
                $instance->status = ProjectStrategicInfo::STATUS_DRAFT;
            }

            if ($instance->isDirty()) {
                $instance->save();
            }
        });

        return true;
    }
}
