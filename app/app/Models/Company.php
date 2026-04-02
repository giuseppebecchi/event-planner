<?php

namespace App\Models;

class Company extends Base
{

    protected $collection = 'companies';

    protected $fillable = [
        // identificativi
        'company',
        'company_name',
        'name',

        // fiscali
        'vat',
        'tax_code',
        'sdi',
        'pec',
        'iban',

        // contatti e branding
        'url',
        'logo',
        'contacts',

        // codici interni
        'client_code',
        'payment_code',
        'api_key',
        'comipa_code',
        'old_comipa_code',
        'commercial_agent_code',

        // pagamenti
        'payments',
        'mandatory_payment',
        'mandatory_payment_who',
        'mandatory_payment_offline',
        'mandatory_payment_offline_who',
        'mandatory_payment_coupon',
        'mandatory_payment_coupon_who',
        'mandatory_payment_offline',
        'mandatory_payment',

        // indirizzi
        'address_1',
        'address_2',
        'zipcode',
        'city',
        'province',

        // configurazioni
        'preavviso',
        'base_commissions',
        'base_commissions_comipa',
        'blood_sampling_price',
        'skip_realtime_check',
        'time_confirmation',

        // visibilità / stato
        'visibility',
        'visibility_old',
        'status',
        'integrated',
        'onboard',
        'group',
        'company_type',
        'category_type',
        'reliability_type',

        // flag
        'googleads',
        'googlereservewith',
        'company_without_stamp_duty',
        'video_old',

        // metadati
        'seed',
        'notes',
        'webhook_data',
        'max_office',
        'invoices_notes_xml',

        // date
        'prima_prenotazione',
    ];

    protected $casts = [
        '_id' => 'string',

        // array semplici
//        'payments' => 'array',
//        'visibility' => 'array',
//        'seed' => 'array',
//
//        // strutture complesse
//        'logo' => 'array',
//        'contacts' => 'array',
//
//        'mandatory_payment' => 'array',
//        'mandatory_payment_who' => 'array',
//        'mandatory_payment_offline' => 'array',
//        'mandatory_payment_offline_who' => 'array',
//        'mandatory_payment_coupon' => 'array',
//        'mandatory_payment_coupon_who' => 'array',
//
//        'preavviso' => 'array',
//        'base_commissions' => 'array',
//        'base_commissions_comipa' => 'array',
//        'webhook_data' => 'array',
//        'time_confirmation' => 'array',

        // boolean
        'googleads' => 'boolean',
        'googlereservewith' => 'boolean',
        'integrated' => 'boolean',
        'company_without_stamp_duty' => 'boolean',

        // date
        'prima_prenotazione' => 'datetime',
    ];

   /* protected $appends = ['id'];

    public function getIdAttribute(): string
    {
        return (string) $this->_id;
    }*/

    //scope get published
    public function scopePublished($query){
        return $query->where('status', 'published');
    }

    //scope filter by city
    public function scopeCity($query, $city){
        return $query->where('city', $city);
    }

}
