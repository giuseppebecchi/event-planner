<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'mysql';

    /**
     * Resolve token owner explicitly from the polymorphic target class.
     */
    public function getTokenableAttribute(): ?Model
    {
        $type = $this->attributes['tokenable_type'] ?? null;
        $id = $this->attributes['tokenable_id'] ?? null;

        if (!$type || !$id || !class_exists($type)) {
            return null;
        }

        /** @var class-string<Model> $type */
        return $type::query()->find($id);
    }

    public function tokenable(): MorphTo
    {
        return $this->morphTo('tokenable');
    }
}
