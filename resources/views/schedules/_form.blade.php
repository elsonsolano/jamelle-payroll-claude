@php
    $restDays = old('rest_days', $schedule?->rest_days ?? []);
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
@endphp

<div class="space-y-5">

    {{-- Week Start Date --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Week Start Date <span class="text-red-500">*</span>
        </label>
        <input type="date" name="week_start_date"
               value="{{ old('week_start_date', $schedule?->week_start_date?->format('Y-m-d') ?? '') }}"
               class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
               required>
        <p class="mt-1 text-xs text-gray-400">Typically a Monday. The schedule applies to the 7-day week starting this date.</p>
        @error('week_start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Rest Days --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Rest Days <span class="text-red-500">*</span>
            <span class="text-xs font-normal text-gray-400 ml-1">(select the day(s) off)</span>
        </label>
        <div class="flex flex-wrap gap-2">
            @foreach($days as $day)
                <label class="cursor-pointer">
                    <input type="checkbox" name="rest_days[]" value="{{ $day }}"
                           {{ in_array($day, $restDays) ? 'checked' : '' }}
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
        @error('rest_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Work Time Override --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Work Hours Override</label>
        <p class="text-xs text-gray-400 mb-2">Leave blank to use the branch's default work hours.</p>
        <div class="flex items-center gap-3">
            <input type="time" name="work_start_time"
                   value="{{ old('work_start_time', $schedule?->work_start_time ? \Carbon\Carbon::parse($schedule->work_start_time)->format('H:i') : '') }}"
                   class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <span class="text-gray-400 text-sm">to</span>
            <input type="time" name="work_end_time"
                   value="{{ old('work_end_time', $schedule?->work_end_time ? \Carbon\Carbon::parse($schedule->work_end_time)->format('H:i') : '') }}"
                   class="rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        @error('work_start_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @error('work_end_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

</div>
