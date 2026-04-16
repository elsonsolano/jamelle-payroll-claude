<x-app-layout>
    <x-slot name="title">DTR — {{ $dtr->employee->full_name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('dtr.index', ['employee_id' => $dtr->employee_id]) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to DTR
        </a>
        <a href="{{ route('dtr.edit', $dtr) }}"
           class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            Edit
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
                <button id="dtr-status-badge"
                        data-dtr-id="{{ $dtr->id }}"
                        onclick="toggleDtrStatus(this)"
                        title="Click to toggle"
                        class="text-sm font-medium px-3 py-1 rounded-full cursor-pointer transition
                            {{ $dtr->status === 'Approved'
                                ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}">
                    {{ $dtr->status }}
                </button>
            </div>

            @if($dtr->status_changed_by && $dtr->statusChangedBy)
                <p id="dtr-status-meta" class="text-xs text-gray-400 mt-1 text-right">
                    {{ $dtr->status }} by {{ $dtr->statusChangedBy->name }}
                    · {{ $dtr->status_changed_at->format('M d, Y h:i A') }}
                </p>
            @else
                <p id="dtr-status-meta" class="text-xs text-gray-400 mt-1 text-right"></p>
            @endif

            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-sm font-semibold text-gray-800">{{ $dtr->employee->full_name }}</p>
                <p class="text-xs text-gray-400">{{ $dtr->employee->position ?? 'No position' }} · {{ $dtr->employee->branch->name }}</p>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6" x-data="{ open: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Schedule</h3>
                <button type="button" @click="open = true"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    Edit Schedule
                </button>
            </div>

            {{-- Edit Schedule Modal --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape.window="open = false">
                <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-semibold text-gray-800">Edit Schedule</h4>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-xs text-gray-500">
                        Daily override for <strong>{{ $dtr->employee->full_name }}</strong>
                        on <strong>{{ $dtr->date->format('M d, Y') }}</strong>.
                        This creates or updates a Daily Schedule for this specific date.
                    </p>

                    @php
                        $scheduleAction = $dailySchedule
                            ? route('employees.daily-schedules.update', [$dtr->employee, $dailySchedule])
                            : route('employees.daily-schedules.store', $dtr->employee);
                    @endphp

                    <form action="{{ $scheduleAction }}" method="POST" x-data="{ isDayOff: {{ $dailySchedule?->is_day_off ? 'true' : 'false' }} }">
                        @csrf
                        @if($dailySchedule)
                            @method('PUT')
                        @endif
                        <input type="hidden" name="date" value="{{ $dtr->date->format('Y-m-d') }}">
                        <input type="hidden" name="redirect_to" value="{{ route('dtr.show', $dtr) }}">

                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="is_day_off" value="1" x-model="isDayOff"
                                       {{ $dailySchedule?->is_day_off ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-700">Mark as Day Off</span>
                            </label>

                            <div x-show="!isDayOff" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Work Start</label>
                                    <input type="time" name="work_start_time"
                                           value="{{ $dailySchedule?->work_start_time ? \Carbon\Carbon::parse($dailySchedule->work_start_time)->format('H:i') : '' }}"
                                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Work End</label>
                                    <input type="time" name="work_end_time"
                                           value="{{ $dailySchedule?->work_end_time ? \Carbon\Carbon::parse($dailySchedule->work_end_time)->format('H:i') : '' }}"
                                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="submit"
                                        class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">
                                    Save
                                </button>
                                <button type="button" @click="open = false"
                                        class="flex-1 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @if($dailySchedule)
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Source</span>
                        <span class="font-medium text-blue-600">Daily Override</span>
                    </div>
                    @if($dailySchedule->is_day_off)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span class="font-medium text-amber-600">Day Off</span>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <span class="text-gray-500">Work Hours</span>
                            <span class="font-medium text-gray-800">
                                {{ \Carbon\Carbon::parse($dailySchedule->work_start_time)->format('h:i A') }}
                                –
                                {{ \Carbon\Carbon::parse($dailySchedule->work_end_time)->format('h:i A') }}
                            </span>
                        </div>
                    @endif
                    @if($dailySchedule->notes)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Notes</span>
                            <span class="font-medium text-gray-800">{{ $dailySchedule->notes }}</span>
                        </div>
                    @endif
                </div>
            @elseif($weeklySchedule)
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Source</span>
                        <span class="font-medium text-gray-600">Weekly Default</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Work Hours</span>
                        <span class="font-medium text-gray-800">
                            {{ \Carbon\Carbon::parse($weeklySchedule->work_start_time)->format('h:i A') }}
                            –
                            {{ \Carbon\Carbon::parse($weeklySchedule->work_end_time)->format('h:i A') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Rest Days</span>
                        <span class="font-medium text-gray-800">{{ implode(', ', $weeklySchedule->rest_days ?? ['Sunday']) }}</span>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-400">No schedule set for this date — late and undertime are not tracked.</p>
            @endif
        </div>

        {{-- Punch Times --}}
        @php
            $isOvernight = $dtr->time_in && $dtr->time_out &&
                \Carbon\Carbon::createFromTimeString($dtr->time_out)->lte(
                    \Carbon\Carbon::createFromTimeString($dtr->time_in)
                );
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Punch Times</h3>
            <div class="grid grid-cols-2 gap-4">
                @foreach([
                    ['label' => 'Time In',     'value' => $dtr->time_in,  'overnight' => false],
                    ['label' => 'Start Break', 'value' => $dtr->am_out,   'overnight' => false],
                    ['label' => 'End Break',   'value' => $dtr->pm_in,    'overnight' => false],
                    ['label' => 'Time Out',    'value' => $dtr->time_out, 'overnight' => $isOvernight],
                ] as $punch)
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">{{ $punch['label'] }}</p>
                        <p class="text-lg font-bold {{ $punch['value'] ? 'text-gray-900' : 'text-gray-300' }}">
                            {{ $punch['value'] ? \Carbon\Carbon::parse($punch['value'])->format('h:i A') : '—' }}
                        </p>
                        @if($punch['overnight'])
                            <p class="text-xs text-orange-500 font-semibold mt-0.5">+1 day</p>
                        @endif
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

        {{-- Notes --}}
        @if($dtr->notes)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-2">Note</h3>
                <p class="text-sm text-gray-700">{{ $dtr->notes }}</p>
            </div>
        @endif

        {{-- Overtime Status --}}
        @if($dtr->ot_status !== 'none')
            <div @class([
                'rounded-xl border p-6',
                'bg-amber-50 border-amber-200' => $dtr->ot_status === 'pending',
                'bg-green-50 border-green-200' => $dtr->ot_status === 'approved',
                'bg-red-50 border-red-200'     => $dtr->ot_status === 'rejected',
            ])>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Overtime Request</h3>
                    <span @class([
                        'text-xs font-semibold px-2.5 py-1 rounded-full',
                        'bg-amber-200 text-amber-800' => $dtr->ot_status === 'pending',
                        'bg-green-200 text-green-800' => $dtr->ot_status === 'approved',
                        'bg-red-200 text-red-800'     => $dtr->ot_status === 'rejected',
                    ])>
                        {{ ucfirst($dtr->ot_status) }}
                    </span>
                </div>

                <dl class="text-sm space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">OT Hours Requested</dt>
                        <dd class="font-semibold text-gray-800">{{ number_format($dtr->overtime_hours, 2) }}h</dd>
                    </div>
                    @if($dtr->ot_status !== 'pending' && $dtr->approvedBy)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ $dtr->ot_status === 'approved' ? 'Approved by' : 'Rejected by' }}</dt>
                            <dd class="text-gray-800">{{ $dtr->approvedBy->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">{{ $dtr->ot_status === 'approved' ? 'Approved at' : 'Rejected at' }}</dt>
                            <dd class="text-gray-800">{{ $dtr->ot_approved_at->format('M d, Y h:i A') }}</dd>
                        </div>
                    @endif
                    @if($dtr->ot_status === 'rejected' && $dtr->ot_rejection_reason)
                        <div class="pt-2 border-t border-red-200">
                            <dt class="text-gray-500 mb-1">Rejection Reason</dt>
                            <dd class="text-gray-800">{{ $dtr->ot_rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>

                @if($dtr->ot_status === 'pending')
                    <div x-data="{ showReject: false }" class="mt-4 pt-4 border-t border-amber-200 flex gap-2">
                        <form action="{{ route('dtr.approve-ot', $dtr) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition"
                                    onclick="return confirm('Approve OT for {{ addslashes($dtr->employee->full_name) }}?')">
                                Approve OT
                            </button>
                        </form>
                        <button type="button"
                                @click="showReject = !showReject"
                                class="px-4 py-2 text-sm font-medium text-red-700 border border-red-300 hover:bg-red-50 rounded-lg transition">
                            Reject OT
                        </button>

                        <div x-show="showReject" x-cloak class="w-full mt-3">
                            <form action="{{ route('dtr.reject-ot', $dtr) }}" method="POST" class="space-y-2">
                                @csrf
                                <textarea name="reason" rows="2"
                                          class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-red-400 focus:border-red-400"
                                          placeholder="Reason (optional)…"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                                        Confirm Reject
                                    </button>
                                    <button type="button" @click="showReject = false"
                                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        @endif

    </div>

@push('scripts')
<script>
async function toggleDtrStatus(btn) {
    const dtrId = btn.dataset.dtrId;
    btn.disabled = true;
    btn.style.opacity = '0.5';
    try {
        const res = await fetch(`/dtr/${dtrId}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        const isApproved = data.status === 'Approved';
        btn.textContent = data.status;
        btn.className = `text-sm font-medium px-3 py-1 rounded-full cursor-pointer transition ${
            isApproved
                ? 'bg-green-100 text-green-700 hover:bg-green-200'
                : 'bg-rose-100 text-rose-700 hover:bg-rose-200'
        }`;
        document.getElementById('dtr-status-meta').textContent =
            `${data.status} by ${data.by} · ${data.at}`;
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
    }
}
</script>
@endpush

</x-app-layout>
