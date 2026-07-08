<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


#[Fillable([
    'user_id',
    'client_id',
    'date',
    'name',
    'description',
    'rate',
    'quantity',
    'amount',
    'approval_status',
    'submitted_for_approval_at',
])]
class TravelAllowance extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    protected function casts(): array
    {
        return [
            'submitted_for_approval_at' => 'datetime',
        ];
    }
}
