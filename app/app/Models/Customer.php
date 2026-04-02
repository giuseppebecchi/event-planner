<?php

namespace App\Models;

class Customer extends Base
{
    protected $collection = 'companies';

    protected $fillable = [
        'iduniq',
        'address_book',
        'channel',
        'company',
        'data',
        'first_name',
        'last_name',
        'lastlogin',
        'list',
        'password',
        'privacy',
        'status',
        'subscriptiondate',
        'tags',
        'user',
    ];

    protected $casts = [
        '_id'              => 'string',
        'channel'          => 'array',
        //'data'             => 'array',
        'privacy'          => 'array',
        /*'tags'             => 'array',
        'status'           => 'int',
        'lastlogin'        => 'datetime',
        'subscriptiondate' => 'datetime',*/
    ];
}
