<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;

class OrderController extends BaseController
{
    protected string $modelClass = Order::class;

    // ✅ campi “leggeri” per lista (index)
    protected array $indexFields = [
        'orderid',
        'iduniq',
        'status',
        'status_checkout',
        'company',
        'domain',
        'total',
        'subtotal',
        'discount',
        'extra_taxes',
        'invoice_id',
        'exported',
        'integrated',
        'date.orderdate',
        'date.servicedate',
        'client_data.bill_to.email',
        'client_data.bill_to.mobile',
        'payment.payment_id',
    ];

    // ✅ dettaglio (show)
    protected array $detailFields = [
        'orderid',
        'iduniq',
        'session_id',
        'status',
        'status_checkout',
        'ip',
        'ip_info',
        'company',
        'company_type',
        'domain',
        'version',
        'subtotal',
        'total',
        'vat_value',
        'discount',
        'taxes',
        'extra_taxes',
        'user_total',
        'user_subtotal',
        'user_vat_value',
        'vat_rate',
        'date',
        'client_data',
        'cart',
        'payment',
        'otp',
        'privacy',
        'abuse_score',
        'shipping',
        'commission',
        'exportdata',
        'exported',
        'integrated',
        'invoice_id',
        'cart_id',
        'who_id',
        'where_id',
        'agendaId',
        'notes',
        'notification',
        'ecommerce_analytics',
    ];

    // 🔤 ricerche testuali (regex i)
    protected array $searchable = [
        'orderid',
        'iduniq',
        'company',
        'domain',
        'commercial_agent_code',
        'client_data.bill_to.email',
        'client_data.bill_to.last_name',
        'client_data.patient.last_name',
        'ip_info.city',
        'ip_info.regionName',
        'payment.payment_id',
        'otp.medium',
    ];

    // 🔢 ricerche esatte (where =)
    protected array $exactSearchable = [
        'status',
        'status_checkout',
        'exported',
        'integrated',
        'invoice_id',
        'client_data.bill_to.mobile',
        'client_data.patient.mobile',
        'ip_info.countryCode',
        'ip_info.timezone',
        'company_type',
        'ecommerce_analytics',
    ];

    // (opzionale) scrittura: di solito sugli ordini conviene limitare molto
    protected array $writeFields = [
        'notes',
        'status',
        'status_checkout',
        'exported',
        'integrated',
        'payment',
        'notification',
    ];

    protected array $storeRules = [
        'status' => 'required|string',
        'company' => 'required|string',
        'total' => 'required|numeric',
        'date' => 'nullable|array',
        'client_data' => 'nullable|array',
        'cart' => 'nullable|array',
    ];

    protected array $updateRules = [
        'notes' => 'nullable|string',
        'status' => 'nullable|string',
        'status_checkout' => 'nullable|string',
        'exported' => 'nullable|boolean',
        'integrated' => 'nullable|boolean',
        'payment' => 'nullable|array',
        'notification' => 'nullable|array',
    ];
}
