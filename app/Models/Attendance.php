<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Client;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'check_in_date',
        'employee_id',
        'client_id',
        'status',
        'approval_status',
        'submitted_for_approval_at',
        'check_in_time',
        'check_out_time',
        'lunch_start_time',
        'lunch_end_time',
        'total_working_seconds',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date:Y-m-d',
            'submitted_for_approval_at' => 'datetime',
            'total_working_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
