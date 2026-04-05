<x-app-layout>
    <x-slot name="title">Overtime Report</x-slot>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reports.overtime') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
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
                <label class="text-xs font-medium text-gray-500">Status</label>
                <select name="status"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="pending"  @selected($status === 'pending')>Pending</option>
                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">From</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">To</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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

            @if($branchId || $search || $status || $from !== now()->startOfMonth()->toDateString() || $to !== now()->toDateString())
                <a href="{{ route('reports.overtime') }}"
                   class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-200">
                    Clear
                </a>
            @endif

        </div>
    </form>

    {{-- Summary count --}}
    <p class="text-sm text-gray-500 mb-4">
        Showing <span class="font-semibold text-gray-800">{{ $grouped->count() }}</span>
        {{ Str::plural('employee', $grouped->count()) }} with overtime
        from <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($from)->format('M d, Y') }}</span>
        to <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</span>
    </p>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($grouped->isEmpty())
            <div class="p-10 text-center text-gray-400 text-sm">No overtime recorded for the selected period.</div>
        @else
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="w-10 px-4 py-3"></th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Employee</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Branch</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Position</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Occurrences</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Total OT Hours</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Pending</th>
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
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                    {{ $row['occurrences'] }}x
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-blue-600">
                                {{ number_format($row['total_ot_hours'], 2) }}h
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($row['pending_count'] > 0)
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        {{ $row['pending_count'] }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Detail rows --}}
                        <tr x-show="open" x-cloak>
                            <td colspan="7" class="px-0 py-0">
                                <table class="w-full text-xs bg-blue-50/40 border-t border-blue-100">
                                    <thead>
                                        <tr class="text-gray-500 border-b border-blue-100">
                                            <th class="py-2 pl-12 pr-4 text-left font-medium w-52">Date</th>
                                            <th class="py-2 px-4 text-left font-medium w-28">OT Hours</th>
                                            <th class="py-2 px-4 text-left font-medium w-36">OT End Time</th>
                                            <th class="py-2 px-4 text-left font-medium w-28">Status</th>
                                            <th class="py-2 px-4 text-left font-medium">Approved By</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-blue-100/60">
                                        @foreach($row['dtrs'] as $dtr)
                                            <tr>
                                                <td class="py-2 pl-12 pr-4 text-gray-700">
                                                    {{ \Carbon\Carbon::parse($dtr->date)->format('D, M d, Y') }}
                                                </td>
                                                <td class="py-2 px-4 font-semibold text-blue-600">
                                                    {{ number_format($dtr->overtime_hours, 2) }}h
                                                </td>
                                                <td class="py-2 px-4 text-gray-600">
                                                    {{ $dtr->ot_end_time ? \Carbon\Carbon::parse($dtr->ot_end_time)->format('h:i A') : '—' }}
                                                </td>
                                                <td class="py-2 px-4">
                                                    @if($dtr->ot_status === 'approved')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Approved</span>
                                                    @elseif($dtr->ot_status === 'pending')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Pending</span>
                                                    @elseif($dtr->ot_status === 'rejected')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Rejected</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-4 text-gray-600">
                                                    {{ $dtr->approvedBy?->name ?? '—' }}
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
