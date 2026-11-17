<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'is_active', 'configuration'];

    protected $casts = ['configuration' => 'array', 'is_active' => 'boolean'];
}
