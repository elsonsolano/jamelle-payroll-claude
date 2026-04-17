<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleChangeRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'current_work_start_time',
        'current_work_end_time',
        'is_current_day_off',
        'requested_work_start_time',
        'requested_work_end_time',
        'is_day_off',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'approved_start_time',
        'approved_end_time',
        'rejection_reason',
        'daily_schedule_id',
    ];

    protected $casts = [
        'date'                => 'date',
        'reviewed_at'         => 'datetime',
        'is_current_day_off'  => 'boolean',
        'is_day_off'          => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function dailySchedule(): BelongsTo
    {
        return $this->belongsTo(DailySchedule::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
