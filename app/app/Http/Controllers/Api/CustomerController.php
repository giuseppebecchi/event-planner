<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;

class CustomerController extends BaseController
{
    protected string $modelClass = Customer::class;

    // Campi mostrati in lista (index)
    protected array $indexFields = [
        'iduniq',
        'company',
        'user',
        'first_name',
        'last_name',
        'status',
        'lastlogin',
        'subscriptiondate',
        'tags',
        'data'
    ];

    // Campi mostrati in dettaglio (show)
    protected array $detailFields = [
        'iduniq',
        'address_book',
        'channel',
        'company',
        'data',
        'first_name',
        'last_name',
        'lastlogin',
        'list',
        'privacy',
        'status',
        'subscriptiondate',
        'tags',
        'user',
        // 'password', // ⚠️ sconsigliato esporlo via API
    ];

    // Ricerca semplice: ?company=...&user=...&first_name=...
    protected array $searchable = [
        'company',
        'user',
        'first_name',
        'last_name',
        'iduniq',
        'data.personal.lastName', // cognome anche dentro data.personal

    ];


    protected array $exactSearchable = [
        'status',
        'data.personal.mobile',   // ✅ &data.personal.mobile=3391122334
    ];

    protected array $storeRules = [
        'iduniq'        => 'required|string',
        'company'       => 'required|string|max:255',
        'user'          => 'required|string|max:255',
        'first_name'    => 'nullable|string|max:255',
        'last_name'     => 'nullable|string|max:255',
        'status'        => 'nullable|integer',
        'tags'          => 'nullable|array',
        'address_book'  => 'nullable|string|max:50',
        'channel'       => 'nullable|array',
        'data'          => 'nullable|array',
        'privacy'       => 'nullable|array',
        'list'          => 'nullable|string|max:50',
        'lastlogin'     => 'nullable|date',
        'subscriptiondate' => 'nullable|date',
        'password'      => 'nullable|string', // ⚠️ idealmente: mai in chiaro, solo hash
    ];

    protected array $updateRules = [
        'iduniq'        => 'string',
        'company'       => 'string|max:255',
        'user'          => 'string|max:255',
        'first_name'    => 'nullable|string|max:255',
        'last_name'     => 'nullable|string|max:255',
        'status'        => 'nullable|integer',
        'tags'          => 'nullable|array',
        'address_book'  => 'nullable|string|max:50',
        'channel'       => 'nullable|array',
        'data'          => 'nullable|array',
        'privacy'       => 'nullable|array',
        'list'          => 'nullable|string|max:50',
        'lastlogin'     => 'nullable|date',
        'subscriptiondate' => 'nullable|date',
        'password'      => 'nullable|string',
    ];

    // opzionale: limita anche i campi scrivibili (se nel BaseController la usi)
    protected array $writeFields = [
        'iduniq',
        'address_book',
        'channel',
        'company',
        'data',
        'first_name',
        'last_name',
        'lastlogin',
        'list',
        'privacy',
        'status',
        'subscriptiondate',
        'tags',
        'user',
        'password',
    ];
}
