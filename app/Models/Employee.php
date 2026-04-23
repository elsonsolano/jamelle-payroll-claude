<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'nickname',
        'email',
        'employee_code',
        'branch_id',
        'timemark_id',
        'salary_type',
        'rate',
        'active',
        'hired_date',
        'birthday',
        'position',
        'sss_no',
        'phic_no',
        'pagibig_no',
        'tin_no',
        'bdo_account_number',
        'contact_number',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'hired_date' => 'date',
        'birthday'   => 'date',
        'rate'       => 'decimal:2',
    ];

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function dtrs(): HasMany
    {
        return $this->hasMany(Dtr::class);
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function attendanceScores(): HasMany
    {
        return $this->hasMany(AttendanceScore::class);
    }

    public function employeeAttendanceBadges(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceBadge::class);
    }

    public function attendanceBadges()
    {
        return $this->belongsToMany(AttendanceBadge::class, 'employee_attendance_badges')
            ->withPivot(['payroll_cutoff_id', 'attendance_score_id', 'awarded_at', 'metadata'])
            ->withTimestamps();
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function employeeStandingDeductions(): HasMany
    {
        return $this->hasMany(EmployeeStandingDeduction::class);
    }

    public function employeeAllowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function dailySchedules(): HasMany
    {
        return $this->hasMany(DailySchedule::class);
    }

    public function timemarkLogs(): HasMany
    {
        return $this->hasMany(TimemarkLog::class);
    }
}
