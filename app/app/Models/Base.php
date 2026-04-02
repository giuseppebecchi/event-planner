<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

abstract class Base extends Model
{
    protected $connection = 'mongodb';

    /**
     * Consiglio: usa fillable per mass-assign safe
     */
    protected $fillable = [];

    /**
     * Se vuoi che Laravel tratti _id come string in output:
     * (dipende da come lo gestisci lato client)
     */
    protected $casts = [
        '_id' => 'string',
    ];
}
