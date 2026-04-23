<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Register - {{ $cutoff->name }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 24px;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .header-table,
        .summary-table,
        .entries-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 84px;
        }

        .logo {
            height: 54px;
            width: auto;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .subtitle {
            font-size: 11px;
            color: #4b5563;
        }

        .meta {
            text-align: right;
            font-size: 10px;
            line-height: 1.7;
        }

        .meta-label {
            color: #6b7280;
            padding-right: 8px;
        }

        .meta-value {
            font-weight: 600;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #374151;
            margin: 16px 0 6px;
        }

        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            width: 25%;
        }

        .summary-label {
            color: #6b7280;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .summary-value {
            font-size: 12px;
            font-weight: 700;
            margin-top: 2px;
        }

        .entries-table th,
        .entries-table td {
            border: 1px solid #d1d5db;
            padding: 5px 5px;
        }

        .entries-table th {
            background: #f3f4f6;
            color: #374151;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
        }

        .entries-table td {
            font-size: 9.5px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .employee-name {
            font-weight: 700;
            color: #111827;
        }

        .employee-meta {
            font-size: 8.5px;
            color: #6b7280;
            margin-top: 2px;
        }

        .totals-row td {
            background: #f9fafb;
            font-weight: 700;
        }

        .net-pay {
            color: #1d4ed8;
            font-weight: 700;
        }

        .deductions {
            color: #b91c1c;
        }

        .footer {
            margin-top: 12px;
            font-size: 8.5px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td>
                    <div class="title">Payroll Register</div>
                    <div class="subtitle">{{ $cutoff->name }}{{ $cutoff->branch ? ' - ' . $cutoff->branch->name : '' }}</div>
                </td>
                <td class="meta">
                    <div><span class="meta-label">Period</span><span class="meta-value">{{ $cutoff->start_date->format('M d') }} - {{ $cutoff->end_date->format('M d, Y') }}</span></div>
                    <div><span class="meta-label">Status</span><span class="meta-value">{{ ucfirst($cutoff->status) }}</span></div>
                    <div><span class="meta-label">Generated</span><span class="meta-value">{{ now()->format('M d, Y h:i A') }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Summary</div>
    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Employees</div>
                <div class="summary-value">{{ $summary['total_employees'] }}</div>
            </td>
            <td>
                <div class="summary-label">Total Basic Pay</div>
                <div class="summary-value">PHP {{ number_format($summary['total_basic_pay'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Deductions</div>
                <div class="summary-value">PHP {{ number_format($summary['total_deductions'], 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Total Net Pay</div>
                <div class="summary-value">PHP {{ number_format($summary['total_net_pay'], 2) }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="summary-label">Retirement Allocation</div>
                <div class="summary-value">PHP {{ number_format($summary['total_retirement_pay'], 2) }}</div>
            </td>
            <td colspan="2">
                <div class="summary-label">13th Month Allocation</div>
                <div class="summary-value">PHP {{ number_format($summary['total_thirteenth_month'], 2) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Employee Entries</div>
    <table class="entries-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Type</th>
                <th class="text-right">Days</th>
                <th class="text-right">Hours</th>
                <th class="text-right">OT Hrs</th>
                <th class="text-right">Basic</th>
                <th class="text-right">OT Pay</th>
                <th class="text-right">Holiday</th>
                <th class="text-right">Allowance</th>
                <th class="text-right">Gross</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Refunds</th>
                <th class="text-right">Net Pay</th>
                <th class="text-right">Retirement</th>
                <th class="text-right">13th Mo.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>
                        <div class="employee-name">{{ $entry->employee->full_name }}</div>
                        <div class="employee-meta">{{ $entry->employee->position ?? 'No position' }}</div>
                    </td>
                    <td>{{ ucfirst($entry->employee->salary_type) }}</td>
                    <td class="text-right">{{ $entry->employee->salary_type !== 'monthly' ? number_format($entry->working_days, 2) : '—' }}</td>
                    <td class="text-right">{{ $entry->employee->salary_type !== 'monthly' ? number_format($entry->total_hours_worked, 2) : '—' }}</td>
                    <td class="text-right">{{ $entry->employee->salary_type !== 'monthly' ? number_format($entry->total_overtime_hours, 2) : '—' }}</td>
                    <td class="text-right">PHP {{ number_format($entry->basic_pay, 2) }}</td>
                    <td class="text-right">{{ $entry->employee->salary_type !== 'monthly' ? 'PHP ' . number_format($entry->overtime_pay, 2) : '—' }}</td>
                    <td class="text-right">{{ $entry->employee->salary_type !== 'monthly' ? 'PHP ' . number_format($entry->holiday_pay, 2) : '—' }}</td>
                    <td class="text-right">PHP {{ number_format($entry->allowance_pay, 2) }}</td>
                    <td class="text-right">PHP {{ number_format($entry->gross_pay, 2) }}</td>
                    <td class="text-right deductions">PHP {{ number_format($entry->total_deductions, 2) }}</td>
                    <td class="text-right">PHP {{ number_format($entry->payrollRefunds->sum('amount'), 2) }}</td>
                    <td class="text-right net-pay">PHP {{ number_format($entry->net_pay, 2) }}</td>
                    @php
                        if ($entry->is_imported) {
                            $pdfRetirementPay   = (float) $entry->retirement_pay;
                            $pdfThirteenthMonth = (float) $entry->thirteenth_month_allocation;
                        } else {
                            $pdfDailyRate = $entry->employee->salary_type === 'monthly'
                                ? $entry->employee->rate / 22
                                : $entry->employee->rate;
                            $pdfRetirementPay   = $pdfDailyRate * 22.5 / 12 / 2;
                            $pdfThirteenthMonth = $entry->basic_pay / 12;
                        }
                    @endphp
                    <td class="text-right">PHP {{ number_format($pdfRetirementPay, 2) }}</td>
                    <td class="text-right">PHP {{ number_format($pdfThirteenthMonth, 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="5">Totals</td>
                <td class="text-right">PHP {{ number_format($summary['total_basic_pay'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($summary['total_overtime'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($summary['total_holiday'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($summary['total_allowance'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($summary['total_gross_pay'], 2) }}</td>
                <td class="text-right deductions">PHP {{ number_format($summary['total_deductions'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($summary['total_refunds'], 2) }}</td>
                <td class="text-right net-pay">PHP {{ number_format($summary['total_net_pay'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($summary['total_retirement_pay'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($summary['total_thirteenth_month'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Payroll cutoff #{{ $cutoff->id }}
    </div>
</body>
</html>
