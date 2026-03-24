<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySchedule extends Model
{
    protected $fillable = [
        'schedule_upload_id',
        'employee_id',
        'date',
        'work_start_time',
        'work_end_time',
        'is_day_off',
        'assigned_branch_id',
        'notes',
    ];

    protected $casts = [
        'date'       => 'date',
        'is_day_off' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function scheduleUpload(): BelongsTo
    {
        return $this->belongsTo(ScheduleUpload::class);
    }
}
