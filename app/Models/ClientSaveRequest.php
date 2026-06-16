<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ClientSaveRequest extends Model
{
    //
     use HasUuids;
     protected $fillable = [
        'user_id',
        'client_id',
        'payload',
        'status',
        'error_message',
    ];
    protected $casts = [
        'payload' => 'array',
    ];
}
