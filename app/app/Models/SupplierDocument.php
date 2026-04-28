<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDocument extends Model
{
    public const TYPE_OPTIONS = [
        'brochure' => 'Brochure',
        'price_list' => 'Price list',
        'rules' => 'Rules',
        'sample_contract' => 'Sample contract',
        'floor_plan' => 'Floor plan',
        'portfolio' => 'Portfolio',
        'other' => 'Other',
    ];

    protected $fillable = [
        'supplier_id',
        'title',
        'document_type',
        'file_path',
        'description',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
