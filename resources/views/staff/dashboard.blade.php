<x-staff-layout title="Dashboard" :hide-header="true">

@php
    $fmt12 = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '—';

    // Avatar initials
    $initials = strtoupper(
        substr($employee->first_name ?? '', 0, 1) .
        substr($employee->last_name  ?? '', 0, 1)
    );

    // Unread notifications count
    $unread = Auth::user()->unreadNotifications()->count();

    // --- Ring visuals ---
    $ringBg = match($clockState) {
        'in'    => '#0f1410',
        'break' => '#f4a53c',
        'out'   => '#ffffff',
        default => '#8bc53f',   // none
    };
    $ringTextColor = match($clockState) {
        'in'  => '#ffffff',
        'out' => '#6b7768',
        default => '#0f1410',
    };
    $ringShadow = match($clockState) {
        'none'  => '0 10px 22px -8px rgba(110,168,48,.45), 0 3px 6px rgba(110,168,48,.18)',
        'in'    => '0 12px 24px -8px rgba(15,20,16,.18), 0 4px 8px rgba(15,20,16,.08)',
        'break' => '0 10px 22px -8px rgba(244,165,60,.5)',
        'out'   => '0 4px 10px -4px rgba(15,20,16,.08), 0 2px 4px rgba(15,20,16,.04)',
    };
    $ringBorder = $clockState === 'out' ? '1.5px solid #d9ddd6' : 'none';

    $ringArcColor = match($clockState) {
        'in'    => '#8bc53f',
        'break' => '#f4a53c',
        'out'   => '#6b7768',
        default => null,
    };
    // Static progress per state (purely visual, no live clock)
    $ringProgress = match(true) {
        $clockState === 'in' && $nextEvent === 'am_out'   => 0.35,  // just clocked in, break not taken yet
        $clockState === 'in' && $nextEvent === 'time_out' => 0.75,  // back from break, nearing end of shift
        $clockState === 'break'                           => 0.55,
        $clockState === 'out'                             => 1.0,
        default                                           => 0,
    };
    $circumference = 2 * M_PI * 92;
    $strokeOffset  = $circumference * (1 - $ringProgress);

    // Tick marks (9 ticks, 40° apart, just outside the ring stroke)
    $ticks = [];
    for ($i = 0; $i < 9; $i++) {
        $a = (-90 + $i * 40) * M_PI / 180;
        $ticks[] = [
            'x1' => round(120 + cos($a) * 100, 2),
            'y1' => round(120 + sin($a) * 100, 2),
            'x2' => round(120 + cos($a) * 107, 2),
            'y2' => round(120 + sin($a) * 107, 2),
        ];
    }

    // Ring center text
    $ringEyebrow = match($clockState) {
        'in'    => 'WORKING',
        'break' => 'ON BREAK',
        'out'   => 'SHIFT DONE',
        default => 'READY',
    };
    $ringBig = match(true) {
        $clockState === 'none'                   => 'Time In',
        $clockState === 'in' && $nextEvent === 'am_out'   => 'Start Break',
        $clockState === 'in' && $nextEvent === 'time_out' => 'Time Out',
        $clockState === 'break'                  => 'End Break',
        $clockState === 'out'                    => 'Done',
        default                                  => 'Time In',
    };
    $ringSub = match($clockState) {
        'none'  => ($todaySchedule['start'] ? 'Starts ' . $fmt12($todaySchedule['start']) : 'No schedule set'),
        'in'    => 'Since ' . $fmt12($todayDtr?->time_in),
        'break' => 'Since ' . $fmt12($todayDtr?->am_out),
        'out'   => $fmt12($todayDtr?->time_in) . ' — ' . $fmt12($todayDtr?->time_out),
    };

    // Ring meta line (below ring)
    $ringMeta = match($clockState) {
        'none'  => $todaySchedule['start'] ? 'Today · ' . $fmt12($todaySchedule['start']) . ' – ' . $fmt12($todaySchedule['end']) : 'No shift scheduled today',
        'in'    => 'Clocked in · ' . $fmt12($todayDtr?->time_in),
        'break' => 'On break since ' . $fmt12($todayDtr?->am_out),
        'out'   => ($todayDtr?->total_hours ? $todayDtr->total_hours . 'h logged' : '') . ' · today complete',
    };

    // Event labels for the bottom sheet
    $eventLabels = [
        'time_in'  => 'Time In',
        'am_out'   => 'Start Break',
        'pm_in'    => 'End Break',
        'time_out' => 'Time Out',
    ];

    // Yesterday labels
    $yesterday      = today()->subDay();
    $yesterdayLabel = $yesterday->format('D, M d Y');
    $yesterdayEventLabel = $eventLabels[$yesterdayNextEvent ?? 'time_out'] ?? 'Time Out';

    // Today schedule card
    $todayPill = match(true) {
        $clockState === 'out'                          => 'done',
        in_array($clockState, ['in', 'break'])         => 'on shift',
        $todaySchedule['is_day_off'] === true          => 'off',
        $todaySchedule['source'] === 'none'            => '',
        default                                        => 'scheduled',
    };
    $todayPillBg = match($todayPill) {
        'done'      => '#2e3a2a',
        'on shift'  => '#557f24',
        'off'       => '#8d9889',
        'scheduled' => '#6ea830',
        default     => '#8d9889',
    };

    // Tomorrow schedule card
    $tomorrowDateLabel = today()->addDay()->format('D, M j');
    $tomorrowIsOff = $tomorrowSchedule['is_day_off'] || $tomorrowSchedule['source'] === 'none';
@endphp

@push('head')
<style>
    /* Dashboard-scoped tokens */
    .dash-ring-btn { transition: transform .12s ease; }
    .dash-ring-btn:active { transform: scale(.97); }
    .quote-serif { font-family: 'Source Serif 4', Georgia, serif; }
    .live-dot {
        display: inline-block; width: 6px; height: 6px; border-radius: 50%;
        background: #8bc53f; margin-right: 6px;
        animation: ring-pulse 2s ease-in-out infinite;
    }
    @media (prefers-reduced-motion: reduce) { .live-dot { animation: none; } }
    @keyframes ring-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .4; transform: scale(.85); }
    }
</style>
@endpush

{{-- ========================================
     Alpine wrapper: ring + bottom sheet
     ======================================== --}}
<div x-data="{
    open: false,
    date: '{{ today()->format('Y-m-d') }}',
    dateLabel: '{{ today()->format('D, M d Y') }}',
    event: '',
    label: '',
    time: '',
    hasOt: false,
    otHours: '',
    otError: '',
    note: '',
    todayNote: '{{ addslashes($todayDtr?->notes ?? '') }}',
    yesterdayNote: '{{ addslashes($yesterdayDtr?->notes ?? '') }}'
}">

{{-- ── Greeting row ── --}}
<div class="flex items-center justify-between gap-3 pt-2 pb-1">
    <div>
        <p class="text-xs font-semibold" style="color:#6b7768; letter-spacing:.03em;">
            {{ today()->format('l · M j') }}
        </p>
        <h1 class="text-2xl font-bold tracking-tight" style="color:#0f1410; line-height:1.15;">
            Hi, {{ $employee->first_name }}
        </h1>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        {{-- Bell --}}
        <a href="{{ route('staff.notifications.index') }}" class="relative p-1">
            <svg class="w-5 h-5" style="color:#4a5748;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 8H4c0-2 2-3 2-8Z"/>
                <path d="M10 21a2 2 0 0 0 4 0"/>
            </svg>
            @if($unread > 0)
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white" style="background:#f4a53c;"></span>
            @endif
        </a>
        {{-- Avatar --}}
        <a href="{{ route('staff.profile') }}"
           class="relative w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm overflow-hidden"
           style="background:#cfe7a4; color:#3f5e1b; border:2px solid #fff; box-shadow:0 1px 2px rgba(15,20,16,.1);"
           aria-label="Profile">
            @if(Auth::user()->profile_photo_url)
                <img src="{{ Auth::user()->profile_photo_url }}" alt="" class="w-full h-full object-cover">
            @else
                {{ $initials }}
            @endif
            @if($unread > 0)
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white" style="background:#f4a53c;"></span>
            @endif
        </a>
    </div>
</div>

{{-- ── Yesterday open shift alert ── --}}
@if($yesterdayDtr)
<div class="mt-3 rounded-2xl border px-4 py-3" style="background:#fffbeb; border-color:#fcd34d;">
    <div class="flex items-start gap-3">
        <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background:#fef3c7;">
            <svg class="w-4 h-4" style="color:#d97706;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold" style="color:#92400e;">
                Unfinished shift — {{ $yesterday->format('D, M j') }}
            </p>
            <p class="text-xs mt-0.5" style="color:#a16207;">
                Clocked in {{ $fmt12($yesterdayDtr->time_in) }} · no time out recorded
            </p>
        </div>
    </div>
    <div class="flex gap-2 mt-3">
        <button type="button"
            @click="date = '{{ $yesterday->format('Y-m-d') }}'; dateLabel = '{{ $yesterdayLabel }}'; event = '{{ $yesterdayNextEvent }}'; label = '{{ $yesterdayEventLabel }}'; time = String(new Date().getHours()).padStart(2,'0') + ':' + String(new Date().getMinutes()).padStart(2,'0'); hasOt = {{ $yesterdayNextEvent === 'time_out' ? 'false' : 'false' }}; otHours = ''; otError = ''; note = yesterdayNote; open = true"
            class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white text-center" style="background:#0f1410;">
            {{ $yesterdayEventLabel }} Now
        </button>
        <a href="{{ route('staff.dtr.edit', $yesterdayDtr) }}"
           class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-center border" style="border-color:#0f1410; color:#0f1410; background:#fff;">
            Edit time
        </a>
    </div>
</div>
@endif

{{-- ── Ring hero ── --}}
<div class="flex flex-col items-center mt-4">

    {{-- SVG progress ring + center button --}}
    {{-- The outer div is the tap target for tappable states, covering the full ring area --}}
    @if(!$yesterdayDtr && $clockState !== 'out')
    <button type="button"
        class="dash-ring-btn relative flex items-center justify-center"
        style="width:240px; height:240px; border-radius:50%; background:transparent; border:none; padding:0; font-family:inherit; cursor:pointer;"
        @click="date = '{{ today()->format('Y-m-d') }}';
                dateLabel = '{{ today()->format('D, M d Y') }}';
                event = '{{ $nextEvent }}';
                label = '{{ $eventLabels[$nextEvent] ?? '' }}';
                time = String(new Date().getHours()).padStart(2,'0') + ':' + String(new Date().getMinutes()).padStart(2,'0');
                hasOt = false; otHours = ''; otError = '';
                note = todayNote; open = true">
    @else
    <div class="relative flex items-center justify-center" style="width:240px; height:240px;">
    @endif

        {{-- Ring SVG — pointer-events:none so it never blocks the button beneath --}}
        <svg width="240" height="240" viewBox="0 0 240 240"
             style="position:absolute;inset:0;pointer-events:none;">
            {{-- Track --}}
            <circle cx="120" cy="120" r="92" fill="none"
                stroke="{{ $clockState === 'none' ? '#d9ddd6' : '#e3eadb' }}"
                stroke-width="10" stroke-linecap="round"
                @if($clockState === 'none') stroke-dasharray="2 7" @endif />

            {{-- Progress arc --}}
            @if($clockState !== 'none')
            <circle cx="120" cy="120" r="92" fill="none"
                stroke="{{ $ringArcColor }}"
                stroke-width="10" stroke-linecap="round"
                stroke-dasharray="{{ $circumference }}"
                stroke-dashoffset="{{ $strokeOffset }}"
                transform="rotate(-90 120 120)" />
            @endif

            {{-- Hour tick marks --}}
            @foreach($ticks as $tick)
            <line x1="{{ $tick['x1'] }}" y1="{{ $tick['y1'] }}"
                  x2="{{ $tick['x2'] }}" y2="{{ $tick['y2'] }}"
                  stroke="#8d9889" stroke-width="1.4" stroke-linecap="round"/>
            @endforeach
        </svg>

        {{-- Visual center circle (non-interactive, purely visual) --}}
        @if($yesterdayDtr)
            <div class="flex flex-col items-center justify-center text-center rounded-full"
                 style="width:186px; height:186px; background:#f7f8f5; border:1.5px solid #d9ddd6; box-shadow:0 2px 6px rgba(15,20,16,.06); pointer-events:none;">
                <span class="text-xs font-bold tracking-widest mb-1" style="color:#8d9889; letter-spacing:.14em;">LOCKED</span>
                <span class="font-bold leading-tight" style="font-size:28px; color:#4a5748; letter-spacing:-.02em;">Resolve<br>first</span>
                <span class="text-xs mt-2" style="color:#8d9889;">Close yesterday above</span>
            </div>
        @elseif($clockState === 'out')
            <div class="flex flex-col items-center justify-center text-center rounded-full"
                 style="width:186px; height:186px; background:{{ $ringBg }}; border:{{ $ringBorder }}; box-shadow:{{ $ringShadow }}; color:{{ $ringTextColor }}; pointer-events:none;">
                <span class="text-xs font-bold tracking-widest" style="letter-spacing:.14em; opacity:.7;">{{ $ringEyebrow }}</span>
                <span class="font-bold leading-tight mt-1" style="font-size:28px; letter-spacing:-.02em;">{{ $ringBig }}</span>
                <span class="text-xs mt-1.5" style="opacity:.75;">{{ $ringSub }}</span>
            </div>
        @else
            <div class="flex flex-col items-center justify-center text-center rounded-full"
                 style="width:186px; height:186px; background:{{ $ringBg }}; border:{{ $ringBorder }}; box-shadow:{{ $ringShadow }}; color:{{ $ringTextColor }}; pointer-events:none;">
                <span class="text-xs font-bold tracking-widest" style="letter-spacing:.14em; opacity:.7;">{{ $ringEyebrow }}</span>
                <span class="font-bold leading-tight mt-1" style="font-size:28px; letter-spacing:-.02em;">{{ $ringBig }}</span>
                <span class="text-xs mt-1.5" style="opacity:.75;">{{ $ringSub }}</span>
            </div>
        @endif

    @if(!$yesterdayDtr && $clockState !== 'out')
    </button>
    @else
    </div>
    @endif

    {{-- Ring meta --}}
    <p class="text-xs font-medium mt-2 text-center" style="color:{{ in_array($clockState, ['in','break']) ? '#6ea830' : '#6b7768' }};">
        @if(in_array($clockState, ['in', 'break']))
            <span class="live-dot"></span>
        @endif
        {{ $ringMeta }}
    </p>

    @if($yesterdayDtr)
        <p class="text-xs font-semibold mt-1.5" style="color:#d97706;">
            ● Resolve yesterday's shift to continue
        </p>
    @endif

</div>{{-- /ring hero --}}

{{-- ── Up next / Agenda ── --}}
<div class="flex items-center justify-between mt-6 mb-2.5">
    <h2 class="text-base font-bold tracking-tight" style="color:#0f1410;">Up next</h2>
    <a href="{{ route('staff.schedule') }}"
       class="flex items-center gap-1 text-xs font-semibold" style="color:#6ea830;">
        Schedule
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg>
    </a>
</div>

<div class="grid grid-cols-2 gap-2.5">

    {{-- Today card --}}
    <div class="rounded-2xl border p-3.5 relative overflow-hidden"
         style="background:linear-gradient(180deg,#f7fbeb 0%,#fff 100%); border-color:#cfe7a4;">
        <div class="absolute top-0 left-0 right-0 h-0.5 rounded-t-2xl" style="background:#8bc53f;"></div>
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold tracking-widest uppercase" style="color:#6ea830; letter-spacing:.08em;">
                TODAY · {{ strtoupper(today()->format('D')) }}
            </span>
            @if($todayPill)
                <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" style="background:{{ $todayPillBg }}; letter-spacing:.02em;">
                    {{ strtoupper($todayPill) }}
                </span>
            @endif
        </div>

        @if($todaySchedule['is_day_off'])
            <div class="text-lg font-bold tracking-tight" style="color:#6b7768;">Rest Day</div>
            <div class="text-xs mt-1" style="color:#8d9889;">no scheduled shift</div>
        @elseif($todaySchedule['source'] === 'none')
            <div class="text-lg font-bold tracking-tight" style="color:#8d9889;">No schedule</div>
            <div class="text-xs mt-1" style="color:#b4bcb0;">not set</div>
        @else
            <div class="text-2xl font-bold tracking-tight" style="color:#0f1410; line-height:1.05;">
                {{ $fmt12($todaySchedule['start']) }}
            </div>
            <div class="text-sm font-medium mt-0.5" style="color:#4a5748;">
                — {{ $fmt12($todaySchedule['end']) }}
            </div>
        @endif

        @if($todayDtr && $todayDtr->time_in && $todayDtr->time_out)
            <div class="flex items-center gap-1 mt-2 text-xs" style="color:#6b7768;">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                {{ $todayDtr->total_hours }}h logged
            </div>
        @endif
    </div>

    {{-- Tomorrow card --}}
    <div class="rounded-2xl border p-3.5" style="background:#fff; border-color:#d9ddd6;">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold tracking-widest uppercase" style="color:#8d9889; letter-spacing:.08em;">
                TMRW · {{ strtoupper(today()->addDay()->format('D')) }}
            </span>
            @if($tomorrowIsOff)
                <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" style="background:#8d9889;">OFF</span>
            @else
                <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" style="background:#6ea830;">ON</span>
            @endif
        </div>

        @if($tomorrowSchedule['is_day_off'] === true)
            <div class="text-lg font-bold tracking-tight" style="color:#8d9889;">Off</div>
            <div class="text-xs mt-1" style="color:#b4bcb0;">rest day</div>
        @elseif($tomorrowSchedule['source'] === 'none' || $tomorrowSchedule['is_day_off'] === null)
            <div class="text-lg font-bold tracking-tight" style="color:#8d9889;">No schedule</div>
            <div class="text-xs mt-1" style="color:#b4bcb0;">not set</div>
        @else
            <div class="text-2xl font-bold tracking-tight" style="color:#0f1410; line-height:1.05;">
                {{ $fmt12($tomorrowSchedule['start']) }}
            </div>
            <div class="text-sm font-medium mt-0.5" style="color:#4a5748;">
                — {{ $fmt12($tomorrowSchedule['end']) }}
            </div>
        @endif
    </div>

</div>{{-- /agenda --}}

{{-- ── Approvals waiting (approvers only) ── --}}
@if($pendingApprovalCount > 0)
<div class="mt-3 rounded-2xl border px-4 py-3 flex items-center justify-between"
     style="background:#fdf2df; border-color:#fcd34d;">
    <div>
        <p class="text-xs font-semibold" style="color:#92400e;">Pending your approval</p>
        <p class="text-2xl font-bold" style="color:#d97706;">{{ $pendingApprovalCount }}</p>
    </div>
    <a href="{{ route('staff.approvals.index') }}"
       class="text-xs font-semibold text-white px-3 py-2 rounded-xl"
       style="background:#d97706;">
        Review
    </a>
</div>
@endif

{{-- ── Quote footer ── --}}
<div class="mt-5 pt-4 text-center" style="border-top:1px dashed #d9ddd6;">
    <p class="quote-serif text-sm leading-relaxed" style="color:#4a5748; font-style:italic;">
        "{{ $quote['text'] }}"
    </p>
</div>

{{-- ── Bottom Sheet ── --}}
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
            <p class="text-xs text-red-700 font-medium">Make sure the times you enter match exactly what is recorded in your timemark.</p>
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
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-lg font-semibold text-gray-800 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <p x-show="time" x-cloak class="text-sm font-semibold mt-1.5 text-center" style="color:#6ea830;"
                   x-text="time ? new Date('1970-01-01T' + time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : ''"></p>
            </div>

            {{-- OT section — only for Time Out --}}
            <div x-show="event === 'time_out'" x-cloak class="mb-5">
                <div class="rounded-xl p-3" style="background:#fdf2df; border:1px solid #fcd34d;">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="has_ot" value="1" x-model="hasOt"
                               class="w-5 h-5 rounded border-gray-300 focus:ring-amber-400" style="accent-color:#d97706;">
                        <span class="text-sm font-medium" style="color:#92400e;">I have overtime</span>
                    </label>
                    <div x-show="hasOt" x-cloak class="mt-3">
                        <label class="block text-sm font-medium mb-1" style="color:#92400e;">Overtime Hours</label>
                        <input type="number" name="ot_hours" x-model="otHours"
                               min="0.25" max="24" step="0.25"
                               placeholder="e.g. 2, 1.5, or 0.75"
                               @input="otError = ''"
                               class="w-full border rounded-xl px-4 py-3 text-sm focus:ring-2 bg-white"
                               style="border-color:#fcd34d; focus:ring-color:#d97706;">
                        <p x-show="otError" x-cloak class="text-xs text-red-600 font-medium mt-1" x-text="otError"></p>
                        <p class="text-xs mt-1" style="color:#d97706;">Your overtime will be sent for approval.</p>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Note <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea name="notes" x-model="note" rows="2" maxlength="500"
                          placeholder="Note any reason here — late, early out, or anything else…"
                          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" @click="open = false"
                        class="flex-1 border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl text-sm">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 text-white font-semibold py-3 rounded-xl text-sm"
                        style="background:#6ea830;">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- /Alpine wrapper --}}

</x-staff-layout>
