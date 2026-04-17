<x-staff-layout>
    <x-slot name="title">Payslip</x-slot>

    <div class="space-y-4">

        {{-- Back + Download --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('staff.payslips.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-lg font-bold text-gray-900">Payslip</h1>
            </div>
            <a href="{{ route('staff.payslips.pdf', $entry) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download PDF
            </a>
        </div>

        {{-- Period header card --}}
        <div class="bg-green-500 rounded-2xl p-5 text-white">
            <p class="text-green-100 text-xs font-medium uppercase tracking-wider">Cutoff Period</p>
            <p class="text-lg font-bold mt-1">{{ $entry->payrollCutoff->name }}</p>
            <p class="text-green-100 text-sm mt-0.5">
                {{ $entry->payrollCutoff->start_date->format('M d') }} – {{ $entry->payrollCutoff->end_date->format('M d, Y') }}
            </p>
            <div class="mt-4 pt-4 border-t border-white/20 flex gap-6 text-sm">
                @if($entry->employee->salary_type !== 'monthly')
                <div>
                    <p class="text-green-200 text-xs">Days Worked</p>
                    <p class="font-semibold">{{ number_format($entry->working_days, 2) }}</p>
                </div>
                @endif
                @if($entry->employee->salary_type !== 'monthly')
                <div>
                    <p class="text-green-200 text-xs">Total Hours</p>
                    <p class="font-semibold">{{ number_format($entry->total_hours_worked, 2) }}h</p>
                </div>
                @endif
                @if($entry->total_overtime_hours > 0 && $entry->employee->salary_type !== 'monthly')
                <div>
                    <p class="text-green-200 text-xs">OT Hours</p>
                    <p class="font-semibold">{{ number_format($entry->total_overtime_hours, 2) }}h</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Earnings --}}
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Earnings</p>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">Basic Pay</span>
                <span class="font-medium text-gray-900">PHP {{ number_format($entry->basic_pay - $unworkedRegularHolidayPay, 2) }}</span>
            </div>
            @if($entry->employee->salary_type !== 'monthly')
            @if($unworkedRegularHolidayPay > 0)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">Regular Holiday Pay</span>
                <span class="font-medium text-gray-900">PHP {{ number_format($unworkedRegularHolidayPay, 2) }}</span>
            </div>
            @endif
            @if($entry->overtime_pay > 0)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">Overtime Pay</span>
                <span class="font-medium text-gray-900">PHP {{ number_format($entry->overtime_pay, 2) }}</span>
            </div>
            @endif
            @if($entry->holiday_pay > 0)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">Holiday Pay</span>
                <span class="font-medium text-gray-900">PHP {{ number_format($entry->holiday_pay, 2) }}</span>
            </div>
            @endif
            @endif
            @if($entry->allowance_pay > 0)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">Allowance</span>
                <span class="font-medium text-gray-900">PHP {{ number_format($entry->allowance_pay, 2) }}</span>
            </div>
            @endif
            <div class="px-5 py-3 flex justify-between text-sm bg-gray-50 rounded-b-2xl">
                <span class="font-semibold text-gray-700">Gross Pay</span>
                <span class="font-bold text-gray-900">PHP {{ number_format($entry->gross_pay, 2) }}</span>
            </div>
        </div>

        {{-- Deductions --}}
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Deductions</p>
            </div>
            @forelse($entry->payrollDeductions as $deduction)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">
                    {{ $deduction->type }}@if($deduction->description) ({{ $deduction->description }})@endif
                </span>
                <span class="font-medium text-red-600">- PHP {{ number_format($deduction->amount, 2) }}</span>
            </div>
            @empty
            @endforelse
            @foreach($entry->payrollVariableDeductions as $varDeduction)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">{{ $varDeduction->description }}</span>
                <span class="font-medium text-red-600">- PHP {{ number_format($varDeduction->amount, 2) }}</span>
            </div>
            @endforeach
            @if($entry->payrollDeductions->isEmpty() && $entry->payrollVariableDeductions->isEmpty())
            <div class="px-5 py-3 text-sm text-gray-400 text-center">No deductions.</div>
            @endif
            <div class="px-5 py-3 flex justify-between text-sm bg-gray-50 rounded-b-2xl">
                <span class="font-semibold text-gray-700">Total Deductions</span>
                <span class="font-bold text-red-600">- PHP {{ number_format($entry->total_deductions, 2) }}</span>
            </div>
        </div>

        {{-- Refunds --}}
        @if($entry->payrollRefunds->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Refunds</p>
            </div>
            @foreach($entry->payrollRefunds as $refund)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-600">{{ $refund->description }}</span>
                <span class="font-medium text-emerald-600">+ PHP {{ number_format($refund->amount, 2) }}</span>
            </div>
            @endforeach
            <div class="px-5 py-3 flex justify-between text-sm bg-gray-50 rounded-b-2xl">
                <span class="font-semibold text-gray-700">Total Refunds</span>
                <span class="font-bold text-emerald-600">+ PHP {{ number_format($entry->payrollRefunds->sum('amount'), 2) }}</span>
            </div>
        </div>
        @endif

        {{-- Net Pay --}}
        <div class="bg-green-600 rounded-2xl px-5 py-4 flex items-center justify-between">
            <p class="text-white font-bold text-base">NET PAY</p>
            <p class="text-white font-bold text-2xl">PHP {{ number_format($entry->net_pay, 2) }}</p>
        </div>

        {{-- Acknowledgment --}}
        @if($entry->acknowledged_at && $entry->acknowledged_by === 'system')
            <div class="bg-gray-50 border border-gray-200 rounded-2xl px-5 py-4 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Automatically confirmed</p>
                    <p class="text-xs text-gray-500 mt-0.5">No response was received within 5 days of payroll finalization. Auto-confirmed on {{ $entry->acknowledged_at->format('M d, Y') }}.</p>
                </div>
            </div>
        @elseif($entry->acknowledged_at)
            <div class="bg-green-50 border border-green-200 rounded-2xl px-5 py-4 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-green-800">Salary received and acknowledged</p>
                    <p class="text-xs text-green-600 mt-0.5">{{ $entry->acknowledged_at->format('M d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        @else
            @php
                $autoConfirmDate = $entry->payrollCutoff->finalized_at?->addDays(5);
            @endphp
            <div class="bg-white border border-gray-200 rounded-2xl p-5 space-y-4">
                <div>
                    <p class="text-sm font-semibold text-gray-800">Confirm Salary Receipt</p>
                    <p class="text-xs text-gray-500 mt-1">By tapping <strong>Confirm Receipt</strong> below, you acknowledge that you have received your salary for this payroll period.</p>
                </div>

                @if($entry->employee->user?->signature)
                    <div class="flex flex-col items-center gap-1">
                        <p class="text-xs text-gray-400">Your signature on file</p>
                        <img src="{{ $entry->employee->user->signature }}"
                             alt="Signature"
                             class="h-16 w-auto object-contain border border-gray-100 rounded-lg bg-gray-50 px-4 py-2">
                    </div>
                @endif

                <form method="POST" action="{{ route('staff.payslips.acknowledge', $entry) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition">
                        Confirm Receipt
                    </button>
                </form>

                @if($autoConfirmDate)
                    <p class="text-xs text-center text-red-400 italic">
                        If not confirmed, this will be automatically stamped on <strong>{{ $autoConfirmDate->format('M d, Y') }}</strong>.
                    </p>
                @endif
            </div>
        @endif

    </div>
</x-staff-layout>
