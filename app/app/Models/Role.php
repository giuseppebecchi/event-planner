<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SUPER_ADMIN = 'SuperAdmin';
    public const ADMIN = 'Admin';
    public const COLLABORATOR = 'Collaborator';
    public const CUSTOMER = 'Customer';

    protected $fillable = [
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
