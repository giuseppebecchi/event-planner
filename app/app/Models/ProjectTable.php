<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTable extends Model
{
    public const TABLE_TYPE_OPTIONS = [
        'round' => 'Round',
        'oval' => 'Oval',
        'rectangular' => 'Rectangular',
        'square' => 'Square',
        'long_table' => 'Long table',
        'chair_row' => 'Chair row',
    ];

    public const CHAIR_ROW_SPACING = 26;
    public const CHAIR_ROW_SIDE_PADDING = 20;
    public const CHAIR_ROW_HEIGHT = 24;
    public const LONG_TABLE_DEFAULT_LENGTH = 800;
    public const LONG_TABLE_WIDTH = 100;
    public const LONG_TABLE_CURVE_TYPES = [
        'subtle' => 'Subtle',
        'medium' => 'Medium',
        'strong' => 'Pronounced',
    ];
    public const CHAIR_ROW_CURVE_TYPES = [
        'none' => 'None',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    protected $fillable = [
        'project_seating_plan_id',
        'name',
        'center_x',
        'center_y',
        'rotation',
        'table_type',
        'primary_dimension',
        'secondary_dimension',
        'seats_total',
        'seats_by_side_json',
        'curve_count',
        'curve_type',
        'guest_assignments_json',
        'sort_order',
    ];

    protected $casts = [
        'center_x' => 'decimal:2',
        'center_y' => 'decimal:2',
        'rotation' => 'decimal:2',
        'primary_dimension' => 'decimal:2',
        'secondary_dimension' => 'decimal:2',
        'seats_total' => 'integer',
        'seats_by_side_json' => 'array',
        'curve_count' => 'integer',
        'guest_assignments_json' => 'array',
        'sort_order' => 'integer',
    ];

    public function seatingPlan(): BelongsTo
    {
        return $this->belongsTo(ProjectSeatingPlan::class, 'project_seating_plan_id');
    }

    public function seatCount(): int
    {
        if (in_array($this->table_type, ['round', 'oval', 'chair_row'], true)) {
            return (int) ($this->seats_total ?? 0);
        }

        return collect($this->seats_by_side_json ?? [])
            ->only(['top', 'right', 'bottom', 'left'])
            ->sum(fn ($count): int => (int) $count);
    }

    public static function chairRowWidth(int $seats): float
    {
        return max(44, ($seats * self::CHAIR_ROW_SPACING) + (self::CHAIR_ROW_SIDE_PADDING * 2));
    }

    public function assignedCount(): int
    {
        return collect($this->guest_assignments_json ?? [])
            ->filter()
            ->count();
    }
}
