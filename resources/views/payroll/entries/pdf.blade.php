<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip — {{ $entry->employee->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            padding: 32px;
        }

        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5;
        }

        .payslip-title {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }

        .employee-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .employee-name {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .employee-meta {
            font-size: 10px;
            color: #6b7280;
            margin-top: 3px;
            line-height: 1.6;
        }

        .cutoff-info {
            text-align: right;
            font-size: 10px;
            color: #6b7280;
            line-height: 1.8;
        }

        .cutoff-info strong {
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            background: #f9fafb;
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            border-bottom: none;
        }

        table td {
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            font-size: 11px;
        }

        table tr:nth-child(even) td {
            background: #f9fafb;
        }

        .label { color: #374151; }
        .amount { text-align: right; font-weight: 500; }
        .amount-red { text-align: right; color: #dc2626; }

        .subtotal td {
            background: #f3f4f6 !important;
            font-weight: bold;
            color: #111827;
        }

        .net-pay-row {
            background: #4f46e5;
            color: white;
            padding: 12px 10px;
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .net-pay-label {
            font-size: 12px;
            font-weight: bold;
        }

        .net-pay-amount {
            font-size: 20px;
            font-weight: bold;
        }

        .summary-grid {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }

        .summary-grid td {
            padding: 4px 10px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }

        .summary-label {
            color: #6b7280;
            width: 25%;
        }

        .summary-value {
            font-weight: 600;
            color: #111827;
            width: 25%;
        }

        .footer {
            margin-top: 32px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
        }

        .signature-block {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #374151;
            margin-top: 40px;
            padding-top: 4px;
            font-size: 10px;
            color: #374151;
        }
    </style>
</head>
<body>

    {{-- Logo --}}
    @php
        $logoPath = public_path('images/logo.png');
        $logoSrc  = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp
    @if($logoSrc)
    <div style="text-align:center; margin-bottom:12px;">
        <img src="{{ $logoSrc }}" style="height:64px; width:auto;">
    </div>
    @endif

    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ $entry->employee->branch->name }}</div>
        <div class="payslip-title">PAYSLIP</div>
    </div>

    {{-- Employee Info + Cutoff --}}
    <table class="summary-grid">
        <tr>
            <td class="summary-label">Employee</td>
            <td class="summary-value">{{ $entry->employee->full_name }}</td>
            <td class="summary-label">Cutoff Period</td>
            <td class="summary-value">{{ $cutoff->name }}</td>
        </tr>
        <tr>
            <td class="summary-label">Position</td>
            <td class="summary-value">{{ $entry->employee->position ?? '—' }}</td>
            <td class="summary-label">Date Range</td>
            <td class="summary-value">{{ $cutoff->start_date->format('M d') }} – {{ $cutoff->end_date->format('M d, Y') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Salary Type</td>
            <td class="summary-value">{{ ucfirst($entry->employee->salary_type) }} Rate</td>
            <td class="summary-label">{{ $entry->employee->salary_type !== 'monthly' ? 'Days Worked' : '' }}</td>
            <td class="summary-value">{{ $entry->employee->salary_type !== 'monthly' ? number_format($entry->working_days, 2) . ' day(s)' : '' }}</td>
        </tr>
        <tr>
            <td class="summary-label">Rate</td>
            <td class="summary-value">PHP {{ number_format($entry->employee->rate, 2) }} / {{ $entry->employee->salary_type === 'daily' ? 'day' : 'month' }}</td>
            <td class="summary-label">{{ $entry->employee->salary_type !== 'monthly' ? 'Total Hours' : '' }}</td>
            <td class="summary-value">{{ $entry->employee->salary_type !== 'monthly' ? number_format($entry->total_hours_worked, 2) . 'h' : '' }}</td>
        </tr>
    </table>

    {{-- Earnings --}}
    <div class="section-title">Earnings</div>
    <table>
        <tr>
            <td class="label">Basic Pay</td>
            <td class="amount">PHP {{ number_format($entry->basic_pay - $unworkedRegularHolidayPay, 2) }}</td>
        </tr>
        @if($entry->employee->salary_type !== 'monthly')
        @if($unworkedRegularHolidayPay > 0)
        <tr>
            <td class="label">Regular Holiday Pay</td>
            <td class="amount">PHP {{ number_format($unworkedRegularHolidayPay, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Overtime Pay</td>
            <td class="amount">PHP {{ number_format($entry->overtime_pay, 2) }}</td>
        </tr>
        @if($entry->holiday_pay > 0)
        <tr>
            <td class="label">Holiday Pay</td>
            <td class="amount">PHP {{ number_format($entry->holiday_pay, 2) }}</td>
        </tr>
        @endif
        @endif
        @if($entry->allowance_pay > 0)
        <tr>
            <td class="label">Allowance{{ $entry->employee->salary_type !== 'monthly' ? ' (' . number_format($entry->working_days, 2) . ' day(s) worked)' : '' }}</td>
            <td class="amount">PHP {{ number_format($entry->allowance_pay, 2) }}</td>
        </tr>
        @endif
        <tr class="subtotal">
            <td class="label">Gross Pay</td>
            <td class="amount">PHP {{ number_format($entry->gross_pay, 2) }}</td>
        </tr>
    </table>

    {{-- Deductions --}}
    <div class="section-title">Deductions</div>
    <table>
        @if($entry->late_deduction > 0)
        <tr>
            <td class="label">Late Deduction</td>
            <td class="amount-red">- PHP {{ number_format($entry->late_deduction, 2) }}</td>
        </tr>
        @endif
        @if($entry->undertime_deduction > 0)
        <tr>
            <td class="label">Undertime Deduction</td>
            <td class="amount-red">- PHP {{ number_format($entry->undertime_deduction, 2) }}</td>
        </tr>
        @endif
        @foreach($entry->payrollDeductions as $deduction)
        <tr>
            <td class="label">
                {{ $deduction->type }}
                @if($deduction->description) ({{ $deduction->description }}) @endif
            </td>
            <td class="amount-red">- PHP {{ number_format($deduction->amount, 2) }}</td>
        </tr>
        @endforeach
        @foreach($entry->payrollVariableDeductions as $varDeduction)
        <tr>
            <td class="label">{{ $varDeduction->description }}</td>
            <td class="amount-red">- PHP {{ number_format($varDeduction->amount, 2) }}</td>
        </tr>
        @endforeach
        @if($entry->total_deductions == 0)
        <tr>
            <td colspan="2" style="text-align:center; color:#9ca3af;">No deductions.</td>
        </tr>
        @endif
        <tr class="subtotal">
            <td class="label">Total Deductions</td>
            <td class="amount-red">- PHP {{ number_format($entry->total_deductions, 2) }}</td>
        </tr>
    </table>

    {{-- Refunds --}}
    @if($entry->payrollRefunds->isNotEmpty())
    <div class="section-title">Refunds</div>
    <table>
        @foreach($entry->payrollRefunds as $refund)
        <tr>
            <td class="label">{{ $refund->description }}</td>
            <td class="amount" style="color:#059669;">+ PHP {{ number_format($refund->amount, 2) }}</td>
        </tr>
        @endforeach
        <tr class="subtotal">
            <td class="label">Total Refunds</td>
            <td class="amount" style="color:#059669;">+ PHP {{ number_format($entry->payrollRefunds->sum('amount'), 2) }}</td>
        </tr>
    </table>
    @endif

    {{-- Net Pay --}}
    <table>
        <tr style="background:#4f46e5;">
            <td style="padding:10px; font-weight:bold; font-size:13px; color:white;">NET PAY</td>
            <td style="padding:10px; text-align:right; font-weight:bold; font-size:18px; color:white;">PHP {{ number_format($entry->net_pay, 2) }}</td>
        </tr>
    </table>

    {{-- Signature Block --}}
    @php
        $signature = $entry->employee->user?->signature ?? null;
    @endphp
    <div style="margin-top:40px; width:45%;">
        @if($entry->acknowledged_at && $signature)
            <div style="text-align:center; margin-bottom:4px;">
                <img src="{{ $signature }}" style="height:48px; width:auto; max-width:180px;">
            </div>
        @else
            <div style="height:52px;"></div>
        @endif
        <div style="border-top:1px solid #374151; padding-top:4px; font-size:10px; color:#374151; text-align:center;">
            Received by: {{ $entry->employee->full_name }}
            @if($entry->acknowledged_at)
                <br><span style="font-size:9px; color:#6b7280;">Acknowledged {{ $entry->acknowledged_at->format('M d, Y h:i A') }}</span>
            @endif
        </div>
    </div>

    {{-- Generated date --}}
    <div style="margin-top:20px; text-align:right; font-size:9px; color:#9ca3af;">
        Generated: {{ now()->format('M d, Y h:i A') }}
    </div>

</body>
</html>
