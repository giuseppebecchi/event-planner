<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'label',
        'label_it',
        'main',
        'order',
    ];

    protected $casts = [
        'main' => 'boolean',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', fn (Builder $query): Builder => $query->orderBy('order')->orderBy('label'));
    }

    public function categoryBudgets(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
    }

    public function supplierProposals(): HasMany
    {
        return $this->hasMany(CategoryBudgetSupplier::class);
    }
}
