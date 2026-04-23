<x-app-layout>
    <x-slot name="title">Attendance Scores</x-slot>

    <x-slot name="actions">
        <a href="{{ route('payroll.cutoffs.show', $cutoff) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            Back to Cutoff
        </a>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex flex-wrap items-center gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Cutoff</p>
                    <p class="font-semibold text-gray-800">{{ $cutoff->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Branch</p>
                    <p class="font-semibold text-gray-800">{{ $cutoff->branch?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Period</p>
                    <p class="font-semibold text-gray-800">
                        {{ $cutoff->start_date->format('M d') }} - {{ $cutoff->end_date->format('M d, Y') }}
                    </p>
                </div>
                <div class="ml-auto">
                    <span class="text-sm font-medium px-3 py-1 rounded-full bg-amber-100 text-amber-700">
                        Official Scores
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Score Summary</h2>
            </div>

            @if($scores->isEmpty())
                <div class="px-5 py-12 text-center">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.956a1 1 0 00.95.69h4.16c.969 0 1.371 1.24.588 1.81l-3.366 2.445a1 1 0 00-.364 1.118l1.286 3.956c.3.921-.755 1.688-1.539 1.118l-3.366-2.445a1 1 0 00-1.176 0L8.044 18.02c-.783.57-1.838-.197-1.539-1.118l1.286-3.956a1 1 0 00-.364-1.118L4.061 9.383c-.783-.57-.38-1.81.588-1.81h4.16a1 1 0 00.95-.69l1.29-3.956z"/>
                    </svg>
                    <p class="text-gray-400 text-sm">No attendance scores have been calculated for this cutoff.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-left">
                                <th class="px-5 py-3 font-semibold text-gray-600">Employee</th>
                                <th class="px-5 py-3 font-semibold text-gray-600">Branch</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Points</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">No Late</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Same-Day</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">No Absent</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Late Days</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 text-right">Late Mins</th>
                                <th class="px-5 py-3 font-semibold text-gray-600">Badges</th>
                                <th class="px-5 py-3 font-semibold text-gray-600"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($scores as $score)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-gray-900">{{ $score->employee->full_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $score->employee->employee_code }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-gray-600">{{ $score->employee->branch?->name ?? '-' }}</td>
                                    <td class="px-5 py-3 text-right font-bold text-amber-600">{{ number_format($score->total_points) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ number_format($score->on_time_days) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ number_format($score->same_day_complete_days) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ number_format($score->no_absent_days ?? 0) }}</td>
                                    <td class="px-5 py-3 text-right text-red-600">{{ number_format($score->late_days ?? 0) }}</td>
                                    <td class="px-5 py-3 text-right text-gray-700">{{ number_format($score->late_minutes) }}</td>
                                    <td class="px-5 py-3">
                                        @php($activeBadges = $score->employeeAttendanceBadges->filter(fn ($award) => $award->badge?->active))
                                        @if($activeBadges->isNotEmpty())
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($activeBadges as $award)
                                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                        {{ $award->badge->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('payroll.cutoffs.attendance-scores.show', [$cutoff, $score]) }}"
                                           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Breakdown</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($scores->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">{{ $scores->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
