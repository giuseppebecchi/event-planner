<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
            $this->syncForProject($project, $previousDefaultValue);
        });

        return true;
    }

    public function syncForProject(Project $project, ?string $previousDefaultValue = null): void
    {
        DB::transaction(function () use ($project, $previousDefaultValue): void {
            Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $instances = $project->strategicInfos()->where('strategic_info_id', $this->id)->with('details')->get();
            if ($instances->isEmpty()) {
                $instances->push($project->strategicInfos()->create([
                    'strategic_info_id' => $this->id, 'category_id' => $this->category_id, 'title' => $this->title,
                ]));
            }
            foreach ($instances as $instance) {
                if ($instance->isPristine($previousDefaultValue)) {
                    $instance->fill(['category_id' => $this->category_id, 'title' => $this->title]);
                    if ($instance->isDirty()) {
                        $instance->save();
                    }
                    $detail = $instance->details()->firstOrNew(['day_key' => 'common']);
                    $detail->fill(['content' => $this->default_value, 'status' => ProjectStrategicInfo::STATUS_DRAFT]);
                    if ($detail->isDirty()) {
                        $detail->save();
                    }
                }
            }
            $supplierIds = $project->categoryBudgetSuppliers()->where('category_id', $this->category_id)
                ->where('proposal_status', CategoryBudgetSupplier::STATUS_CONFIRMED)->whereNotNull('supplier_id')
                ->orderBy('supplier_id')->distinct()->pluck('supplier_id');
            foreach ($supplierIds as $supplierId) {
                if ($instances->contains('supplier_id', (int) $supplierId)) {
                    continue;
                }
                $unassigned = $instances->firstWhere('supplier_id', null);
                if ($unassigned) {
                    $unassigned->update(['supplier_id' => $supplierId]);
                    continue;
                }
                $instance = $project->strategicInfos()->create([
                    'strategic_info_id' => $this->id, 'category_id' => $this->category_id,
                    'title' => $this->title, 'supplier_id' => $supplierId,
                ]);
                $instance->details()->create(['day_key' => 'common', 'content' => $this->default_value, 'status' => ProjectStrategicInfo::STATUS_DRAFT]);
                $instances->push($instance);
            }
        });
    }
}
