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
        'source',
        'ot_end_time',
        'ot_status',
        'ot_approved_by',
        'ot_approved_at',
        'ot_rejection_reason',
    ];

    protected $casts = [
        'date'           => 'date',
        'is_rest_day'    => 'boolean',
        'total_hours'    => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'ot_approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ot_approved_by');
    }

    public function hasOt(): bool
    {
        return $this->ot_status !== 'none';
    }

    public function otIsPending(): bool
    {
        return $this->ot_status === 'pending';
    }
}
