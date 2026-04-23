<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_type_name'])]
class UserType extends Model
{
    /**
     * Get the users for the user type.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
