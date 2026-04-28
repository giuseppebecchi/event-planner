<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_PAID = 'paid';
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_OPTIONS = [
        self::STATUS_PAID => 'Paid',
        self::STATUS_UNPAID => 'Unpaid',
    ];

    protected $fillable = [
        'project_id',
        'supplier_id',
        'category_budget_supplier_id',
        'reason',
        'amount',
        'due_date',
        'payment_status',
        'paid_at',
        'invoice_reference',
        'payment_receipt_document_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
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

    public function paymentReceiptDocument(): BelongsTo
    {
        return $this->belongsTo(ProjectDocument::class, 'payment_receipt_document_id');
    }
}
