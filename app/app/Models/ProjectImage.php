<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    public const CATEGORY_OPTIONS = [
        'exterior' => 'Exterior',
        'interior' => 'Interior',
        'ceremony_area' => 'Ceremony area',
        'dinner_setup' => 'Dinner setup',
        'panorama' => 'Panorama',
        'rooms' => 'Rooms',
        'details' => 'Details',
        'other' => 'Other',
    ];

    protected $fillable = [
        'project_id',
        'project_moodboard_id',
        'supplier_id',
        'image_path',
        'description',
        'image_category',
        'is_client_visible',
    ];

    protected $casts = [
        'is_client_visible' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function moodboard(): BelongsTo
    {
        return $this->belongsTo(ProjectMoodboard::class, 'project_moodboard_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
