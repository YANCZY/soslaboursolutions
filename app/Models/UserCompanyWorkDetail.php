<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


#[Fillable([
    'user_id',
    'client_id',
    'job_role',
    'salary',
    'travel_allowance',
    'travel_allowance_currency',
    'site_id',
    'start_shift',
    'end_shift',
])]
class UserCompanyWorkDetail extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
