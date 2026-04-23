<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceScore extends Model
{
    protected $fillable = [
        'payroll_cutoff_id',
        'employee_id',
        'total_points',
        'complete_dtr_days',
        'on_time_days',
        'proper_time_out_days',
        'same_day_complete_days',
        'no_absent_days',
        'approved_ot_days',
        'late_days',
        'late_minutes',
        'finalized_at',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
    ];

    public function payrollCutoff(): BelongsTo
    {
        return $this->belongsTo(PayrollCutoff::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AttendanceScoreItem::class);
    }

    public function employeeAttendanceBadges(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceBadge::class);
    }
}
