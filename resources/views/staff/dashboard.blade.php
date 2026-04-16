<x-staff-layout title="Dashboard">

@php
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $today      = today();
    $yesterday  = today()->subDay();
    $todayLabel = $today->format('D, M d Y');
    $yLabel     = $yesterday->format('D, M d Y');

    $fmt12 = fn($t) => $t ? date('g:i A', strtotime($t)) : null;

    // Next loggable event — Yesterday (overnight)
    $yesterdayNextEvent = null;
    if ($yesterdayDtr) {
        if      (!$yesterdayDtr->am_out) $yesterdayNextEvent = 'am_out';
        elseif  (!$yesterdayDtr->pm_in)  $yesterdayNextEvent = 'pm_in';
        else                             $yesterdayNextEvent = 'time_out';
    }

    // Shift state
    $shiftState = 'not_started';
    if ($todayDtr) {
        if      ($todayDtr->time_out) $shiftState = 'complete';
        elseif  ($todayDtr->pm_in)    $shiftState = 'back_from_break';
        elseif  ($todayDtr->am_out)   $shiftState = 'on_break';
        elseif  ($todayDtr->time_in)  $shiftState = 'working';
    }

    // Late indicator — only when not yet timed in and schedule exists
    $isLate      = false;
    $lateMinutes = 0;
    if ($todaySchedule['source'] !== 'none'
        && !$todaySchedule['is_day_off']
        && $todaySchedule['start']
        && !$todayDtr?->time_in)
    {
        $scheduledStart = \Carbon\Carbon::parse($todaySchedule['start']);
        if (now()->gt($scheduledStart)) {
            $lateMinutes = (int) now()->diffInMinutes($scheduledStart);
            $isLate      = $lateMinutes > 0;
        }
    }
@endphp

<script>
function liveClock() {
    return {
        now: new Date(),
        init() { setInterval(() => { this.now = new Date(); }, 1000); },
        get hourMin() {
            const h = this.now.getHours() % 12 || 12;
            const m = String(this.now.getMinutes()).padStart(2, '0');
            return h + ':' + m;
        },
        get ampm() {
            return this.now.getHours() >= 12 ? 'PM' : 'AM';
        }
    };
}
</script>

{{-- White card floating on the green main background --}}
<div class="bg-white rounded-t-3xl pt-7 px-5 pb-28">

    {{-- Clock --}}
    <div x-data="liveClock()" class="text-center mb-1">
        <div class="flex items-end justify-center gap-1.5">
            <span class="text-6xl font-bold text-gray-900 tabular-nums leading-none" x-text="hourMin"></span>
            <span class="text-2xl font-semibold text-gray-400 mb-1.5" x-text="ampm"></span>
        </div>
        <p class="text-sm text-gray-400 mt-2">{{ now()->format('l, F j, Y') }}</p>
    </div>

    {{-- Schedule pill + late badge --}}
    <div class="flex items-center justify-center gap-2 mt-3 mb-7">
        @if($todaySchedule['source'] === 'none')
            <span class="text-xs text-gray-400">No schedule set today</span>
        @elseif($todaySchedule['is_day_off'])
            <span class="inline-flex items-center gap-1.5 text-xs text-orange-600 bg-orange-50 px-3 py-1.5 rounded-full font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                Rest Day
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 text-xs text-green-700 bg-green-50 px-3 py-1.5 rounded-full font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ \Carbon\Carbon::parse($todaySchedule['start'])->format('g:i A') }}
                –
                {{ \Carbon\Carbon::parse($todaySchedule['end'])->format('g:i A') }}
            </span>
            @if($isLate && $lateMinutes > 0)
                <span class="inline-flex items-center text-xs bg-red-500 text-white px-2.5 py-1 rounded-full font-bold">
                    {{ $lateMinutes }}{{ $lateMinutes === 1 ? 'min' : 'mins' }} late
                </span>
            @endif
        @endif
    </div>

    {{-- Alpine wrapper: action button + bottom sheet --}}
    <div x-data="{
            open: false,
            date: '{{ $today->format('Y-m-d') }}',
            dateLabel: '{{ $todayLabel }}',
            event: '',
            label: '',
            time: '',
            hasOt: false,
            otHours: '',
            otError: '',
            note: '',
            todayNote: '{{ addslashes($todayDtr?->notes ?? '') }}',
            yesterdayNote: '{{ addslashes($yesterdayDtr?->notes ?? '') }}',
            openSheet(evt, lbl, dateStr, dateL, noteVal) {
                const n = new Date();
                this.time      = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
                this.event     = evt;
                this.label     = lbl;
                this.date      = dateStr;
                this.dateLabel = dateL;
                this.hasOt     = false;
                this.otHours   = '';
                this.otError   = '';
                this.note      = noteVal;
                this.open      = true;
            }
        }">

        {{-- Open Shift (yesterday overnight) --}}
        @if($yesterdayDtr)
        @php
            $yNextLabel = match($yesterdayNextEvent) {
                'am_out'   => 'Start Break',
                'pm_in'    => 'End Break',
                'time_out' => 'Time Out',
                default    => null,
            };
            $yNextStyle = match($yesterdayNextEvent) {
                'am_out'   => 'bg-amber-500 hover:bg-amber-600',
                'pm_in'    => 'bg-orange-500 hover:bg-orange-600',
                'time_out' => 'bg-blue-600 hover:bg-blue-700',
                default    => 'bg-gray-400',
            };
        @endphp
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wide">Open Shift — Yesterday</p>
                    <p class="text-xs text-amber-700 mt-0.5">Timed in at <span class="font-bold">{{ $fmt12($yesterdayDtr->time_in) }}</span></p>
                </div>
                <a href="{{ route('staff.dtr.edit', $yesterdayDtr) }}" class="text-xs text-indigo-600 font-medium shrink-0 ml-3">Edit</a>
            </div>
            @if($yesterdayNextEvent && $yNextLabel)
            <button type="button"
                    @click="openSheet('{{ $yesterdayNextEvent }}', '{{ $yNextLabel }}', '{{ $yesterday->format('Y-m-d') }}', '{{ $yLabel }}', yesterdayNote)"
                    class="w-full {{ $yNextStyle }} text-white font-semibold py-2.5 rounded-xl text-sm transition">
                {{ $yNextLabel }}
            </button>
            @endif
        </div>
        @endif

        {{-- Big Circle Button --}}
        <div class="flex flex-col items-center mb-7">

            @if(($todaySchedule['is_day_off'] ?? false) && $shiftState === 'not_started')
            {{-- Rest Day --}}
            <div class="w-44 h-44 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 flex flex-col items-center justify-center gap-2 shadow-inner">
                <svg class="w-12 h-12 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span class="text-orange-400 font-bold text-xs tracking-widest">REST DAY</span>
            </div>
            <p class="text-xs text-gray-400 mt-3">Enjoy your day off!</p>

            @elseif($shiftState === 'not_started')
            {{-- TIME IN --}}
            <button type="button"
                    @click="openSheet('time_in', 'Time In', '{{ $today->format('Y-m-d') }}', '{{ $todayLabel }}', todayNote)"
                    class="w-44 h-44 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 shadow-2xl shadow-green-200 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform cursor-pointer">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-white font-bold text-sm tracking-widest">TIME IN</span>
            </button>
            <p class="text-xs text-gray-400 mt-3">Tap to start your shift</p>

            @elseif($shiftState === 'working')
            {{-- START BREAK (primary) --}}
            <button type="button"
                    @click="openSheet('am_out', 'Start Break', '{{ $today->format('Y-m-d') }}', '{{ $todayLabel }}', todayNote)"
                    class="w-44 h-44 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 shadow-2xl shadow-amber-200 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform cursor-pointer">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-white font-bold text-sm tracking-widest">START BREAK</span>
            </button>
            {{-- TIME OUT (secondary - skip break) --}}
            <button type="button"
                    @click="openSheet('time_out', 'Time Out', '{{ $today->format('Y-m-d') }}', '{{ $todayLabel }}', todayNote)"
                    class="mt-3 text-xs text-gray-400 border border-gray-200 bg-white px-5 py-2 rounded-full shadow-sm hover:border-gray-300 hover:text-gray-600 transition">
                Skip break → Time Out
            </button>

            @elseif($shiftState === 'on_break')
            {{-- END BREAK --}}
            <button type="button"
                    @click="openSheet('pm_in', 'End Break', '{{ $today->format('Y-m-d') }}', '{{ $todayLabel }}', todayNote)"
                    class="w-44 h-44 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 shadow-2xl shadow-orange-200 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform cursor-pointer">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-white font-bold text-sm tracking-widest">END BREAK</span>
            </button>
            <p class="text-xs text-gray-400 mt-3">Break since {{ $fmt12($todayDtr->am_out) }}</p>

            @elseif($shiftState === 'back_from_break')
            {{-- TIME OUT --}}
            <button type="button"
                    @click="openSheet('time_out', 'Time Out', '{{ $today->format('Y-m-d') }}', '{{ $todayLabel }}', todayNote)"
                    class="w-44 h-44 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 shadow-2xl shadow-blue-200 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform cursor-pointer">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span class="text-white font-bold text-sm tracking-widest">TIME OUT</span>
            </button>

            @elseif($shiftState === 'complete')
            {{-- SHIFT DONE --}}
            <div class="w-44 h-44 rounded-full bg-gradient-to-br from-green-100 to-emerald-200 flex flex-col items-center justify-center gap-2 shadow-inner">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-green-600 font-bold text-xs tracking-widest">SHIFT DONE</span>
            </div>
            <p class="text-xs text-gray-400 mt-3">
                {{ $fmt12($todayDtr->time_in) }} – {{ $fmt12($todayDtr->time_out) }}
                @if(strtotime($todayDtr->time_out) <= strtotime($todayDtr->time_in))
                    <span class="text-orange-500">(+1 day)</span>
                @endif
            </p>
            @if($todayDtr->ot_status !== 'none')
            <span class="mt-1.5 text-xs px-2.5 py-0.5 rounded-full font-medium
                {{ $todayDtr->ot_status === 'approved' ? 'bg-green-100 text-green-700' : '' }}
                {{ $todayDtr->ot_status === 'pending'  ? 'bg-amber-100 text-amber-700'  : '' }}
                {{ $todayDtr->ot_status === 'rejected' ? 'bg-red-100 text-red-700'      : '' }}">
                OT {{ ucfirst($todayDtr->ot_status) }}
            </span>
            @endif
            <a href="{{ route('staff.dtr.edit', $todayDtr) }}" class="mt-2 text-xs text-gray-300 underline underline-offset-2">Edit record</a>
            @endif

        </div>{{-- end circle button area --}}

        {{-- Summary Row --}}
        <div class="flex items-center justify-around py-4 border-t border-b border-gray-100">
            <div class="text-center flex-1">
                <p class="text-base font-bold text-gray-800 tabular-nums">{{ $fmt12($todayDtr?->time_in) ?? '—' }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Time In</p>
            </div>
            <div class="w-px h-8 bg-gray-200 shrink-0"></div>
            <div class="text-center flex-1">
                <p class="text-base font-bold text-gray-800 tabular-nums">{{ $fmt12($todayDtr?->time_out) ?? '—' }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Time Out</p>
            </div>
            <div class="w-px h-8 bg-gray-200 shrink-0"></div>
            <div class="text-center flex-1">
                <p class="text-base font-bold text-gray-800 tabular-nums">
                    {{ $todayDtr?->total_hours ? $todayDtr->total_hours . 'h' : '—' }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">Hours</p>
            </div>
        </div>

        {{-- OT Approvals (approvers only) --}}
        @if($pendingApprovalCount > 0)
        <div class="mt-4 bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-amber-700">OT Approvals Pending</p>
                <p class="text-xl font-bold text-amber-600">{{ $pendingApprovalCount }}</p>
            </div>
            <a href="{{ route('staff.ot-approvals.index') }}"
               class="text-xs bg-amber-600 text-white px-3 py-1.5 rounded-lg font-medium">
                Review
            </a>
        </div>
        @endif

        {{-- Bottom Sheet --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex flex-col justify-end"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

            <div class="relative bg-white rounded-t-2xl p-5 shadow-xl"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full">

                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>

                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2">
                    <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <p class="text-xs text-red-700 font-medium">Make sure the time you enter matches what is recorded in your timemark.</p>
                </div>

                <p class="text-xs text-gray-400 mb-0.5" x-text="dateLabel"></p>
                <h3 class="text-lg font-bold text-gray-900 mb-4" x-text="label"></h3>

                <form method="POST" action="{{ route('staff.dtr.log-event') }}"
                      @submit.prevent="if (hasOt && !otHours) { otError = 'Please enter your overtime hours.' } else { otError = ''; $el.submit() }">
                    @csrf
                    <input type="hidden" name="date"  :value="date">
                    <input type="hidden" name="event" :value="event">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Time <span class="text-gray-400 font-normal">(from your timemark)</span>
                        </label>
                        <input type="time" name="time" x-model="time" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 text-lg font-semibold text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p x-show="time" x-cloak class="text-sm font-semibold text-indigo-600 mt-1.5 text-center"
                           x-text="time ? new Date('1970-01-01T' + time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : ''"></p>
                    </div>

                    <div x-show="event === 'time_out'" x-cloak class="mb-5">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="has_ot" value="1" x-model="hasOt"
                                       class="w-5 h-5 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                                <span class="text-sm font-medium text-amber-800">I have overtime</span>
                            </label>
                            <div x-show="hasOt" x-cloak class="mt-3">
                                <label class="block text-sm font-medium text-amber-800 mb-1">Overtime Hours</label>
                                <input type="number" name="ot_hours" x-model="otHours"
                                       min="0.25" max="24" step="0.25"
                                       placeholder="e.g. 2, 1.5, or 0.75"
                                       @input="otError = ''"
                                       class="w-full border border-amber-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-amber-500 bg-white">
                                <p x-show="otError" x-cloak class="text-xs text-red-600 font-medium mt-1" x-text="otError"></p>
                                <p class="text-xs text-amber-600 mt-1">Your overtime will be sent for approval.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Note <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea name="notes" x-model="note" rows="2" maxlength="500"
                                  placeholder="Late, early out, or anything else…"
                                  class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="open = false"
                                class="flex-1 border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl text-sm">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl text-sm transition">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>{{-- end Alpine wrapper --}}

    {{-- Recent Logs --}}
    @if($recentDtrs->isNotEmpty())
    <div class="mt-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Recent Logs</h3>
            <a href="{{ route('staff.dtr.index') }}" class="text-xs text-green-600 font-medium">View All</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($recentDtrs as $dtr)
            <div class="flex items-center justify-between py-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $dtr->date->format('D, M d') }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $dtr->time_in ? date('g:i A', strtotime($dtr->time_in)) : '--' }}
                        –
                        {{ $dtr->time_out ? date('g:i A', strtotime($dtr->time_out)) : '--' }}
                        @if($dtr->time_in && $dtr->time_out && strtotime($dtr->time_out) <= strtotime($dtr->time_in))
                            <span class="text-orange-500">(+1 day)</span>
                        @endif
                        · {{ $dtr->total_hours }}h
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @if($dtr->ot_status !== 'none')
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $dtr->ot_status === 'approved' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $dtr->ot_status === 'pending'  ? 'bg-amber-100 text-amber-700'  : '' }}
                            {{ $dtr->ot_status === 'rejected' ? 'bg-red-100 text-red-700'      : '' }}">
                            OT {{ ucfirst($dtr->ot_status) }}
                        </span>
                    @endif
                    <a href="{{ route('staff.dtr.edit', $dtr) }}" class="text-gray-300 hover:text-green-500 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Quote --}}
    <div class="mt-6 text-center px-2">
        <p class="text-xs text-gray-300 italic leading-relaxed">"{{ $quote['text'] }}"</p>
        <p class="text-xs text-gray-200 mt-0.5">— {{ $quote['author'] }}</p>
    </div>

</div>{{-- end white card --}}

</x-staff-layout>
