<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Post extends Base
{
    protected $collection = 'posts';

    protected $fillable = [
        'title',
        'body',
        'slug',
        'logo',
        'userId', // se poi vuoi riferimento a users
    ];
}
