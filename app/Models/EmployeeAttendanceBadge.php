<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendanceBadge extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_badge_id',
        'payroll_cutoff_id',
        'attendance_score_id',
        'awarded_at',
        'metadata',
    ];

    protected $casts = [
        'awarded_at' => 'datetime',
        'metadata'   => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(AttendanceBadge::class, 'attendance_badge_id');
    }

    public function payrollCutoff(): BelongsTo
    {
        return $this->belongsTo(PayrollCutoff::class);
    }

    public function attendanceScore(): BelongsTo
    {
        return $this->belongsTo(AttendanceScore::class);
    }
}
