<x-app-layout>
    <x-slot name="title">{{ $cutoff->name }}</x-slot>

    <x-slot name="actions">
        <div class="flex items-center gap-2">
            @if($cutoff->status === 'draft')
                <a href="{{ route('payroll.cutoffs.edit', $cutoff) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Edit
                </a>
            @endif

            @if(in_array($cutoff->status, ['draft', 'finalized']))
                <form method="POST" action="{{ route('payroll.cutoffs.generate', $cutoff) }}"
                      onsubmit="return confirm('{{ $cutoff->status === 'finalized' ? 'This will regenerate payroll and overwrite existing entries. Continue?' : 'Generate payroll for all active employees in this branch?' }}')">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $cutoff->status === 'finalized' ? 'Regenerate Payroll' : 'Generate Payroll' }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Cutoff Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex flex-wrap items-center gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Branch</p>
                    <p class="font-semibold text-gray-800">{{ $cutoff->branch->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Period</p>
                    <p class="font-semibold text-gray-800">
                        {{ $cutoff->start_date->format('M d') }} – {{ $cutoff->end_date->format('M d, Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Duration</p>
                    <p class="font-semibold text-gray-800">{{ $cutoff->start_date->diffInDays($cutoff->end_date) + 1 }} days</p>
                </div>
                <div class="ml-auto">
                    <span @class([
                        'text-sm font-medium px-3 py-1 rounded-full',
                        'bg-gray-100 text-gray-600'   => $cutoff->status === 'draft',
                        'bg-amber-100 text-amber-700' => $cutoff->status === 'processing',
                        'bg-green-100 text-green-700' => $cutoff->status === 'finalized',
                    ])>{{ ucfirst($cutoff->status) }}</span>
                </div>
            </div>
        </div>

        @if($summary['total_employees'] > 0)
        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Employees</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['total_employees'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Total Basic Pay</p>
                <p class="text-2xl font-bold text-gray-900">₱{{ number_format($summary['total_basic_pay'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Total Deductions</p>
                <p class="text-2xl font-bold text-red-500">₱{{ number_format($summary['total_deductions'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Total Net Pay</p>
                <p class="text-2xl font-bold text-indigo-600">₱{{ number_format($summary['total_net_pay'], 2) }}</p>
            </div>
        </div>
        @endif

        {{-- Entries Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Payroll Entries</h2>
            </div>

            @if($entries->isEmpty())
                <div class="px-5 py-12 text-center">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-400 text-sm">No payroll entries yet.</p>
                    @if($cutoff->status === 'draft')
                        <p class="text-gray-400 text-sm mt-1">Click <span class="font-medium text-indigo-600">Generate Payroll</span> to compute payroll for all active employees.</p>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                                <th class="px-5 py-3 font-semibold text-gray-600">Employee</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Basic Pay</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Overtime</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Gross Pay</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Deductions</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Net Pay</th>
                                <th class="px-5 py-3 font-semibold text-gray-600"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($entries as $entry)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">{{ $entry->employee->full_name }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ ucfirst($entry->employee->salary_type) }} · {{ $entry->working_days }}d worked
                                        </p>
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-700">₱{{ number_format($entry->basic_pay, 2) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        @if($entry->overtime_pay > 0)
                                            <span class="text-indigo-600 font-medium">₱{{ number_format($entry->overtime_pay, 2) }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-medium text-gray-800">₱{{ number_format($entry->gross_pay, 2) }}</td>
                                    <td class="px-5 py-3 text-right text-red-500">
                                        @if($entry->total_deductions > 0)
                                            ₱{{ number_format($entry->total_deductions, 2) }}
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-bold text-indigo-600">₱{{ number_format($entry->net_pay, 2) }}</td>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('payroll.cutoffs.entries.show', [$cutoff, $entry]) }}"
                                           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td class="px-5 py-3 font-semibold text-gray-700">Total</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-800">₱{{ number_format($summary['total_basic_pay'], 2) }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-indigo-600">₱{{ number_format($summary['total_overtime'], 2) }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-800">₱{{ number_format($summary['total_basic_pay'] + $summary['total_overtime'], 2) }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-red-500">₱{{ number_format($summary['total_deductions'], 2) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-indigo-600">₱{{ number_format($summary['total_net_pay'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($entries->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">{{ $entries->links() }}</div>
                @endif
            @endif
        </div>

    </div>

</x-app-layout>
