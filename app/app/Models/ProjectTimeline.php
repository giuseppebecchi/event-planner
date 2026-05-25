<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimeline extends Model
{
    protected $table = 'project_timeline';

    protected $fillable = [
        'project_id',
        'supplier_id',
        'timeline_date',
        'start_time',
        'end_time',
        'sunset_time',
        'is_surprise',
        'cover_activity',
        'cover_activity_type',
        'location',
        'location_plan_b',
        'title',
        'description',
        'has_extended_description',
        'extended_description',
        'notes',
        'image_paths',
        'sort_order',
    ];

    protected $casts = [
        'timeline_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'sunset_time' => 'datetime:H:i',
        'is_surprise' => 'boolean',
        'cover_activity' => 'boolean',
        'has_extended_description' => 'boolean',
        'image_paths' => 'array',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
