<x-app-layout>
    <x-slot name="title">{{ $attendanceScore->employee->full_name }} Attendance Score</x-slot>

    <x-slot name="actions">
        <a href="{{ route('payroll.cutoffs.attendance-scores.index', $cutoff) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            Back to Scores
        </a>
    </x-slot>

    <div class="space-y-6">
        @php($activeBadges = $attendanceScore->employeeAttendanceBadges->filter(fn ($award) => $award->badge?->active))

        <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Total Points</p>
                <p class="text-2xl font-bold text-amber-600">{{ number_format($attendanceScore->total_points) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">No Late</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($attendanceScore->on_time_days) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Same-Day</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($attendanceScore->same_day_complete_days) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">No Absent</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($attendanceScore->no_absent_days ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Late Days</p>
                <p class="text-2xl font-bold text-red-500">{{ number_format($attendanceScore->late_days ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 mb-1">Late Mins</p>
                <p class="text-2xl font-bold text-red-500">{{ number_format($attendanceScore->late_minutes) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex flex-wrap items-center gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Employee</p>
                    <p class="font-semibold text-gray-800">{{ $attendanceScore->employee->full_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Branch</p>
                    <p class="font-semibold text-gray-800">{{ $attendanceScore->employee->branch?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Cutoff</p>
                    <p class="font-semibold text-gray-800">{{ $cutoff->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Finalized</p>
                    <p class="font-semibold text-gray-800">{{ $attendanceScore->finalized_at?->format('M d, Y h:i A') ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between gap-3 mb-4">
                <h2 class="font-semibold text-gray-800">Badges Awarded</h2>
                <span class="text-xs font-medium text-amber-700 bg-amber-100 rounded-full px-2 py-1">
                    {{ $activeBadges->count() }} earned
                </span>
            </div>

            @if($activeBadges->isEmpty())
                <p class="text-sm text-gray-400">No badges were awarded for this cutoff.</p>
            @else
                <div class="grid md:grid-cols-2 gap-3">
                    @foreach($activeBadges as $award)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ str($award->badge->name)->substr(0, 2)->upper() }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $award->badge->name }}</p>
                                    <p class="text-xs text-gray-600 mt-0.5">{{ $award->badge->description }}</p>
                                    @if($award->metadata)
                                        <p class="text-xs text-amber-700 mt-1">
                                            @foreach($award->metadata as $key => $value)
                                                {{ str($key)->replace('_', ' ')->title() }}:
                                                @if(is_array($value))
                                                    {{ json_encode($value) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                                @if(! $loop->last), @endif
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Line-Item Breakdown</h2>
            </div>

            @if($items->isEmpty())
                <div class="px-5 py-12 text-center">
                    <p class="text-gray-400 text-sm">No score items for this employee.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                                <th class="px-5 py-3 font-semibold text-gray-600">Date</th>
                                <th class="px-5 py-3 font-semibold text-gray-600">Rule</th>
                                <th class="px-5 py-3 font-semibold text-gray-600">Description</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($items as $item)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 text-gray-700">
                                        {{ $item->work_date?->format('M d, Y') ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                            {{ str($item->rule_key)->replace('_', ' ')->title() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-800">{{ $item->description }}</p>
                                        @if($item->metadata)
                                            <p class="text-xs text-gray-400 mt-1">
                                                @foreach($item->metadata as $key => $value)
                                                    {{ str($key)->replace('_', ' ')->title() }}:
                                                    @if(is_array($value))
                                                        {{ json_encode($value) }}
                                                    @else
                                                        {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}
                                                    @endif
                                                    @if(! $loop->last), @endif
                                                @endforeach
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold {{ $item->points < 0 ? 'text-red-600' : 'text-amber-600' }}">
                                        {{ $item->points > 0 ? '+' : '' }}{{ number_format($item->points) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
