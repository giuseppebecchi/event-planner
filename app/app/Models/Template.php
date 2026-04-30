<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    public const TYPE_HTML = 'html';
    public const TYPE_TEXT_PLAIN = 'text_plain';

    protected $fillable = [
        'title',
        'slug',
        'language',
        'type',
        'content',
    ];
}
