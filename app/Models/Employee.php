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
        'email',
        'employee_code',
        'branch_id',
        'timemark_id',
        'salary_type',
        'rate',
        'active',
        'hired_date',
        'position',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'hired_date' => 'date',
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

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function employeeStandingDeductions(): HasMany
    {
        return $this->hasMany(EmployeeStandingDeduction::class);
    }

    public function timemarkLogs(): HasMany
    {
        return $this->hasMany(TimemarkLog::class);
    }
}
