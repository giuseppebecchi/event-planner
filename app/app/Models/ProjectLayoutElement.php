<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLayoutElement extends Model
{
    public const ELEMENT_TYPE_OPTIONS = [
        'text' => 'Text',
        'space' => 'Space',
    ];

    public const SHAPE_OPTIONS = [
        'rectangle' => 'Rectangle',
        'circle' => 'Circle',
    ];

    protected $fillable = [
        'project_seating_plan_id',
        'element_type',
        'shape',
        'label',
        'center_x',
        'center_y',
        'rotation',
        'width',
        'height',
        'background_color',
        'sort_order',
    ];

    protected $casts = [
        'center_x' => 'decimal:2',
        'center_y' => 'decimal:2',
        'rotation' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function seatingPlan(): BelongsTo
    {
        return $this->belongsTo(ProjectSeatingPlan::class, 'project_seating_plan_id');
    }
}
