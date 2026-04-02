<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminUsers;

class AdminUsersController extends BaseController
{
    protected string $modelClass = AdminUsers::class;
    protected string $roleRule;

    public function __construct()
    {
        $this->roleRule = 'required|string|in:' . implode(',', AdminUsers::roleOptions());

        $this->storeRules['role'] = $this->roleRule;
        $this->updateRules['role'] = $this->roleRule;
    }

    protected array $indexFields = [
        '_id',
        'user',
        'email',
        'name',
        'first_name',
        'last_name',
        'company',
        'role',
        'status',
        'address_book',
        'list',
        'iduniq',
    ];

    protected array $detailFields = [
        '_id',
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

    protected array $searchable = [
        'user',
        'email',
        'name',
        'first_name',
        'last_name',
        'company',
        'iduniq',
        'address_book',
        'list',
        'role',
        'data.personal.first_name',
        'data.personal.last_name',
        'data.personal.name',
    ];

    protected array $exactSearchable = [
        'status',
        'role',
    ];

    protected array $storeRules = [
        'user' => 'required|string|max:255',
        'email' => 'required|string|max:255',
        'name' => 'required|string|max:255',
        'password' => 'required|string|max:255',
        'first_name' => 'nullable|string|max:255',
        'last_name' => 'nullable|string|max:255',
        'iduniq' => 'nullable|string|max:255',
        'company' => 'nullable|string|max:255',
        'address_book' => 'nullable|string|max:255',
        'list' => 'nullable|string|max:255',
        'status' => 'nullable|integer',
        'token' => 'nullable|string|max:255',
        'role' => 'required|string',
        'roles' => 'nullable',
        'permissions' => 'nullable',
        'channel' => 'nullable',
        'data' => 'nullable',
        'subcompanies' => 'nullable',
        'where' => 'nullable',
    ];

    protected array $updateRules = [
        'user' => 'required|string|max:255',
        'email' => 'required|string|max:255',
        'name' => 'required|string|max:255',
        'password' => 'nullable|string|max:255',
        'first_name' => 'nullable|string|max:255',
        'last_name' => 'nullable|string|max:255',
        'iduniq' => 'nullable|string|max:255',
        'company' => 'nullable|string|max:255',
        'address_book' => 'nullable|string|max:255',
        'list' => 'nullable|string|max:255',
        'status' => 'nullable|integer',
        'token' => 'nullable|string|max:255',
        'role' => 'required|string',
        'roles' => 'nullable',
        'permissions' => 'nullable',
        'channel' => 'nullable',
        'data' => 'nullable',
        'subcompanies' => 'nullable',
        'where' => 'nullable',
    ];
}
