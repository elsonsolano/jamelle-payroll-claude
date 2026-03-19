<x-app-layout>
    <x-slot name="title">Schedule — {{ $employee->full_name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('employees.show', $employee) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to Employee
        </a>
    </x-slot>

    <div class="max-w-3xl space-y-6" x-data="{ showAddForm: {{ $errors->any() ? 'true' : 'false' }}, editId: null }">

        {{-- Employee Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold flex-shrink-0">
                {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
            </div>
            <div class="flex-1">
                <p class="font-semibold text-gray-900">{{ $employee->full_name }}</p>
                <p class="text-xs text-gray-400">{{ $employee->position ?? 'No position' }} · {{ $employee->branch->name }}</p>
            </div>
            <div class="text-right text-xs text-gray-400">
                <p>Branch hours</p>
                <p class="font-medium text-gray-600">
                    {{ \Carbon\Carbon::parse($employee->branch->work_start_time)->format('g:i A') }}
                    – {{ \Carbon\Carbon::parse($employee->branch->work_end_time)->format('g:i A') }}
                </p>
            </div>
        </div>

        {{-- Add Schedule --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <button @click="showAddForm = !showAddForm"
                    class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition">
                <span class="font-semibold text-gray-800">Add Week Schedule</span>
                <svg :class="showAddForm ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="showAddForm" x-cloak class="border-t border-gray-100 p-5">
                <form method="POST" action="{{ route('employees.schedules.store', $employee) }}">
                    @csrf
                    @include('schedules._form', ['schedule' => null])
                    <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Save Schedule
                        </button>
                        <button type="button" @click="showAddForm = false"
                                class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Schedule History --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Schedule History</h2>
            </div>

            @forelse($schedules as $schedule)
                <div class="border-b border-gray-100 last:border-0" x-data>
                    {{-- Schedule Row --}}
                    <div x-show="editId !== {{ $schedule->id }}" class="px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                {{-- Week Range --}}
                                <div class="flex items-center gap-3 mb-3">
                                    <p class="font-medium text-gray-900">
                                        Week of {{ $schedule->week_start_date->format('M d, Y') }}
                                    </p>
                                    <span class="text-xs text-gray-400">
                                        – {{ $schedule->week_start_date->addDays(6)->format('M d, Y') }}
                                    </span>
                                </div>

                                {{-- Week Calendar --}}
                                <div class="flex gap-1.5">
                                    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dayShort)
                                        @php
                                            $fullDay = match($dayShort) {
                                                'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday',
                                                'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday',
                                                'Sun' => 'Sunday'
                                            };
                                            $isRest = in_array($fullDay, $schedule->rest_days ?? []);
                                        @endphp
                                        <div @class([
                                            'w-9 h-9 rounded-lg flex items-center justify-center text-xs font-medium',
                                            'bg-red-100 text-red-600'      => $isRest,
                                            'bg-indigo-100 text-indigo-700' => !$isRest,
                                        ])>
                                            {{ $dayShort }}
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Time override --}}
                                @if($schedule->work_start_time && $schedule->work_end_time)
                                    <p class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Custom hours:
                                        <span class="font-medium text-gray-700">
                                            {{ \Carbon\Carbon::parse($schedule->work_start_time)->format('g:i A') }}
                                            – {{ \Carbon\Carbon::parse($schedule->work_end_time)->format('g:i A') }}
                                        </span>
                                    </p>
                                @else
                                    <p class="mt-2 text-xs text-gray-400">Using branch default hours</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 flex-shrink-0">
                                <button @click="editId = {{ $schedule->id }}"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('employees.schedules.destroy', [$employee, $schedule]) }}"
                                      onsubmit="return confirm('Delete this schedule?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Inline Edit Form --}}
                    <div x-show="editId === {{ $schedule->id }}" x-cloak class="px-5 py-4 bg-indigo-50/50">
                        <p class="text-sm font-semibold text-gray-700 mb-4">Edit Schedule</p>
                        <form method="POST" action="{{ route('employees.schedules.update', [$employee, $schedule]) }}">
                            @csrf @method('PUT')
                            @include('schedules._form', ['schedule' => $schedule])
                            <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-200">
                                <button type="submit"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                    Update
                                </button>
                                <button type="button" @click="editId = null"
                                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-gray-400">
                    No schedules set yet. Add the first week schedule above.
                </div>
            @endforelse
        </div>

    </div>

</x-app-layout>
