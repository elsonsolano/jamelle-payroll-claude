<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollCutoff extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'void_reason',
        'finalized_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'finalized_at'=> 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
}
