<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    protected $fillable = [
        'title',
        'category_id',
        'options',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'options' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function projectOptions(): HasMany
    {
        return $this->hasMany(ProjectChecklistOption::class, 'checkbox_id');
    }
}
