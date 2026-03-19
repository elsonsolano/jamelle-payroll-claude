<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollEntry extends Model
{
    protected $fillable = [
        'payroll_cutoff_id',
        'employee_id',
        'basic_pay',
        'overtime_pay',
        'late_deduction',
        'undertime_deduction',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'working_days',
        'total_hours_worked',
    ];

    protected $casts = [
        'basic_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'undertime_deduction' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'total_hours_worked' => 'decimal:2',
    ];

    public function payrollCutoff(): BelongsTo
    {
        return $this->belongsTo(PayrollCutoff::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }
}
