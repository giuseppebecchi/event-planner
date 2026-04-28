<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends Model
{
    public const TYPE_QUOTE = 'quote';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_SIGNED_CONTRACT = 'signed_contract';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_PAYMENT_RECEIPT = 'payment_receipt';
    public const TYPE_OTHER = 'other';

    public const TYPE_OPTIONS = [
        self::TYPE_QUOTE => 'Quote',
        self::TYPE_CONTRACT => 'Contract',
        self::TYPE_SIGNED_CONTRACT => 'Signed contract',
        self::TYPE_INVOICE => 'Invoice',
        self::TYPE_PAYMENT_RECEIPT => 'Payment receipt',
        self::TYPE_OTHER => 'Other',
    ];

    protected $fillable = [
        'project_id',
        'supplier_id',
        'category_budget_supplier_id',
        'title',
        'document_type',
        'type',
        'file_path',
        'description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function categoryBudgetSupplier(): BelongsTo
    {
        return $this->belongsTo(CategoryBudgetSupplier::class);
    }
}
