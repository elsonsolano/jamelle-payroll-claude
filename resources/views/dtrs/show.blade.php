<x-app-layout>
    <x-slot name="title">DTR — {{ $dtr->employee->full_name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('dtr.index', ['employee_id' => $dtr->employee_id]) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to DTR
        </a>
    </x-slot>

    <div class="max-w-xl space-y-5">

        {{-- Header --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">DTR Record</p>
                    <h2 class="text-xl font-bold text-gray-900">{{ $dtr->date->format('F d, Y') }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $dtr->date->format('l') }}
                        @if($dtr->is_rest_day)
                            <span class="text-amber-600 font-medium">· Rest Day</span>
                        @endif
                    </p>
                </div>
                <span @class([
                    'text-sm font-medium px-3 py-1 rounded-full',
                    'bg-green-100 text-green-700'  => $dtr->status === 'Approved',
                    'bg-amber-100 text-amber-700'  => $dtr->status === 'Pending',
                    'bg-red-100 text-red-700'      => $dtr->status === 'Rejected',
                ])>{{ $dtr->status }}</span>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-sm font-semibold text-gray-800">{{ $dtr->employee->full_name }}</p>
                <p class="text-xs text-gray-400">{{ $dtr->employee->position ?? 'No position' }} · {{ $dtr->employee->branch->name }}</p>
            </div>
        </div>

        {{-- Punch Times --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Punch Times</h3>
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['label' => 'Time In',  'value' => $dtr->time_in,  'color' => 'green'],
                    ['label' => 'AM Out',   'value' => $dtr->am_out,   'color' => 'gray'],
                    ['label' => 'PM In',    'value' => $dtr->pm_in,    'color' => 'gray'],
                    ['label' => 'Time Out', 'value' => $dtr->time_out, 'color' => 'red'],
                ] as $punch)
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">{{ $punch['label'] }}</p>
                        <p class="text-lg font-bold {{ $punch['value'] ? 'text-gray-900' : 'text-gray-300' }}">
                            {{ $punch['value'] ? \Carbon\Carbon::parse($punch['value'])->format('h:i A') : '—' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Summary</h3>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500">Total Hours Worked</dt>
                    <dd class="text-xl font-bold text-gray-900 mt-0.5">{{ number_format($dtr->total_hours, 2) }}h</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Overtime Hours</dt>
                    <dd class="mt-0.5">
                        @if($dtr->overtime_hours > 0)
                            <span class="text-xl font-bold text-indigo-600">+{{ number_format($dtr->overtime_hours, 2) }}h</span>
                        @else
                            <span class="text-xl font-bold text-gray-300">0.00h</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Late (minutes)</dt>
                    <dd class="mt-0.5">
                        @if($dtr->late_mins > 0)
                            <span class="text-xl font-bold text-red-500">{{ $dtr->late_mins }}m</span>
                        @else
                            <span class="text-xl font-bold text-gray-300">0m</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Undertime (minutes)</dt>
                    <dd class="mt-0.5">
                        @if($dtr->undertime_mins > 0)
                            <span class="text-xl font-bold text-orange-500">{{ $dtr->undertime_mins }}m</span>
                        @else
                            <span class="text-xl font-bold text-gray-300">0m</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

    </div>

</x-app-layout>
