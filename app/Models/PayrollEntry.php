<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PayrollEntry extends Model
{
    protected $fillable = [
        'payroll_cutoff_id',
        'employee_id',
        'basic_pay',
        'overtime_pay',
        'holiday_pay',
        'allowance_pay',
        'late_deduction',
        'undertime_deduction',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'working_days',
        'daily_rate',
        'total_hours_worked',
        'total_overtime_hours',
        'retirement_pay',
        'thirteenth_month_allocation',
        'acknowledged_at',
        'acknowledged_ip',
        'acknowledged_by',
        'is_imported',
    ];

    protected $casts = [
        'basic_pay'                    => 'decimal:2',
        'overtime_pay'                 => 'decimal:2',
        'holiday_pay'                  => 'decimal:2',
        'allowance_pay'                => 'decimal:2',
        'retirement_pay'               => 'decimal:2',
        'thirteenth_month_allocation'  => 'decimal:2',
        'late_deduction'               => 'decimal:2',
        'undertime_deduction'          => 'decimal:2',
        'gross_pay'                    => 'decimal:2',
        'total_deductions'             => 'decimal:2',
        'net_pay'                      => 'decimal:2',
        'total_hours_worked'           => 'decimal:2',
        'acknowledged_at'              => 'datetime',
        'is_imported'                  => 'boolean',
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

    public function payrollRefunds(): HasMany
    {
        return $this->hasMany(PayrollEntryRefund::class);
    }

    public function payrollVariableDeductions(): HasMany
    {
        return $this->hasMany(PayrollEntryVariableDeduction::class);
    }

    /**
     * The portion of basic_pay that comes from unworked regular holidays.
     * Daily employees receive 100% daily rate for each unworked regular holiday
     * (unless the holiday falls on a rest day). This amount is bundled into
     * basic_pay in the DB; this method extracts it for transparent display.
     */
    public function unworkedRegularHolidayPay(): float
    {
        if ($this->employee->salary_type !== 'daily') {
            return 0.0;
        }

        $cutoff    = $this->payrollCutoff;
        $dailyRate = (float) $this->employee->rate;

        $regularHolidays = Holiday::where('type', 'regular')
            ->whereBetween('date', [$cutoff->start_date->toDateString(), $cutoff->end_date->toDateString()])
            ->get();

        if ($regularHolidays->isEmpty()) {
            return 0.0;
        }

        $dtrs = Dtr::where('employee_id', $this->employee_id)
            ->whereBetween('date', [$cutoff->start_date->toDateString(), $cutoff->end_date->toDateString()])
            ->get()
            ->keyBy(fn($d) => Carbon::parse($d->date)->toDateString());

        $total = 0.0;
        foreach ($regularHolidays as $holiday) {
            $dateStr = $holiday->date->toDateString();
            $dtr     = $dtrs->get($dateStr);
            if (! $dtr?->time_in && ! ($dtr?->is_rest_day ?? false)) {
                $total += $dailyRate;
            }
        }

        return round($total, 2);
    }
}
