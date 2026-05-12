<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierImage extends Model
{
    public const TYPE_OPTIONS = [
        'hero' => 'Hero',
        'gallery' => 'Gallery',
        'portfolio' => 'Portfolio',
        'venue_spaces' => 'Venue spaces',
        'rooms' => 'Rooms',
        'ceremony' => 'Ceremony',
        'food' => 'Food',
        'details' => 'Details',
        'other' => 'Other',
    ];

    protected $fillable = [
        'supplier_id',
        'title',
        'image_type',
        'image_path',
        'description',
        'is_client_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_client_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
