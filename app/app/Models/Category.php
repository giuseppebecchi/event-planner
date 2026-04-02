<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
}
