<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSeatingPlan extends Model
{
    public const PLAN_TYPE_OPTIONS = [
        'ceremony' => 'Ceremony',
        'aperitivo' => 'Aperitivo',
        'lunch' => 'Lunch',
        'dinner' => 'Dinner',
        'party' => 'Party',
        'other' => 'Other',
    ];

    protected $fillable = [
        'project_id',
        'name',
        'plan_type',
        'notes',
        'background_image_path',
        'preview_image_path',
        'viewport_zoom',
        'viewport_x',
        'viewport_y',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'viewport_zoom' => 'float',
        'viewport_x' => 'integer',
        'viewport_y' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(ProjectTable::class)->orderBy('sort_order');
    }
}
