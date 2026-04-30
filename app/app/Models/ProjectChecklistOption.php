<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectChecklistOption extends Model
{
    public const ASSIGNED_TO_OPTIONS = [
        'admin' => 'Admin',
        'client' => 'Client',
        'supplier' => 'Supplier',
        'none' => 'None',
    ];

    protected $fillable = [
        'project_id',
        'supplier_id',
        'category_budget_id',
        'checkbox_id',
        'order',
        'title',
        'details',
        'default',
        'anticipation',
        'assigned_to',
        'due_date',
        'enabled',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'supplier_id' => 'integer',
        'category_budget_id' => 'integer',
        'checkbox_id' => 'integer',
        'order' => 'integer',
        'default' => 'boolean',
        'due_date' => 'date',
        'enabled' => 'boolean',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function categoryBudget(): BelongsTo
    {
        return $this->belongsTo(CategoryBudget::class);
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class, 'checkbox_id');
    }

    public static function normalizeAssignedTo(mixed $value): string
    {
        $assignedTo = is_string($value) ? trim(strtolower($value)) : 'none';

        return array_key_exists($assignedTo, self::ASSIGNED_TO_OPTIONS)
            ? $assignedTo
            : 'none';
    }
}
