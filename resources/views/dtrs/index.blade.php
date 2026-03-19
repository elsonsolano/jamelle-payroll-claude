<x-app-layout>
    <x-slot name="title">DTR Records</x-slot>

    {{-- Filters --}}
    <form method="GET" action="{{ route('dtr.index') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Branch</label>
                <select name="branch_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Employee</label>
                <select name="employee_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-48">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Cutoff Period</label>
                <select name="cutoff_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-52">
                    <option value="">— or select cutoff —</option>
                    @foreach($cutoffs as $cutoff)
                        <option value="{{ $cutoff->id }}" @selected(request('cutoff_id') == $cutoff->id)>
                            {{ $cutoff->name }} ({{ $cutoff->branch->name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg transition">
                Filter
            </button>

            @if(request()->hasAny(['employee_id','branch_id','cutoff_id','date_from','date_to']))
                <a href="{{ route('dtr.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-800">Clear</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $dtrs->total() }} record(s) found</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left">
                        <th class="px-4 py-3 font-semibold text-gray-600">Employee</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Time In</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">AM Out</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">PM In</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Time Out</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Hours</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">OT</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Late</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Status</th>
                        <th class="px-4 py-3 font-semibold text-gray-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dtrs as $dtr)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $dtr->employee->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $dtr->employee->branch->name }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                <p class="font-medium">{{ $dtr->date->format('M d, Y') }}</p>
                                <p class="text-xs text-gray-400">{{ $dtr->date->format('l') }}
                                    @if($dtr->is_rest_day)
                                        <span class="text-amber-600 font-medium">· Rest Day</span>
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-700">
                                {{ $dtr->time_in ? \Carbon\Carbon::parse($dtr->time_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">
                                {{ $dtr->am_out ? \Carbon\Carbon::parse($dtr->am_out)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">
                                {{ $dtr->pm_in ? \Carbon\Carbon::parse($dtr->pm_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-700">
                                {{ $dtr->time_out ? \Carbon\Carbon::parse($dtr->time_out)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-gray-800">{{ number_format($dtr->total_hours, 2) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($dtr->overtime_hours > 0)
                                    <span class="text-xs font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">
                                        +{{ number_format($dtr->overtime_hours, 2) }}h
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($dtr->late_mins > 0)
                                    <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded-full">
                                        {{ $dtr->late_mins }}m
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'text-xs font-medium px-2 py-0.5 rounded-full',
                                    'bg-green-100 text-green-700'  => $dtr->status === 'Approved',
                                    'bg-amber-100 text-amber-700'  => $dtr->status === 'Pending',
                                    'bg-red-100 text-red-700'      => $dtr->status === 'Rejected',
                                ])>{{ $dtr->status }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('dtr.show', $dtr) }}"
                                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-5 py-10 text-center text-gray-400">
                                No DTR records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dtrs->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $dtrs->links() }}
            </div>
        @endif
    </div>

</x-app-layout>
