<x-app-layout>
    <x-slot name="title">13th Month Pay Report</x-slot>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reports.thirteenth-month') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Year</label>
                <select name="year"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Branch</label>
                <select name="branch_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Employee name..."
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-48">
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Apply
            </button>

            @if($branchId || $search || $year != now()->year)
                <a href="{{ route('reports.thirteenth-month') }}"
                   class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200">
                    Clear
                </a>
            @endif

        </div>
    </form>

    {{-- Formula info --}}
    <div class="bg-teal-50 border border-teal-200 rounded-xl p-4 mb-5 text-sm text-teal-900">
        <p class="font-semibold mb-1">How is 13th Month Pay calculated?</p>
        <p class="text-teal-800">
            Under <span class="font-medium">Presidential Decree No. 851</span>, the formula is:
        </p>
        <p class="mt-2 font-mono bg-white border border-teal-200 rounded-lg px-4 py-2 text-teal-700 inline-block">
            13th Month Pay = Total Basic Salary Earned in the Year ÷ 12
        </p>
        <p class="mt-2 text-teal-700 text-xs">
            Only <span class="font-semibold">basic pay</span> is counted — overtime pay, holiday pay, and allowances are excluded.
            This report sums the basic pay from all <span class="font-semibold">finalized</span> payroll cutoffs within the selected year, then divides by 12.
        </p>
    </div>

    {{-- Summary --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            Showing <span class="font-semibold text-gray-800">{{ $grouped->count() }}</span>
            {{ Str::plural('employee', $grouped->count()) }} with finalized payroll for
            <span class="font-semibold text-gray-800">{{ $year }}</span>
        </p>
        @if($grouped->isNotEmpty())
            <p class="text-sm text-gray-500">
                Total 13th Month:
                <span class="font-semibold text-teal-700">
                    ₱{{ number_format($grouped->sum('thirteenth_month'), 2) }}
                </span>
            </p>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($grouped->isEmpty())
            <div class="p-10 text-center text-gray-400 text-sm">No finalized payroll entries found for {{ $year }}.</div>
        @else
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="w-10 px-4 py-3"></th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Employee</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Branch</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Position</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Cutoffs</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Total Basic Pay</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">13th Month Pay</th>
                    </tr>
                </thead>

                @foreach($grouped as $row)
                    <tbody x-data="{ open: false }" class="border-b border-gray-100 last:border-b-0">

                        {{-- Summary row --}}
                        <tr class="hover:bg-gray-50 cursor-pointer" @click="open = !open">
                            <td class="px-4 py-3 text-center text-gray-400">
                                <svg class="w-4 h-4 inline transition-transform duration-150"
                                     :class="{ 'rotate-90': open }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $row['employee']->full_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['employee']->branch->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['employee']->position ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                    {{ $row['cutoff_count'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700">
                                ₱{{ number_format($row['total_basic_pay'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-teal-700">
                                ₱{{ number_format($row['thirteenth_month'], 2) }}
                            </td>
                        </tr>

                        {{-- Detail rows --}}
                        <tr x-show="open" x-cloak>
                            <td colspan="7" class="px-0 py-0">
                                <table class="w-full text-xs bg-teal-50/40 border-t border-teal-100">
                                    <thead>
                                        <tr class="text-gray-500 border-b border-teal-100">
                                            <th class="py-2 pl-12 pr-4 text-left font-medium w-64">Cutoff Period</th>
                                            <th class="py-2 px-4 text-right font-medium">Basic Pay</th>
                                            <th class="py-2 px-4 text-right font-medium">Allocation (÷12)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-teal-100/60">
                                        @foreach($row['entries'] as $entry)
                                            <tr>
                                                <td class="py-2 pl-12 pr-4 text-gray-700 font-medium">
                                                    {{ $entry->payrollCutoff->name }}
                                                    <span class="text-gray-400 font-normal ml-1">
                                                        ({{ \Carbon\Carbon::parse($entry->payrollCutoff->start_date)->format('M d') }}–{{ \Carbon\Carbon::parse($entry->payrollCutoff->end_date)->format('M d, Y') }})
                                                    </span>
                                                </td>
                                                <td class="py-2 px-4 text-right text-gray-600">
                                                    ₱{{ number_format($entry->basic_pay, 2) }}
                                                </td>
                                                <td class="py-2 px-4 text-right font-semibold text-teal-700">
                                                    ₱{{ number_format($entry->basic_pay / 12, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                    </tbody>
                @endforeach

                {{-- Grand total footer --}}
                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-700">Grand Total</td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                            ₱{{ number_format($grouped->sum('total_basic_pay'), 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-teal-700">
                            ₱{{ number_format($grouped->sum('thirteenth_month'), 2) }}
                        </td>
                    </tr>
                </tfoot>

            </table>
        @endif
    </div>

</x-app-layout>
