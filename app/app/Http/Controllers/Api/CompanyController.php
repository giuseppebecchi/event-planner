<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;

class CompanyController extends BaseController
{
    protected string $modelClass = Company::class;

    // Campi mostrati in lista (index)
    protected array $indexFields = [
        '_id',
        'company',
        'name',
        'company_name',
        'vat',
        'tax_code',
        'client_code',
        'payment_code',
        'city',
        'province',
        'status',
        'payments',
        'googleads',
        'integrated',
        'onboard',
        'category_type',
        'reliability_type',
    ];

    // Campi mostrati in dettaglio (show)
    protected array $detailFields = [
        '_id',
        'company',
        'name',
        'company_name',

        'vat',
        'tax_code',
        'sdi',
        'pec',
        'iban',

        'url',
        'logo',
        'contacts',

        'client_code',
        'payment_code',
        'api_key',
        'comipa_code',
        'old_comipa_code',
        'commercial_agent_code',

        'payments',
        'mandatory_payment',
        'mandatory_payment_who',
        'mandatory_payment_offline',
        'mandatory_payment_offline_who',
        'mandatory_payment_coupon',
        'mandatory_payment_coupon_who',

        'address_1',
        'address_2',
        'zipcode',
        'city',
        'province',

        'preavviso',
        'base_commissions',
        'base_commissions_comipa',
        'blood_sampling_price',
        'skip_realtime_check',
        'time_confirmation',

        'visibility',
        'visibility_old',
        'status',
        'integrated',
        'onboard',
        'group',
        'company_type',
        'category_type',
        'reliability_type',

        'googleads',
        'googlereservewith',
        'company_without_stamp_duty',
        'video_old',

        'seed',
        'notes',
        'webhook_data',
        'max_office',
        'invoices_notes_xml',

        'prima_prenotazione',
    ];

    // Ricerca semplice (LIKE/contains): ?company=...&name=...&city=...
    protected array $searchable = [
        'company',
        'name',
        'company_name',
        'vat',
        'tax_code',
        'sdi',
        'pec',
        'client_code',
        'payment_code',
        'city',
        'province',
        'status',
        'category_type',
        'commercial_agent_code',
        // esempi nested (se nel BaseController supporti dot notation)
        'contacts.name',
        'contacts.channel.email',
    ];

    // Ricerca esatta: ?status=published&integrated=1
    protected array $exactSearchable = [
        'status',
        'integrated',
        'googleads',
        'googlereservewith',
        'company_without_stamp_duty',
        'onboard',
        'company_type',
        'reliability_type',
        'category_type',
    ];

    protected array $storeRules = [
        'company' => 'required|string|max:255',
        'name' => 'required|string|max:255',
        'company_name' => 'required|string|max:255',

        'vat' => 'nullable|string|max:50',
        'tax_code' => 'nullable|string|max:50',
        'sdi' => 'nullable|string|max:50',
        'pec' => 'nullable|email|max:255',
        'iban' => 'nullable|string|max:64',

        'url' => 'nullable|string|max:255',
        'logo' => 'nullable|array',
        'logo.logo_path' => 'nullable|string|max:255',
        'logo.w' => 'nullable|string|max:10',
        'logo.h' => 'nullable|string|max:10',

        'client_code' => 'nullable|string|max:50',
        'payment_code' => 'nullable|string|max:50',
        'api_key' => 'nullable|string|max:255',

        'notes' => 'nullable|string',

        'payments' => 'nullable|array',
        'payments.*' => 'string|max:50',

        'mandatory_payment' => 'nullable|array',
        'mandatory_payment.*.what_id' => 'nullable|string|max:100',
        'mandatory_payment.*.name' => 'nullable|string|max:255',

        'mandatory_payment_who' => 'nullable|array',
        'mandatory_payment_who.*.who_id' => 'nullable|string|max:100',
        'mandatory_payment_who.*.who_name' => 'nullable|string|max:255',

        'mandatory_payment_offline' => 'nullable|array',
        'mandatory_payment_offline_who' => 'nullable|array',
        'mandatory_payment_coupon' => 'nullable|array',
        'mandatory_payment_coupon_who' => 'nullable|array',

        'preavviso' => 'nullable|array',
        'preavviso.daysoff' => 'nullable|array',
        'preavviso.daysoff.*' => 'integer',
        'preavviso.unit' => 'nullable|string|max:20',
        'preavviso.value' => 'nullable|string|max:20',
        'preavviso.min_value' => 'nullable|string|max:20',
        'preavviso.changes' => 'nullable|array',

        'address_1' => 'nullable|string|max:255',
        'address_2' => 'nullable|string|max:255',
        'zipcode' => 'nullable|string|max:20',
        'city' => 'required|string|max:100',
        'province' => 'nullable|string|max:10',

        'contacts' => 'nullable|array',

        'base_commissions' => 'nullable|array',
        'base_commissions_comipa' => 'nullable|array',
        'blood_sampling_price' => 'nullable|numeric',
        'skip_realtime_check' => 'nullable',

        'visibility' => 'nullable|array',
        'visibility.*' => 'string|max:50',

        'comipa_code' => 'nullable|string|max:100',
        'old_comipa_code' => 'nullable|string|max:100',
        'commercial_agent_code' => 'nullable|string|max:100',

        'status' => 'nullable|string|max:50',
        'integrated' => 'nullable|boolean',
        'onboard' => 'nullable|string|max:50',
        'company_type' => 'nullable|string|max:50',
        'group' => 'nullable|string|max:100',
        'category_type' => 'nullable|string|max:100',
        'reliability_type' => 'nullable|string|max:50',

        'time_confirmation' => 'nullable|array',
        'time_confirmation.minutes' => 'nullable|integer',

        'webhook_data' => 'nullable|array',

        'googleads' => 'nullable|boolean',
        'googlereservewith' => 'nullable|boolean',
        'company_without_stamp_duty' => 'nullable|boolean',

        'seed' => 'nullable|array',
        'seed.*' => 'string|max:100',

        'prima_prenotazione' => 'nullable|date',
        'max_office' => 'nullable|string|max:50',
        'invoices_notes_xml' => 'nullable|string',
        'video_old' => 'nullable|string|max:10',
    ];

    protected array $updateRules = [
        'company' => 'required|string|max:255',
        'name' => 'string|max:255',
        'company_name' => 'required|string|max:255',

        'vat' => 'nullable|string|max:50',
        'tax_code' => 'nullable|string|max:50',
        'sdi' => 'nullable|string|max:50',
        'pec' => 'nullable|email|max:255',
        'iban' => 'nullable|string|max:64',

        'url' => 'nullable|string|max:255',
        'logo' => 'nullable|array',
        'logo.logo_path' => 'nullable|string|max:255',
        'logo.w' => 'nullable|string|max:10',
        'logo.h' => 'nullable|string|max:10',

        'client_code' => 'nullable|string|max:50',
        'payment_code' => 'nullable|string|max:50',
        'api_key' => 'nullable|string|max:255',

        'notes' => 'nullable|string',

        'payments' => 'nullable|array',
        'payments.*' => 'string|max:50',

        'mandatory_payment' => 'nullable|array',
        'mandatory_payment.*.what_id' => 'nullable|string|max:100',
        'mandatory_payment.*.name' => 'nullable|string|max:255',

        'mandatory_payment_who' => 'nullable|array',
        'mandatory_payment_who.*.who_id' => 'nullable|string|max:100',
        'mandatory_payment_who.*.who_name' => 'nullable|string|max:255',

        'mandatory_payment_offline' => 'nullable|array',
        'mandatory_payment_offline_who' => 'nullable|array',
        'mandatory_payment_coupon' => 'nullable|array',
        'mandatory_payment_coupon_who' => 'nullable|array',

        'preavviso' => 'nullable|array',
        'preavviso.daysoff' => 'nullable|array',
        'preavviso.daysoff.*' => 'integer',
        'preavviso.unit' => 'nullable|string|max:20',
        'preavviso.value' => 'nullable|string|max:20',
        'preavviso.min_value' => 'nullable|string|max:20',
        'preavviso.changes' => 'nullable|array',

        'address_1' => 'nullable|string|max:255',
        'address_2' => 'nullable|string|max:255',
        'zipcode' => 'nullable|string|max:20',
        'city' => 'required|string|max:100',
        'province' => 'nullable|string|max:10',

        'contacts' => 'nullable|array',

        'base_commissions' => 'nullable|array',
        'base_commissions_comipa' => 'nullable|array',
        'blood_sampling_price' => 'nullable|numeric',
        'skip_realtime_check' => 'nullable',

        'visibility' => 'nullable|array',
        'visibility.*' => 'string|max:50',

        'comipa_code' => 'nullable|string|max:100',
        'old_comipa_code' => 'nullable|string|max:100',
        'commercial_agent_code' => 'nullable|string|max:100',

        'status' => 'nullable|string|max:50',
        'integrated' => 'nullable|boolean',
        'onboard' => 'nullable|string|max:50',
        'company_type' => 'nullable|string|max:50',
        'group' => 'nullable|string|max:100',
        'category_type' => 'nullable|string|max:100',
        'reliability_type' => 'nullable|string|max:50',

        'time_confirmation' => 'nullable|array',
        'time_confirmation.minutes' => 'nullable|integer',

        'webhook_data' => 'nullable|array',

        'googleads' => 'nullable|boolean',
        'googlereservewith' => 'nullable|boolean',
        'company_without_stamp_duty' => 'nullable|boolean',

        'seed' => 'nullable|array',
        'seed.*' => 'string|max:100',

        'prima_prenotazione' => 'nullable|date',
        'max_office' => 'nullable|string|max:50',
        'invoices_notes_xml' => 'nullable|string',
        'video_old' => 'nullable|string|max:10',
    ];

    // opzionale: limita i campi scrivibili (se nel BaseController la usi)
    protected array $writeFields = [
        'company',
        'name',
        'company_name',

        'vat',
        'tax_code',
        'sdi',
        'pec',
        'iban',

        'url',
        'logo',
        'contacts',

        'client_code',
        'payment_code',
        'api_key',
        'comipa_code',
        'old_comipa_code',
        'commercial_agent_code',

        'notes',
        'payments',

        'mandatory_payment',
        'mandatory_payment_who',
        'mandatory_payment_offline',
        'mandatory_payment_offline_who',
        'mandatory_payment_coupon',
        'mandatory_payment_coupon_who',

        'preavviso',

        'address_1',
        'address_2',
        'zipcode',
        'city',
        'province',

        'base_commissions',
        'base_commissions_comipa',
        'blood_sampling_price',
        'skip_realtime_check',

        'visibility',

        'status',
        'integrated',
        'onboard',
        'company_type',
        'group',
        'category_type',
        'reliability_type',

        'time_confirmation',
        'webhook_data',

        'googleads',
        'googlereservewith',
        'company_without_stamp_duty',

        'seed',
        'max_office',
        'invoices_notes_xml',
        'video_old',

        'prima_prenotazione',
    ];
}
