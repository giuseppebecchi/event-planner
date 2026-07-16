<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMoodboard extends Model
{
    public const SOURCE_UPLOAD = 'upload';
    public const SOURCE_PINTEREST = 'pinterest';
    public const SOURCE_PDF = 'pdf';

    protected $fillable = [
        'project_id',
        'title',
        'board_type',
        'source_type',
        'pinterest_board_url',
        'pdf_file_path',
        'pdf_original_name',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class);
    }
}
