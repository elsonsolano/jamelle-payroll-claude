<x-app-layout>
    <x-slot name="title">PHIC Report</x-slot>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reports.phic') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Month</label>
                <select name="month"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    @foreach($months as $value => $label)
                        <option value="{{ $value }}" @selected($month == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

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

            @if($branchId || $search || $month != now()->month || $year != now()->year)
                <a href="{{ route('reports.phic') }}"
                   class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200">
                    Clear
                </a>
            @endif

        </div>
    </form>

    {{-- Summary --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            Showing <span class="font-semibold text-gray-800">{{ $grouped->count() }}</span>
            {{ Str::plural('employee', $grouped->count()) }} with finalized PHIC deductions for
            <span class="font-semibold text-gray-800">{{ $months[$month] }} {{ $year }}</span>
        </p>
        @if($grouped->isNotEmpty())
            <p class="text-sm text-gray-500">
                Grand Total:
                <span class="font-semibold text-emerald-700">₱{{ number_format($grandTotal, 2) }}</span>
            </p>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($grouped->isEmpty())
            <div class="p-10 text-center text-gray-400 text-sm">
                No finalized PHIC deductions found for {{ $months[$month] }} {{ $year }}.
            </div>
        @else
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Employee Name</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">1st Cutoff Salary</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">2nd Cutoff Salary</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Employee Share</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Employer Share</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @foreach($grouped as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $row['employee']->full_name }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">₱{{ number_format($row['partner_basic_pay'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">₱{{ number_format($row['current_basic_pay'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">₱{{ number_format($row['employee_share'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">₱{{ number_format($row['employer_share'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-700">Grand Total</td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                            ₱{{ number_format($grouped->sum('partner_basic_pay'), 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                            ₱{{ number_format($grouped->sum('current_basic_pay'), 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                            ₱{{ number_format($grouped->sum('employee_share'), 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                            ₱{{ number_format($grouped->sum('employer_share'), 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-emerald-700">
                            Grand Total: ₱{{ number_format($grandTotal, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

</x-app-layout>
