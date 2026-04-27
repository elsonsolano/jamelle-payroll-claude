<x-app-layout>
    <x-slot name="title">Absences Report</x-slot>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reports.absences') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">

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
                <label class="text-xs font-medium text-gray-500">Month</label>
                <select name="month"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected($month == $num)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Year</label>
                <select name="year"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
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
                <a href="{{ route('reports.absences') }}"
                   class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200">
                    Clear
                </a>
            @endif

        </div>
    </form>

    {{-- Summary count --}}
    <p class="text-sm text-gray-500 mb-4">
        Showing <span class="font-semibold text-gray-800">{{ count($grouped) }}</span>
        {{ Str::plural('employee', count($grouped)) }} with absences in
        <span class="font-semibold text-gray-800">{{ $months[$month] }} {{ $year }}</span>
    </p>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if(empty($grouped))
            <div class="p-10 text-center text-gray-400 text-sm">No absences recorded for the selected period.</div>
        @else
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="w-10 px-4 py-3"></th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Employee</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Branch</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Position</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Absences</th>
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
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    {{ $row['occurrences'] }}x
                                </span>
                            </td>
                        </tr>

                        {{-- Detail rows --}}
                        <tr x-show="open" x-cloak>
                            <td colspan="5" class="px-0 py-0">
                                <table class="w-full text-xs bg-red-50/40 border-t border-red-100">
                                    <thead>
                                        <tr class="text-gray-500 border-b border-red-100">
                                            <th class="py-2 pl-12 pr-4 text-left font-medium w-52">Date</th>
                                            <th class="py-2 px-4 text-left font-medium">Scheduled Shift</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-red-100/60">
                                        @foreach($row['absences'] as $absence)
                                            <tr>
                                                <td class="py-2 pl-12 pr-4 font-medium text-gray-700">
                                                    {{ \Carbon\Carbon::parse($absence['date'])->format('D, M d, Y') }}
                                                </td>
                                                <td class="py-2 px-4 text-gray-600">
                                                    @if($absence['work_start_time'] && $absence['work_end_time'])
                                                        {{ \Carbon\Carbon::parse($absence['work_start_time'])->format('h:i A') }}
                                                        –
                                                        {{ \Carbon\Carbon::parse($absence['work_end_time'])->format('h:i A') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                    </tbody>
                @endforeach

            </table>
        @endif
    </div>

</x-app-layout>
