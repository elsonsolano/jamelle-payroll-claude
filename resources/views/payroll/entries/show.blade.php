<x-app-layout>
    <x-slot name="title">Payslip — {{ $entry->employee->full_name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('payroll.cutoffs.show', $cutoff) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to Cutoff
        </a>
        <a href="{{ route('payroll.cutoffs.entries.pdf', [$cutoff, $entry]) }}"
           target="_blank"
           class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            Download PDF
        </a>
    </x-slot>

    <div class="max-w-2xl space-y-5">

        {{-- Header --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-bold flex-shrink-0">
                    {{ strtoupper(substr($entry->employee->first_name, 0, 1) . substr($entry->employee->last_name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-gray-900">{{ $entry->employee->full_name }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ $entry->employee->position ?? 'No position' }} · {{ $entry->employee->branch->name }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ ucfirst($entry->employee->salary_type) }} rate ·
                        ₱{{ number_format($entry->employee->rate, 2) }} / {{ $entry->employee->salary_type === 'daily' ? 'day' : 'month' }}
                    </p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-6 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Cutoff Period</p>
                    <p class="font-medium text-gray-800">{{ $cutoff->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Date Range</p>
                    <p class="font-medium text-gray-800">
                        {{ $cutoff->start_date->format('M d') }} – {{ $cutoff->end_date->format('M d, Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Days Worked</p>
                    <p class="font-medium text-gray-800">{{ $entry->working_days }} days</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Total Hours</p>
                    <p class="font-medium text-gray-800">{{ number_format($entry->total_hours_worked, 2) }}h</p>
                </div>
            </div>
        </div>

        {{-- Earnings --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider">Earnings</h3>
            </div>
            <div class="divide-y divide-gray-100">
                <div class="px-5 py-3 flex justify-between text-sm">
                    <span class="text-gray-600">Basic Pay</span>
                    <span class="font-medium text-gray-900">₱{{ number_format($entry->basic_pay, 2) }}</span>
                </div>
                <div class="px-5 py-3 flex justify-between text-sm">
                    <span class="text-gray-600">Overtime Pay</span>
                    <span class="font-medium {{ $entry->overtime_pay > 0 ? 'text-indigo-600' : 'text-gray-300' }}">
                        ₱{{ number_format($entry->overtime_pay, 2) }}
                    </span>
                </div>
                <div class="px-5 py-3 flex justify-between text-sm">
                    <span class="text-gray-600">Holiday Pay</span>
                    <span class="font-medium {{ $entry->holiday_pay > 0 ? 'text-green-600' : 'text-gray-300' }}">
                        ₱{{ number_format($entry->holiday_pay, 2) }}
                    </span>
                </div>
                <div class="px-5 py-3 flex justify-between text-sm bg-gray-50">
                    <span class="font-semibold text-gray-700">Gross Pay</span>
                    <span class="font-bold text-gray-900">₱{{ number_format($entry->gross_pay, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Deductions --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider">Deductions</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @if($entry->late_deduction > 0)
                    <div class="px-5 py-3 flex justify-between text-sm">
                        <span class="text-gray-600">Late Deduction</span>
                        <span class="text-red-500">- ₱{{ number_format($entry->late_deduction, 2) }}</span>
                    </div>
                @endif
                @if($entry->undertime_deduction > 0)
                    <div class="px-5 py-3 flex justify-between text-sm">
                        <span class="text-gray-600">Undertime Deduction</span>
                        <span class="text-red-500">- ₱{{ number_format($entry->undertime_deduction, 2) }}</span>
                    </div>
                @endif
                @foreach($entry->payrollDeductions as $deduction)
                    <div class="px-5 py-3 flex justify-between text-sm">
                        <span class="text-gray-600">
                            {{ $deduction->type }}
                            @if($deduction->description)
                                <span class="text-gray-400 text-xs">({{ $deduction->description }})</span>
                            @endif
                        </span>
                        <span class="text-red-500">- ₱{{ number_format($deduction->amount, 2) }}</span>
                    </div>
                @endforeach
                @if($entry->total_deductions == 0)
                    <div class="px-5 py-4 text-center text-sm text-gray-400">No deductions.</div>
                @endif
                <div class="px-5 py-3 flex justify-between text-sm bg-gray-50">
                    <span class="font-semibold text-gray-700">Total Deductions</span>
                    <span class="font-bold text-red-500">- ₱{{ number_format($entry->total_deductions, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Net Pay --}}
        <div class="bg-indigo-600 rounded-xl p-5 flex items-center justify-between">
            <p class="text-white font-semibold text-lg">Net Pay</p>
            <p class="text-white font-bold text-3xl">₱{{ number_format($entry->net_pay, 2) }}</p>
        </div>

    </div>

</x-app-layout>
