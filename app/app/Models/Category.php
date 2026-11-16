<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'label',
        'label_it',
        'main',
        'order',
        'default_supplier_id',
        'auto_confirm_default_supplier',
    ];

    protected $casts = [
        'main' => 'boolean',
        'order' => 'integer',
        'default_supplier_id' => 'integer',
        'auto_confirm_default_supplier' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', fn (Builder $query): Builder => $query->orderBy('order')->orderBy('label'));
    }

    public function categoryBudgets(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
    }

    public function defaultSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }

    public function supplierProposals(): HasMany
    {
        return $this->hasMany(CategoryBudgetSupplier::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->withTimestamps();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function strategicInfos(): HasMany
    {
        return $this->hasMany(StrategicInfo::class);
    }
}
