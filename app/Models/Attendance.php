<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'check_in_date',
        'employee_id',
        'status',
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
            'total_working_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
