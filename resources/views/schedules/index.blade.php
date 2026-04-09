<x-app-layout>
    <x-slot name="title">Schedule — {{ $employee->full_name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ Auth::user()->isSuperAdmin() ? route('employees.show', $employee) : route('employees.index') }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to Employees
        </a>
    </x-slot>

    <div class="max-w-3xl space-y-6">

        {{-- Success flash --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

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


        {{-- Default Schedule --}}
        @php
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden"
             x-data="{ editing: {{ $errors->hasAny(['rest_days','work_start_time','work_end_time']) && !$defaultSchedule ? 'false' : ($errors->hasAny(['rest_days','work_start_time','work_end_time']) ? 'true' : 'false') }} }">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Default Schedule
                    <span class="text-xs font-normal text-gray-400 ml-1">applies when no specific daily schedule is set</span>
                </h2>
                @if($defaultSchedule)
                    <button @click="editing = !editing"
                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                            x-text="editing ? 'Cancel' : 'Edit'"></button>
                @endif
            </div>

            {{-- View state --}}
            @if($defaultSchedule)
                <div x-show="!editing" class="px-5 py-4 space-y-3">
                    <div class="flex gap-1.5 flex-wrap">
                        @foreach($days as $day)
                            @php $isRest = in_array($day, $defaultSchedule->rest_days ?? []); @endphp
                            <span @class([
                                'px-3 py-1.5 rounded-lg text-xs font-medium',
                                'bg-red-100 text-red-600'       => $isRest,
                                'bg-indigo-100 text-indigo-700' => !$isRest,
                            ])>{{ substr($day, 0, 3) }}</span>
                        @endforeach
                    </div>
                    @if($defaultSchedule->work_start_time && $defaultSchedule->work_end_time)
                        <p class="text-sm text-gray-700">
                            {{ \Carbon\Carbon::parse($defaultSchedule->work_start_time)->format('g:i A') }}
                            – {{ \Carbon\Carbon::parse($defaultSchedule->work_end_time)->format('g:i A') }}
                        </p>
                    @else
                        <p class="text-sm text-gray-400">Using branch default hours</p>
                    @endif
                    <form method="POST" action="{{ route('employees.schedules.destroyDefault', $employee) }}"
                          onsubmit="return confirm('Remove default schedule?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remove default schedule</button>
                    </form>
                </div>
            @else
                <div x-show="!editing" class="px-5 py-6 flex items-center justify-between">
                    <p class="text-sm text-gray-400">No default schedule set.</p>
                    <button @click="editing = true"
                            class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        Set Default Schedule
                    </button>
                </div>
            @endif

            {{-- Edit / Create form --}}
            <div x-show="editing" x-cloak class="border-t border-gray-100 px-5 py-5">
                <form method="POST" action="{{ route('employees.schedules.saveDefault', $employee) }}">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Rest Days <span class="text-red-500">*</span>
                                <span class="text-xs font-normal text-gray-400 ml-1">(select the day(s) off)</span>
                            </label>
                            @php
                                $defaultRestDays = old('rest_days', $defaultSchedule?->rest_days ?? []);
                            @endphp
                            <div class="flex flex-wrap gap-2">
                                @foreach($days as $day)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="rest_days[]" value="{{ $day }}"
                                               {{ in_array($day, $defaultRestDays) ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border transition
                                                     border-gray-300 bg-white text-gray-600
                                                     peer-checked:bg-red-100 peer-checked:text-red-700 peer-checked:border-red-300
                                                     hover:bg-gray-50">
                                            {{ substr($day, 0, 3) }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('rest_days') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Work Hours</label>
                            <p class="text-xs text-gray-400 mb-2">Leave blank to use the branch's default work hours.</p>
                            <div class="flex items-center gap-3">
                                <input type="time" name="work_start_time"
                                       value="{{ old('work_start_time', $defaultSchedule?->work_start_time ? \Carbon\Carbon::parse($defaultSchedule->work_start_time)->format('H:i') : '') }}"
                                       class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <span class="text-gray-400 text-sm">to</span>
                                <input type="time" name="work_end_time"
                                       value="{{ old('work_end_time', $defaultSchedule?->work_end_time ? \Carbon\Carbon::parse($defaultSchedule->work_end_time)->format('H:i') : '') }}"
                                       class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            @error('work_start_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('work_end_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Save
                        </button>
                        <button type="button" @click="editing = false"
                                class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Add Daily Schedule --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ open: {{ $errors->hasAny(['date','work_start_time','work_end_time','assigned_branch_id']) ? 'true' : 'false' }}, isDayOff: false }">
            <button @click="open = !open"
                    class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition">
                <span class="font-semibold text-gray-800">Add Daily Schedule</span>
                <svg :class="open ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-cloak class="border-t border-gray-100 p-5">
                <form method="POST" action="{{ route('employees.daily-schedules.store', $employee) }}">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                            <input type="date" name="date" value="{{ old('date') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 @error('date') border-red-300 @enderror" required>
                            @error('date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Branch Override</label>
                            <select name="assigned_branch_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                <option value="">— none (home branch) —</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('assigned_branch_id') == $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_day_off" value="1" x-model="isDayOff"
                                       @checked(old('is_day_off'))
                                       class="rounded border-gray-300 text-indigo-600">
                                Day Off
                            </label>
                        </div>
                        <div x-show="!isDayOff">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Work Start Time</label>
                            <input type="time" name="work_start_time" value="{{ old('work_start_time') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 @error('work_start_time') border-red-300 @enderror">
                            @error('work_start_time') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div x-show="!isDayOff">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Work End Time</label>
                            <input type="time" name="work_end_time" value="{{ old('work_end_time') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 @error('work_end_time') border-red-300 @enderror">
                            @error('work_end_time') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Save
                        </button>
                        <button type="button" @click="open = false"
                                class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Daily Schedules (from uploads) --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ editDailyId: null }">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Daily Schedules <span class="text-xs font-normal text-gray-400 ml-1">from schedule uploads</span></h2>
            </div>

            @forelse($dailySchedules as $daily)
                <div class="border-b border-gray-100 last:border-0">
                    {{-- View Row --}}
                    <div x-show="editDailyId !== {{ $daily->id }}" class="px-5 py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="text-center w-14">
                                <p class="text-xs text-gray-400">{{ $daily->date->format('D') }}</p>
                                <p class="font-semibold text-gray-900 text-sm">{{ $daily->date->format('M d') }}</p>
                                <p class="text-xs text-gray-400">{{ $daily->date->format('Y') }}</p>
                            </div>
                            <div>
                                @if($daily->is_day_off)
                                    <span class="text-sm font-medium text-orange-600">Day Off</span>
                                @else
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $daily->work_start_time ? \Carbon\Carbon::parse($daily->work_start_time)->format('g:i A') : '—' }}
                                        –
                                        {{ $daily->work_end_time ? \Carbon\Carbon::parse($daily->work_end_time)->format('g:i A') : '—' }}
                                    </p>
                                @endif
                                @if($daily->notes)
                                    <p class="text-xs text-amber-600 mt-0.5">{{ $daily->notes }}</p>
                                @endif
                                @if($daily->assignedBranch)
                                    <p class="text-xs text-indigo-600 mt-0.5">Branch: {{ $daily->assignedBranch->name }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <button @click="editDailyId = {{ $daily->id }}"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                            <form method="POST" action="{{ route('employees.daily-schedules.destroy', [$employee, $daily]) }}"
                                  onsubmit="return confirm('Delete this daily schedule?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                            </form>
                        </div>
                    </div>

                    {{-- Inline Edit Form --}}
                    <div x-show="editDailyId === {{ $daily->id }}" x-cloak class="px-5 py-4 bg-indigo-50/50">
                        <p class="text-sm font-semibold text-gray-700 mb-4">Edit Daily Schedule</p>
                        <form method="POST" action="{{ route('employees.daily-schedules.update', [$employee, $daily]) }}" x-data="{ isDayOff: {{ $daily->is_day_off ? 'true' : 'false' }} }">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                                    <input type="date" name="date" value="{{ $daily->date->format('Y-m-d') }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Branch Override</label>
                                    <select name="assigned_branch_id"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                        <option value="">— none (home branch) —</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected($daily->assigned_branch_id === $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700 mb-3">
                                        <input type="checkbox" name="is_day_off" value="1"
                                               x-model="isDayOff"
                                               @checked($daily->is_day_off)
                                               class="rounded border-gray-300 text-indigo-600">
                                        Day Off
                                    </label>
                                </div>
                                <div x-show="!isDayOff">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Work Start Time</label>
                                    <input type="time" name="work_start_time"
                                           value="{{ $daily->work_start_time ? \Carbon\Carbon::parse($daily->work_start_time)->format('H:i') : '' }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div x-show="!isDayOff">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Work End Time</label>
                                    <input type="time" name="work_end_time"
                                           value="{{ $daily->work_end_time ? \Carbon\Carbon::parse($daily->work_end_time)->format('H:i') : '' }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-200">
                                <button type="submit"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                    Update
                                </button>
                                <button type="button" @click="editDailyId = null"
                                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-5 py-6 text-center text-sm text-gray-400">
                    No daily schedules from uploads yet.
                </div>
            @endforelse
        </div>

        {{-- Schedule History (hidden — managed via Default Schedule card above) --}}
        {{-- <div class="bg-white rounded-xl border border-gray-200 overflow-hidden"> --}}
        @if(false)
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
        @endif {{-- end hidden Schedule History --}}

    </div>

</x-app-layout>
