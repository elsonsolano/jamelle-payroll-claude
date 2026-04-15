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

    <div class="flex gap-6 items-start">
    {{-- Left: payslip --}}
    <div class="flex-1 min-w-0 max-w-2xl space-y-5">

        {{-- Header --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-bold flex-shrink-0">
                    {{ strtoupper(substr($entry->employee->first_name, 0, 1) . substr($entry->employee->last_name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <h2>
                        <a href="{{ route('employees.show', $entry->employee) }}"
                           class="text-lg font-bold text-gray-900 hover:text-indigo-600 transition">
                            {{ $entry->employee->full_name }}
                        </a>
                    </h2>
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
                    <p class="font-medium text-gray-800">{{ number_format($entry->working_days, 2) }} days</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Regular Hours</p>
                    <p class="font-medium text-gray-800">{{ number_format($entry->total_hours_worked, 2) }}h</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Overtime Hours</p>
                    <p class="font-medium {{ $entry->total_overtime_hours > 0 ? 'text-indigo-600' : 'text-gray-800' }}">{{ number_format($entry->total_overtime_hours, 2) }}h</p>
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
                <div class="px-5 py-3 flex justify-between text-sm">
                    <span class="text-gray-600">Allowance
                        @if($entry->allowance_pay > 0)
                            <span class="text-xs text-gray-400">({{ $entry->working_days }} day(s) worked)</span>
                        @endif
                    </span>
                    <span class="font-medium {{ $entry->allowance_pay > 0 ? 'text-emerald-600' : 'text-gray-300' }}">
                        ₱{{ number_format($entry->allowance_pay, 2) }}
                    </span>
                </div>
                <div class="px-5 py-3 flex justify-between text-sm bg-gray-50">
                    <span class="font-semibold text-gray-700">Gross Pay</span>
                    <span class="font-bold text-gray-900">₱{{ number_format($entry->gross_pay, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Deductions --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ showAddVarDeduction: false }">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider">Deductions</h3>
                <button @click="showAddVarDeduction = !showAddVarDeduction"
                        class="text-xs text-red-600 hover:text-red-800 font-medium">+ Add Deduction</button>
            </div>

            {{-- Add Variable Deduction Form --}}
            <div x-show="showAddVarDeduction" x-cloak class="px-5 py-4 border-b border-gray-100 bg-red-50/40">
                <form method="POST" action="{{ route('payroll.cutoffs.entries.variable-deductions.store', [$cutoff, $entry]) }}"
                      class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                        <input type="text" name="description" placeholder="e.g. SSS Adjustment, Uniform"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400">
                    </div>
                    <div class="w-36">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount (₱)</label>
                        <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400">
                    </div>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                        Add
                    </button>
                    <button type="button" @click="showAddVarDeduction = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</button>
                </form>
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
                @foreach($entry->payrollVariableDeductions as $varDeduction)
                    <div x-data="{ editing: false }" class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="text-gray-600">{{ $varDeduction->description }}</span>
                        <div class="flex items-center gap-3">
                            {{-- View mode --}}
                            <template x-if="!editing">
                                <div class="flex items-center gap-3">
                                    <span class="text-red-500">- ₱{{ number_format($varDeduction->amount, 2) }}</span>
                                    <button @click="editing = true" class="text-xs text-blue-500 hover:text-blue-700">Edit</button>
                                    <form method="POST" action="{{ route('payroll.cutoffs.entries.variable-deductions.destroy', [$cutoff, $entry, $varDeduction]) }}"
                                          onsubmit="return confirm('Remove this deduction?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-400 hover:text-red-600">Remove</button>
                                    </form>
                                </div>
                            </template>
                            {{-- Edit mode --}}
                            <template x-if="editing">
                                <form method="POST" action="{{ route('payroll.cutoffs.entries.variable-deductions.update', [$cutoff, $entry, $varDeduction]) }}"
                                      class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <span class="text-gray-400">₱</span>
                                    <input type="number" name="amount" value="{{ $varDeduction->amount }}"
                                           min="0" step="0.01"
                                           class="w-28 border border-gray-300 rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-blue-400">
                                    <button type="submit" class="text-xs text-blue-600 font-medium hover:text-blue-800">Save</button>
                                    <button type="button" @click="editing = false" class="text-xs text-gray-400 hover:text-gray-600">Cancel</button>
                                </form>
                            </template>
                        </div>
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

        {{-- Refunds --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ showAddRefund: false }">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wider">Refunds</h3>
                <button @click="showAddRefund = !showAddRefund"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Add Refund</button>
            </div>

            {{-- Add Refund Form --}}
            <div x-show="showAddRefund" x-cloak class="px-5 py-4 border-b border-gray-100 bg-indigo-50/40">
                <form method="POST" action="{{ route('payroll.cutoffs.entries.refunds.store', [$cutoff, $entry]) }}"
                      class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                        <input type="text" name="description" placeholder="e.g. SSS Over-deduction"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="w-36">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount (₱)</label>
                        <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Add
                    </button>
                    <button type="button" @click="showAddRefund = false"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</button>
                </form>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($entry->payrollRefunds as $refund)
                    <div class="px-5 py-3 flex justify-between items-center text-sm">
                        <span class="text-gray-600">{{ $refund->description }}</span>
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-emerald-600">+ ₱{{ number_format($refund->amount, 2) }}</span>
                            <form method="POST" action="{{ route('payroll.cutoffs.entries.refunds.destroy', [$cutoff, $entry, $refund]) }}"
                                  onsubmit="return confirm('Remove this refund?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-4 text-center text-sm text-gray-400">No refunds.</div>
                @endforelse

                @if($entry->payrollRefunds->isNotEmpty())
                    <div class="px-5 py-3 flex justify-between text-sm bg-gray-50">
                        <span class="font-semibold text-gray-700">Total Refunds</span>
                        <span class="font-bold text-emerald-600">+ ₱{{ number_format($entry->payrollRefunds->sum('amount'), 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Net Pay --}}
        <div class="bg-indigo-600 rounded-xl p-5 flex items-center justify-between">
            <p class="text-white font-semibold text-lg">Net Pay</p>
            <p class="text-white font-bold text-3xl">₱{{ number_format($entry->net_pay, 2) }}</p>
        </div>

    </div>{{-- end left column --}}

    {{-- Right: Calculation Breakdown --}}
    <div class="w-96 flex-shrink-0 space-y-4 text-sm sticky top-6">
        <h3 class="font-bold text-gray-700 text-base">Calculation Breakdown</h3>

        @php
            $b = $breakdown;
            $rateLabel = $b['salary_type'] === 'daily'
                ? '₱' . number_format($b['rate'], 2) . '/day  (₱' . number_format($b['hourly_rate'], 4) . '/hr)'
                : '₱' . number_format($b['rate'], 2) . '/month';
        @endphp
        <p class="text-xs text-gray-500">Rate: {{ $rateLabel }}</p>

        {{-- Basic Pay --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <span class="font-semibold text-gray-700 text-xs uppercase tracking-wider">Basic Pay</span>
                <span class="font-bold text-gray-900">₱{{ number_format($entry->basic_pay, 2) }}</span>
            </div>
            <div class="px-4 py-3 space-y-3">

                @if($b['salary_type'] === 'daily')
                    {{-- DTR table --}}
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-1">DTR Summary</p>
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-gray-400 border-b border-gray-100">
                                    <th class="text-left pb-1">Date</th>
                                    <th class="text-right pb-1">Actual</th>
                                    <th class="text-right pb-1">Billable</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($b['dtr_rows'] as $row)
                                <tr class="{{ $row['is_rest'] ? 'text-amber-600' : 'text-gray-700' }}">
                                    <td class="py-0.5">
                                        {{ $row['date'] }} <span class="text-gray-400">{{ $row['day'] }}</span>
                                        @if($row['holiday']) <span class="text-green-600">*</span> @endif
                                        @if($row['late_mins'] > 0) <span class="text-red-400 text-[10px]">-{{ $row['late_mins'] }}m</span> @endif
                                    </td>
                                    <td class="py-0.5 text-right">{{ number_format($row['hours'], 2) }}h</td>
                                    <td class="py-0.5 text-right font-medium">{{ number_format($row['billable'], 2) }}h</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-gray-200 text-gray-600">
                                <tr>
                                    <td class="pt-1 font-medium">Total</td>
                                    <td></td>
                                    <td class="pt-1 text-right font-medium">{{ number_format($b['total_billable'], 2) }}h</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Working days formula --}}
                    <div class="bg-gray-50 rounded-lg px-3 py-2 text-xs text-gray-600 space-y-0.5">
                        <p>Working days = floor({{ number_format($b['total_billable'], 2) }}h ÷ 8 × 100) ÷ 100</p>
                        <p class="font-semibold text-gray-800">= {{ number_format($b['working_days'], 2) }} days</p>
                    </div>

                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600">{{ number_format($b['working_days'], 2) }} days × ₱{{ number_format($b['rate'], 2) }}</span>
                        <span class="font-medium text-gray-800">₱{{ number_format($b['base_pay'], 2) }}</span>
                    </div>

                    @foreach($b['unworked_holidays'] as $uh)
                    <div class="flex justify-between text-xs text-green-700">
                        <span>+ {{ $uh['date'] }} {{ $uh['name'] }} (unworked regular holiday)</span>
                        <span class="font-medium">₱{{ number_format($uh['amount'], 2) }}</span>
                    </div>
                    @endforeach

                    @if(count($b['unworked_holidays']) > 0)
                    <div class="flex justify-between text-xs border-t border-gray-100 pt-1 font-semibold text-gray-800">
                        <span>Basic Pay Total</span>
                        <span>₱{{ number_format($entry->basic_pay, 2) }}</span>
                    </div>
                    @endif

                @else
                    <div class="text-xs text-gray-600">
                        Monthly: ₱{{ number_format($b['rate'], 2) }} ÷ 2 (semi-monthly)
                        = <span class="font-semibold text-gray-800">₱{{ number_format($b['base_pay'], 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Overtime Pay --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <span class="font-semibold text-gray-700 text-xs uppercase tracking-wider">Overtime Pay</span>
                <span class="font-bold {{ $entry->overtime_pay > 0 ? 'text-indigo-600' : 'text-gray-300' }}">₱{{ number_format($entry->overtime_pay, 2) }}</span>
            </div>
            <div class="px-4 py-3">
                @if(count($b['ot_rows']) === 0)
                    <p class="text-xs text-gray-400">No overtime this period.</p>
                @else
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-gray-400 border-b border-gray-100">
                                <th class="text-left pb-1">Date</th>
                                <th class="text-right pb-1">Hrs</th>
                                <th class="text-right pb-1">× Rate</th>
                                <th class="text-right pb-1">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-gray-700">
                            @foreach($b['ot_rows'] as $row)
                            <tr>
                                <td class="py-0.5">
                                    {{ $row['date'] }}
                                    @if(!empty($row['holiday'])) <span class="text-green-600 text-[10px]">{{ $row['holiday'] }}</span> @endif
                                </td>
                                <td class="py-0.5 text-right">{{ $row['hours'] }}</td>
                                <td class="py-0.5 text-right text-gray-500">×{{ $row['mult'] }}</td>
                                <td class="py-0.5 text-right font-medium">₱{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-gray-400 mt-2">Rate per OT hr = ₱{{ number_format($b['hourly_rate'], 4) }} × multiplier</p>
                @endif
            </div>
        </div>

        {{-- Holiday Pay --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <span class="font-semibold text-gray-700 text-xs uppercase tracking-wider">Holiday Pay</span>
                <span class="font-bold {{ $entry->holiday_pay > 0 ? 'text-green-600' : 'text-gray-300' }}">₱{{ number_format($entry->holiday_pay, 2) }}</span>
            </div>
            <div class="px-4 py-3">
                @if(count($b['holiday_rows']) === 0)
                    <p class="text-xs text-gray-400">No holiday premiums this period.</p>
                @else
                    <div class="space-y-2">
                        @foreach($b['holiday_rows'] as $row)
                        <div class="text-xs border-b border-gray-50 pb-2 last:border-0 last:pb-0">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">{{ $row['date'] }} — {{ $row['name'] }}</span>
                                <span class="font-semibold text-green-700">₱{{ number_format($row['amount'], 2) }}</span>
                            </div>
                            <div class="text-gray-400 mt-0.5">
                                @if($row['hours'] !== null)
                                    @if($row['type'] === 'regular')
                                        Regular holiday worked: {{ number_format($row['hours'], 2) }}h × ₱{{ number_format($b['hourly_rate'], 4) }} × 100%
                                    @else
                                        Special non-working worked: {{ number_format($row['hours'], 2) }}h × ₱{{ number_format($b['hourly_rate'], 4) }} × 30%
                                    @endif
                                @else
                                    @if($row['type'] === 'regular')
                                        Regular holiday worked: ₱{{ number_format($b['daily_equiv'] ?? 0, 4) }}/day × 100%
                                    @else
                                        Special non-working worked: ₱{{ number_format($b['daily_equiv'] ?? 0, 4) }}/day × 30%
                                    @endif
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif

                @if(count($b['unworked_holidays'] ?? []) > 0)
                    <div class="mt-2 pt-2 border-t border-gray-100">
                        <p class="text-xs text-gray-400">Unworked regular holidays are added to Basic Pay, not here.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Net Pay formula --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 px-4 py-3 text-xs space-y-1 text-gray-600">
            <p class="font-semibold text-gray-700 mb-1">Net Pay Formula</p>
            <div class="flex justify-between"><span>Basic Pay</span><span>₱{{ number_format($entry->basic_pay, 2) }}</span></div>
            <div class="flex justify-between"><span>+ Overtime Pay</span><span>₱{{ number_format($entry->overtime_pay, 2) }}</span></div>
            <div class="flex justify-between"><span>+ Holiday Pay</span><span>₱{{ number_format($entry->holiday_pay, 2) }}</span></div>
            <div class="flex justify-between"><span>+ Allowance</span><span>₱{{ number_format($entry->allowance_pay, 2) }}</span></div>
            <div class="flex justify-between font-semibold text-gray-800 border-t border-gray-200 pt-1"><span>= Gross Pay</span><span>₱{{ number_format($entry->gross_pay, 2) }}</span></div>
            <div class="flex justify-between text-red-500"><span>− Deductions</span><span>₱{{ number_format($entry->total_deductions, 2) }}</span></div>
            @if($entry->payrollRefunds->isNotEmpty())
            <div class="flex justify-between text-emerald-600"><span>+ Refunds</span><span>₱{{ number_format($entry->payrollRefunds->sum('amount'), 2) }}</span></div>
            @endif
            <div class="flex justify-between font-bold text-indigo-700 border-t border-gray-200 pt-1"><span>= Net Pay</span><span>₱{{ number_format($entry->net_pay, 2) }}</span></div>
        </div>

    </div>{{-- end right column --}}

    </div>{{-- end flex wrapper --}}

</x-app-layout>
