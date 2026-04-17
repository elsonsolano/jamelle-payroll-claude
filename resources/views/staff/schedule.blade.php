<x-staff-layout>
    <x-slot name="title">My Schedule</x-slot>

    <div class="space-y-5">

        {{-- Week views --}}
        @foreach($weeks as $weekIndex => $week)
            @php
                $weekDays  = $week->values();
                $firstDate = $weekDays->first()['date'];
                $lastDate  = $weekDays->last()['date'];
                $isCurrentWeek = $firstDate->lte(today()) && $lastDate->gte(today());
            @endphp

            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-700">
                        {{ $firstDate->format('M d') }} – {{ $lastDate->format('M d, Y') }}
                    </p>
                    @if($isCurrentWeek)
                        <span class="text-xs font-semibold bg-green-100 text-green-700 px-2.5 py-0.5 rounded-full">This Week</span>
                    @elseif($weekIndex === 1)
                        <span class="text-xs font-semibold bg-gray-100 text-gray-500 px-2.5 py-0.5 rounded-full">Next Week</span>
                    @endif
                </div>

                <div class="grid grid-cols-7 divide-x divide-gray-100">
                    @foreach($weekDays as $day)
                        @php
                            $isToday      = $day['date']->isToday();
                            $isPast       = $day['date']->isPast() && !$isToday;
                            $isOff        = $day['is_day_off'];
                            $noSched      = $day['source'] === 'none';
                            $dateStr      = $day['date']->toDateString();
                            $changeReq    = $changeRequests->get($dateStr);
                        @endphp
                        <div @class([
                            'flex flex-col items-center py-3 px-1 text-center',
                            'bg-green-50'  => $isToday,
                            'opacity-40'   => $isPast,
                        ])>
                            {{-- Day name --}}
                            <p @class([
                                'text-xs font-semibold mb-1',
                                'text-green-600' => $isToday,
                                'text-gray-400'  => !$isToday,
                            ])>{{ $day['date']->format('D') }}</p>

                            {{-- Date number --}}
                            <div @class([
                                'w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold mb-2',
                                'bg-green-500 text-white' => $isToday,
                                'text-gray-700'           => !$isToday && !$isPast,
                            ])>{{ $day['date']->format('j') }}</div>

                            {{-- Shift info --}}
                            @if($noSched)
                                <span class="text-gray-300 text-xs">—</span>
                            @elseif($isOff)
                                <span class="text-xs font-semibold text-orange-500">OFF</span>
                            @else
                                <p class="text-xs font-medium text-gray-700 leading-tight">
                                    {{ $day['start'] ? \Carbon\Carbon::parse($day['start'])->format('g:i') : '—' }}
                                </p>
                                <p class="text-xs text-gray-400 leading-tight">
                                    {{ $day['end'] ? \Carbon\Carbon::parse($day['end'])->format('g A') : '' }}
                                </p>
                            @endif

                            {{-- Change request dot --}}
                            @if($changeReq)
                                <span class="mt-1 w-1.5 h-1.5 rounded-full inline-block
                                    {{ $changeReq->status === 'pending' ? 'bg-amber-400' : 'bg-red-400' }}">
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Upcoming detail list (next 30 days) --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-700">Upcoming Schedule</p>
            </div>

            {{-- Alpine component for the schedule change bottom sheet --}}
            <script>
            function scheduleChangeModal() {
                return {
                    open: false,
                    date: '',
                    dateLabel: '',
                    currentStart: '',
                    currentEnd: '',
                    isCurrentDayOff: false,
                    noCurrentSchedule: false,
                    existingId: null,
                    existingStatus: null,
                    requestType: 'time',
                    newStart: '',
                    newEnd: '',
                    reason: '',
                    rejectionReason: '',
                    openFor(data) {
                        this.date = data.date;
                        this.dateLabel = data.dateLabel;
                        this.currentStart = data.currentStart;
                        this.currentEnd = data.currentEnd;
                        this.isCurrentDayOff = data.isCurrentDayOff;
                        this.noCurrentSchedule = data.noCurrentSchedule;
                        this.existingId = data.existingId;
                        this.existingStatus = data.existingStatus;
                        this.rejectionReason = data.rejectionReason || '';
                        if (data.existingId && data.existingStatus === 'pending') {
                            this.requestType = data.existingIsDayOff ? 'day_off' : 'time';
                            this.newStart = data.existingStart || '';
                            this.newEnd = data.existingEnd || '';
                            this.reason = data.existingReason || '';
                        } else {
                            this.requestType = 'time';
                            this.newStart = '';
                            this.newEnd = '';
                            this.reason = '';
                        }
                        this.open = true;
                    },
                    get formAction() {
                        if (this.existingId && this.existingStatus === 'pending') {
                            return '/staff/schedule-change-requests/' + this.existingId + '?_method=PUT';
                        }
                        return '/staff/schedule-change-requests';
                    },
                    get isEdit() {
                        return this.existingId && this.existingStatus === 'pending';
                    }
                };
            }
            </script>

            <div x-data="scheduleChangeModal()">

                <div class="divide-y divide-gray-100">
                    @php
                        $upcoming = collect($days)->filter(fn($d) => !$d['date']->isPast() || $d['date']->isToday())->take(30);
                    @endphp
                    @foreach($upcoming as $day)
                        @php
                            $isToday    = $day['date']->isToday();
                            $isOff      = $day['is_day_off'];
                            $noSched    = $day['source'] === 'none';
                            $dateStr    = $day['date']->toDateString();
                            $changeReq  = $changeRequests->get($dateStr);
                            $isPast     = $day['date']->isPast() && !$isToday;

                            $changeData = json_encode([
                                'date'             => $dateStr,
                                'dateLabel'        => $day['date']->format('D, M d Y'),
                                'currentStart'     => $day['start'],
                                'currentEnd'       => $day['end'],
                                'isCurrentDayOff'  => (bool) $isOff,
                                'noCurrentSchedule' => $noSched,
                                'existingId'       => $changeReq?->id,
                                'existingStatus'   => $changeReq?->status,
                                'existingIsDayOff' => (bool) $changeReq?->is_day_off,
                                'existingStart'    => $changeReq?->requested_work_start_time,
                                'existingEnd'      => $changeReq?->requested_work_end_time,
                                'existingReason'   => $changeReq?->reason,
                                'rejectionReason'  => $changeReq?->rejection_reason,
                            ]);
                        @endphp
                        <div @class([
                            'flex items-center px-4 py-3 gap-4',
                            'bg-green-50' => $isToday,
                        ])>
                            {{-- Date --}}
                            <div class="w-12 text-center shrink-0">
                                <p class="text-xs font-semibold {{ $isToday ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $day['date']->format('D') }}
                                </p>
                                <p class="text-lg font-bold {{ $isToday ? 'text-green-600' : 'text-gray-800' }}">
                                    {{ $day['date']->format('j') }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $day['date']->format('M') }}</p>
                            </div>

                            {{-- Shift --}}
                            <div class="flex-1 min-w-0">
                                @if($noSched)
                                    <p class="text-sm text-gray-400">No schedule set</p>
                                @elseif($isOff)
                                    <p class="text-sm font-semibold text-orange-500">Rest Day</p>
                                @else
                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ $day['start'] ? \Carbon\Carbon::parse($day['start'])->format('g:i A') : '—' }}
                                        –
                                        {{ $day['end'] ? \Carbon\Carbon::parse($day['end'])->format('g:i A') : '—' }}
                                    </p>
                                    @if($day['branch'])
                                        <p class="text-xs text-indigo-500 mt-0.5">{{ $day['branch'] }}</p>
                                    @endif
                                    @if($day['notes'])
                                        <p class="text-xs text-amber-500 mt-0.5">{{ $day['notes'] }}</p>
                                    @endif
                                @endif

                                {{-- Request status badge --}}
                                @if($changeReq)
                                    @if($changeReq->status === 'pending')
                                        <span class="inline-block mt-1 text-xs font-semibold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">
                                            Change Pending
                                        </span>
                                    @elseif($changeReq->status === 'rejected')
                                        <span class="inline-block mt-1 text-xs font-semibold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">
                                            Change Rejected
                                        </span>
                                    @endif
                                @endif
                            </div>

                            {{-- Right side: badges + request button --}}
                            <div class="flex items-center gap-2 shrink-0">
                                @if($isToday)
                                    <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded-full">Today</span>
                                @endif

                                @if(!$noSched && $day['source'] === 'daily' && !$changeReq)
                                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-400" data-tippy-content="Date-specific schedule"></div>
                                @endif

                                {{-- Request / Edit button — only for today and future --}}
                                @if(!$isPast)
                                    <button type="button"
                                            @click="openFor({{ $changeData }})"
                                            class="text-xs font-medium px-2.5 py-1.5 rounded-lg transition
                                                {{ $changeReq?->status === 'pending'
                                                    ? 'bg-amber-100 text-amber-700'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600' }}">
                                        {{ $changeReq?->status === 'pending' ? 'Edit' : ($changeReq?->status === 'rejected' ? 'Re-submit' : 'Request') }}
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Rejection reason inline (if rejected) --}}
                        @if($changeReq?->status === 'rejected' && $changeReq->rejection_reason)
                            <div class="px-4 pb-3 -mt-2">
                                <div class="bg-red-50 rounded-xl px-3 py-2 text-xs text-red-700">
                                    <span class="font-semibold">Not approved:</span> {{ $changeReq->rejection_reason }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- ── Schedule Change Bottom Sheet ── --}}
                <div x-show="open" x-cloak
                     class="fixed inset-0 z-50 flex flex-col justify-end"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">

                    <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

                    <div class="relative bg-white rounded-t-2xl p-5 shadow-xl max-h-[90vh] overflow-y-auto"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="translate-y-full"
                         x-transition:enter-end="translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="translate-y-0"
                         x-transition:leave-end="translate-y-full">

                        <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>

                        {{-- Header --}}
                        <p class="text-xs text-gray-400 mb-0.5" x-text="dateLabel"></p>
                        <h3 class="text-lg font-bold text-gray-900 mb-4"
                            x-text="isEdit ? 'Edit Schedule Request' : (existingStatus === 'rejected' ? 'Re-submit Schedule Request' : 'Request Schedule Change')">
                        </h3>

                        {{-- Current schedule display --}}
                        <div class="bg-gray-50 rounded-xl p-3 mb-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Current Schedule</p>
                            <template x-if="noCurrentSchedule">
                                <p class="text-sm text-gray-400">No schedule assigned</p>
                            </template>
                            <template x-if="!noCurrentSchedule && isCurrentDayOff">
                                <p class="text-sm font-semibold text-orange-500">Rest Day</p>
                            </template>
                            <template x-if="!noCurrentSchedule && !isCurrentDayOff">
                                <p class="text-sm font-semibold text-gray-700"
                                   x-text="currentStart && currentEnd
                                       ? new Date('1970-01-01T' + currentStart).toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit', hour12:true})
                                         + ' – '
                                         + new Date('1970-01-01T' + currentEnd).toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit', hour12:true})
                                       : 'No schedule'">
                                </p>
                            </template>
                        </div>

                        {{-- Show rejection reason if re-submitting --}}
                        <template x-if="existingStatus === 'rejected' && rejectionReason">
                            <div class="bg-red-50 rounded-xl px-3 py-2 mb-4">
                                <p class="text-xs text-red-600 font-semibold mb-0.5">Previous rejection reason</p>
                                <p class="text-sm text-red-700" x-text="rejectionReason"></p>
                            </div>
                        </template>

                        <form :action="isEdit ? '/staff/schedule-change-requests/' + existingId : '/staff/schedule-change-requests'" method="POST">
                            @csrf
                            <template x-if="isEdit">
                                <input type="hidden" name="_method" value="PUT">
                            </template>
                            <input type="hidden" name="date" :value="date">
                            <input type="hidden" name="is_day_off" :value="requestType === 'day_off' ? '1' : '0'">

                            {{-- Request type --}}
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">I want to request</p>
                                <div class="flex gap-2">
                                    <button type="button" @click="requestType = 'time'"
                                            :class="requestType === 'time' ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-600'"
                                            class="flex-1 text-sm font-semibold py-2.5 rounded-xl transition">
                                        Different Time
                                    </button>
                                    <button type="button" @click="requestType = 'day_off'"
                                            :class="requestType === 'day_off' ? 'bg-orange-500 text-white' : 'border border-gray-300 text-gray-600'"
                                            class="flex-1 text-sm font-semibold py-2.5 rounded-xl transition">
                                        Day Off
                                    </button>
                                </div>
                            </div>

                            {{-- Time inputs --}}
                            <div x-show="requestType === 'time'" x-cloak class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">New Start Time</label>
                                    <input type="time" name="requested_work_start_time" x-model="newStart" required
                                           class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm font-semibold text-gray-800 focus:ring-2 focus:ring-indigo-500">
                                    <p x-show="newStart" x-cloak class="text-xs font-semibold text-indigo-600 mt-1 text-center"
                                       x-text="newStart ? new Date('1970-01-01T' + newStart).toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit', hour12:true}) : ''"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">New End Time</label>
                                    <input type="time" name="requested_work_end_time" x-model="newEnd" required
                                           class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm font-semibold text-gray-800 focus:ring-2 focus:ring-indigo-500">
                                    <p x-show="newEnd" x-cloak class="text-xs font-semibold text-indigo-600 mt-1 text-center"
                                       x-text="newEnd ? new Date('1970-01-01T' + newEnd).toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit', hour12:true}) : ''"></p>
                                </div>
                            </div>

                            <div x-show="requestType === 'day_off'" x-cloak class="mb-4">
                                <div class="bg-orange-50 rounded-xl p-3 text-sm text-orange-700 font-medium">
                                    Requesting this date as a day off. Your approver will be notified.
                                </div>
                            </div>

                            {{-- Reason --}}
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Reason <span class="text-red-500">*</span>
                                </label>
                                <textarea name="reason" x-model="reason" rows="2" maxlength="500"
                                          placeholder="Why do you need this schedule change?"
                                          required
                                          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                            </div>

                            @error('requested_work_start_time')
                                <p class="text-xs text-red-600 mb-3">{{ $message }}</p>
                            @enderror

                            {{-- Buttons --}}
                            <div class="flex gap-3">
                                <button type="button" @click="open = false"
                                        class="flex-1 border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl text-sm">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl text-sm transition"
                                        x-text="isEdit ? 'Update Request' : 'Submit Request'">
                                </button>
                            </div>

                        </form>

                        {{-- Cancel pending request (outside submit form to avoid nested forms) --}}
                        <template x-if="isEdit">
                            <form :action="'/staff/schedule-change-requests/' + existingId" method="POST" class="mt-3">
                                @csrf
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit"
                                        onclick="return confirm('Cancel this schedule change request?')"
                                        class="w-full text-center text-sm text-red-500 font-medium py-2">
                                    Cancel Request
                                </button>
                            </form>
                        </template>
                    </div>
                </div>

            </div> {{-- end Alpine wrapper --}}
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-4 px-1 text-xs text-gray-400">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 inline-block"></span>
                Date-specific schedule
            </span>
            <span class="flex items-center gap-1.5">
                <span class="font-semibold text-orange-400">OFF</span>
                Rest day
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                Change pending
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                Change rejected
            </span>
        </div>

    </div>
</x-staff-layout>
