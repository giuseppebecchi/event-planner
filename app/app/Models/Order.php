<?php

namespace App\Models;

class Order extends Base
{
    protected $collection = 'orders';

    protected $fillable = [
        'querystring',
        'session_id',
        'status',
        'ip',
        'subtotal',
        'total',
        'vat_value',
        'discount',
        'taxes',
        'extra_taxes',
        'user_total',
        'user_subtotal',
        'user_vat_value',
        'date',
        'client_data',
        'notes',
        'version',
        'domain',
        'company',
        'cart',
        'ip_info',
        'company_type',
        'commercial_agent_code',
        'group',
        'time_confirmation',
        'integrated',
        'shipping',
        'vat_rate',
        'payment_types_stats_before',
        'payment_types_stats_after',
        'payment_types_stats_after_function',
        'abuse_score',
        'privacy',
        'iduniq',
        'payment',
        'otp',
        'notification',
        'status_checkout',
        'orderid',
        'invoice_id',
        'cart_id',
        'who_id',
        'where_id',
        'agendaId',
        'exported',
        'commission',
        'exportdata',
        'ecommerce_analytics',
    ];

    protected $casts = [
        '_id' => 'string',

        // numeri
        'subtotal' => 'float',
        'total' => 'float',
        'vat_value' => 'float',
        'discount' => 'float',
        'taxes' => 'float',
        'extra_taxes' => 'float',
        'user_total' => 'float',
        'user_subtotal' => 'float',
        'user_vat_value' => 'float',
        'vat_rate' => 'float',
        'invoice_id' => 'int',

        // boolean
        'integrated' => 'bool',
        'exported' => 'bool',
        'ecommerce_analytics' => 'bool',

        // array/embedded docs
        /*'date' => 'array',
        'client_data' => 'array',
        'cart' => 'array',
        'ip_info' => 'array',
        'time_confirmation' => 'array',
        'shipping' => 'array',
        'payment_types_stats_before' => 'array',
        'payment_types_stats_after' => 'array',
        'abuse_score' => 'array',
        'privacy' => 'array',
        'payment' => 'array',
        'otp' => 'array',
        'notification' => 'array',
        'commission' => 'array',
        'exportdata' => 'array',
        'who_id' => 'array',
        'where_id' => 'array',*/
    ];
}
