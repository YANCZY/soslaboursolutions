<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable([
    'company_name',
    'company_type',
    'trade',
    'industry',
    'industry_description',
    'phone',
    'website',
    'company_address',
    'company_address_2',
    'company_address_city',
    'company_address_state',
    'company_address_country',

])]
class Client extends Model
{
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

}
