<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dtr extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'am_out',
        'pm_in',
        'time_out',
        'total_hours',
        'overtime_hours',
        'late_mins',
        'undertime_mins',
        'is_rest_day',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'is_rest_day' => 'boolean',
        'total_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
