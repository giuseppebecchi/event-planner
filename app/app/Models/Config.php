<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';

    public const TYPE_OPTIONS = [
        self::TYPE_TEXT => 'Text',
        self::TYPE_IMAGE => 'Image',
    ];

    protected $fillable = [
        'slug',
        'label',
        'type',
        'text',
        'img',
    ];
}
