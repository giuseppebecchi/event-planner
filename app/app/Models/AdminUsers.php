<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Auth\User as Authenticatable;

class AdminUsers extends Authenticatable
{
    use Notifiable;
    use HasApiTokens;

    public const ROLE_SUPER_ADMIN = 'SuperAdmin';
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_COMPANY_MANAGER = 'Company Manager';
    public const ROLE_DOCTOR = 'Doctor';

    public const ROLE_OPTIONS = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_COMPANY_MANAGER,
        self::ROLE_DOCTOR,
    ];


    // Force correct MongoDB collection name.
    protected $table = 'adminusers';
    protected $collection = 'adminusers';

    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user',
        'address_book',
        'channel',
        'company',
        'data',
        'email',
        'first_name',
        'iduniq',
        'last_name',
        'list',
        'name',
        'password',
        'status',
        'subcompanies',
        'where',
        'token',
        'role',
        'roles',
        'permissions',
    ];

    public static function roleOptions(): array
    {
        return self::ROLE_OPTIONS;
    }

    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $rawValue = (string) $value;

        // Avoid double hashing: if it already looks like a valid hash, keep it.
        $this->attributes['password'] = Hash::needsRehash($rawValue)
            ? Hash::make($rawValue)
            : $rawValue;
    }

    public function getTable()
    {
        return 'adminusers';
    }
}
