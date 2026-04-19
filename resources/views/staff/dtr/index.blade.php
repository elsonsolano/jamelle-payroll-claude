<x-staff-layout title="My DTR">

@push('head')
<style>
    .dtr-pulse { animation: dtr-blink 2s ease-in-out infinite; }
    @keyframes dtr-blink { 0%,100%{opacity:1} 50%{opacity:.35} }
    /* 60px = layout sticky header height; 44px = list header height */
    .week-header-sticky { position:sticky; top:104px; z-index:9; }
</style>
@endpush

@php
    $today    = today();
    $todayStr = $today->format('Y-m-d');
    $fmt12    = fn($t) => $t ? date('g:i A', strtotime($t)) : null;

    $dtrStatus = function($dtr) use ($todayStr) {
        if ($dtr->time_out) {
            return $dtr->ot_status === 'approved' ? 'ot' : 'complete';
        }
        return $dtr->date->format('Y-m-d') === $todayStr ? 'in-progress' : 'incomplete';
    };

    $weekGroups = $dtrs->getCollection()
        ->groupBy(fn($d) => $d->date->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->format('Y-m-d'))
        ->sortKeysDesc();
@endphp

{{-- ── Sticky list header ── --}}
<div class="-mx-4 sticky top-[60px] z-10 flex items-center justify-between px-4 py-2.5 border-b"
     style="background:rgba(247,248,245,.92); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); border-color:#d9ddd6;">
    <h2 class="text-[14px] font-bold" style="color:#0f1410;">All DTR records</h2>
    <a href="{{ route('staff.dtr.create') }}"
       class="inline-flex items-center gap-[5px] text-[12px] font-bold text-white px-3 py-[7px] rounded-[10px]"
       style="background:#0f1410;">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        New DTR
    </a>
</div>

{{-- ── Pending OT alert ── --}}
@php $pendingOtCount = $dtrs->getCollection()->where('ot_status', 'pending')->count(); @endphp
@if($pendingOtCount > 0)
<div class="mt-3 px-4 py-3 rounded-2xl border flex items-center gap-3"
     style="background:#fdf2df; border-color:#f8d99a;">
    <svg class="w-5 h-5 shrink-0" style="color:#f4a53c;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="text-sm font-semibold" style="color:#8a5a12;">
            {{ $pendingOtCount }} pending OT {{ Str::plural('request', $pendingOtCount) }}
        </p>
        <p class="text-xs" style="color:#8a5a12;">Awaiting approval from your supervisor.</p>
    </div>
</div>
@endif

{{-- ── Content ── --}}
@if($dtrs->isEmpty())

    {{-- Empty state --}}
    <div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
             style="background:#eef1ec; color:#8d9889;">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 7h6m-6 4h4"/>
            </svg>
        </div>
        <div>
            <div class="text-[15px] font-bold" style="color:#0f1410;">No records yet</div>
            <div class="text-xs mt-1" style="color:#6b7768;">Your daily time records will appear here.</div>
        </div>
        <a href="{{ route('staff.dtr.create') }}"
           class="mt-1 text-[13px] font-bold px-[18px] py-2.5 rounded-xl text-white"
           style="background:#0f1410;">
            + New DTR entry
        </a>
    </div>

@else

    {{-- Week groups --}}
    @foreach($weekGroups as $weekStartStr => $weekDtrs)
    @php
        $weekStart   = \Carbon\Carbon::parse($weekStartStr);
        $weekEnd     = $weekStart->copy()->addDays(6);
        $isThisWeek  = $today->between($weekStart, $weekEnd);
        $weekHours   = $weekDtrs->whereNotNull('time_out')->sum('total_hours');
        $weekPct     = min(($weekHours / 40) * 100, 100);

        $startFmt = $weekStart->format('M j');
        $endFmt   = ($weekStart->month !== $weekEnd->month)
            ? $weekEnd->format('M j')
            : $weekEnd->format('j');
        $weekLabel = $isThisWeek
            ? 'This week · ' . $startFmt . '–' . $endFmt
            : 'Week of ' . $startFmt . '–' . $endFmt;
    @endphp

    <div class="mb-1">

        {{-- Week header — sticky below list header (~44px) --}}
        <div class="-mx-4 week-header-sticky flex items-center justify-between px-4 py-[10px]"
             style="background:rgba(247,248,245,.92); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);">
            <span class="text-[11px] font-bold uppercase tracking-[.06em]" style="color:#6b7768;">
                {{ $weekLabel }}
            </span>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-bold whitespace-nowrap" style="color:#2e3a2a;">
                    {{ number_format($weekHours, 1) }}h
                </span>
                <div class="w-14 h-[5px] rounded-full overflow-hidden" style="background:#d9ddd6;">
                    <div class="h-full rounded-full" style="width:{{ $weekPct }}%; background:#8bc53f;"></div>
                </div>
            </div>
        </div>

        {{-- Record cards --}}
        <div class="-mx-4 px-3 pt-1.5 pb-1 flex flex-col gap-1.5">
            @foreach($weekDtrs->sortByDesc('date') as $dtr)
            @php
                $status   = $dtrStatus($dtr);
                $tin      = $fmt12($dtr->time_in);
                $brk      = $fmt12($dtr->am_out);
                $endBrk   = $fmt12($dtr->pm_in);
                $tout     = $fmt12($dtr->time_out);
                $isToday  = $dtr->date->format('Y-m-d') === $todayStr;

                $cardBg = match($status) {
                    'incomplete'  => 'linear-gradient(135deg,#fdf2df 0%,#fff 60%)',
                    'in-progress' => 'linear-gradient(135deg,#f1f8e4 0%,#fff 60%)',
                    default       => '#fff',
                };
                $cardBorder = match($status) {
                    'incomplete'  => '#f8d99a',
                    'in-progress' => '#cfe7a4',
                    default       => '#d9ddd6',
                };
                $accentColor = match($status) {
                    'incomplete'  => '#f4a53c',
                    'in-progress' => '#8bc53f',
                    default       => null,
                };
                $dateNumColor = $isToday ? '#557f24' : ($status === 'incomplete' ? '#8a5a12' : '#0f1410');
                $chips = [
                    ['label'=>'IN',  'aria'=>'Time In',     'val'=>$tin,    'active'=>($status==='in-progress')],
                    ['label'=>'BRK', 'aria'=>'Start Break', 'val'=>$brk,    'active'=>false],
                    ['label'=>'END', 'aria'=>'End Break',   'val'=>$endBrk, 'active'=>false],
                    ['label'=>'OUT', 'aria'=>'Time Out',    'val'=>$tout,   'active'=>false],
                ];
            @endphp

            <div class="relative overflow-hidden rounded-[14px] border"
                 style="background:{{ $cardBg }}; border-color:{{ $cardBorder }}; box-shadow:0 1px 2px rgba(15,20,16,.04),0 1px 1px rgba(15,20,16,.03);"
                 role="listitem"
                 aria-label="{{ $dtr->date->format('l, F j Y') }}">

                {{-- Left accent border --}}
                @if($accentColor)
                <div class="absolute left-0 top-0 bottom-0 w-[3px] rounded-[3px_0_0_3px]"
                     style="background:{{ $accentColor }};"></div>
                @endif

                {{-- Main clickable row --}}
                <a href="{{ route('staff.dtr.edit', $dtr) }}"
                   class="flex items-center gap-2.5 pl-[14px] pr-3 py-[10px] no-underline">

                    {{-- Date --}}
                    <div class="min-w-[46px] shrink-0 flex flex-col items-center gap-[1px]">
                        <span class="text-[9px] font-bold uppercase tracking-[.08em]"
                              style="color:#8d9889;">{{ $dtr->date->format('D') }}</span>
                        <span class="text-[22px] font-bold leading-none"
                              style="letter-spacing:-.02em; color:{{ $dateNumColor }};">{{ $dtr->date->format('j') }}</span>
                        <span class="text-[9px] font-semibold"
                              style="color:#6b7768;">{{ $dtr->date->format('M') }}</span>
                    </div>

                    {{-- Divider --}}
                    <div class="w-px h-11 shrink-0" style="background:#d9ddd6;"></div>

                    {{-- Body --}}
                    <div class="flex-1 min-w-0 flex flex-col gap-[5px]">

                        {{-- Punch chips --}}
                        <div class="flex items-center gap-1 overflow-hidden">
                            @foreach($chips as $i => $chip)
                                @if($i > 0)
                                    <span class="text-[10px] shrink-0" style="color:#b4bcb0;">›</span>
                                @endif
                                @php
                                    $missing = !$chip['val'];
                                    if ($missing) {
                                        $chipBg  = 'background:#fdf2df; border:1px dashed #f8d99a;';
                                        $lblClr  = '#8a5a12';
                                        $valClr  = '#8a5a12';
                                    } elseif ($chip['active']) {
                                        $chipBg  = 'background:#f1f8e4; border:1px solid #cfe7a4;';
                                        $lblClr  = '#557f24';
                                        $valClr  = '#557f24';
                                    } else {
                                        $chipBg  = 'background:#f7f8f5;';
                                        $lblClr  = '#8d9889';
                                        $valClr  = '#1b2419';
                                    }
                                @endphp
                                <span class="inline-flex items-center gap-[3px] text-[10px] font-semibold whitespace-nowrap px-[6px] py-[2px] rounded-[6px]"
                                      style="{{ $chipBg }}"
                                      aria-label="{{ $chip['aria'] }}: {{ $chip['val'] ?? 'not set' }}">
                                    <span style="color:{{ $lblClr }}; font-size:9px; font-weight:700; letter-spacing:.04em; text-transform:uppercase;">{{ $chip['label'] }}</span>
                                    <span style="color:{{ $valClr }};">{{ $chip['val'] ?? '—' }}</span>
                                </span>
                            @endforeach
                        </div>

                        {{-- Footer row --}}
                        <div class="flex items-center gap-[6px] flex-wrap">
                            @if($dtr->time_out)
                                <span class="text-[11px] font-bold" style="color:#1b2419;">{{ $dtr->total_hours }}h</span>
                            @elseif($status === 'in-progress')
                                <span class="text-[11px] font-bold" style="color:#8d9889;">Working…</span>
                            @else
                                <span class="text-[11px] font-bold" style="color:#8d9889;">0.0h</span>
                            @endif

                            @if($dtr->ot_status === 'approved' && $dtr->overtime_hours)
                                <span class="w-[3px] h-[3px] rounded-full shrink-0" style="background:#b4bcb0;"></span>
                                <span class="text-[11px] font-bold" style="color:#557f24;">OT {{ $dtr->overtime_hours }}h</span>
                            @endif

                            <span class="w-[3px] h-[3px] rounded-full shrink-0" style="background:#b4bcb0;"></span>

                            {{-- Status badge --}}
                            @if($status === 'in-progress')
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-[7px] py-[2px] rounded-full"
                                      style="background:#f1f8e4; color:#557f24;">
                                    <span class="dtr-pulse inline-block w-[6px] h-[6px] rounded-full shrink-0"
                                          style="background:#8bc53f;"></span>
                                    Working
                                </span>
                            @elseif($status === 'incomplete')
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-[7px] py-[2px] rounded-full"
                                      style="background:#fdf2df; color:#8a5a12;">
                                    <svg class="w-[10px] h-[10px] shrink-0" fill="none" stroke="currentColor"
                                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/>
                                    </svg>
                                    Missing out
                                </span>
                            @elseif($status === 'ot')
                                <span class="text-[10px] font-bold px-[7px] py-[2px] rounded-full"
                                      style="background:#e3f1c9; color:#3f5e1b;">OT Approved</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-[7px] py-[2px] rounded-full"
                                      style="background:#f1f8e4; color:#557f24;">
                                    <svg class="w-[10px] h-[10px] shrink-0" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M20 6 9 17l-5-5"/>
                                    </svg>
                                    Complete
                                </span>
                            @endif

                            @if($dtr->ot_status === 'pending')
                                <span class="w-[3px] h-[3px] rounded-full shrink-0" style="background:#b4bcb0;"></span>
                                <span class="text-[10px] font-bold px-[7px] py-[2px] rounded-full"
                                      style="background:#fdf2df; color:#8a5a12;">OT Pending</span>
                            @elseif($dtr->ot_status === 'rejected')
                                <span class="w-[3px] h-[3px] rounded-full shrink-0" style="background:#b4bcb0;"></span>
                                <span class="text-[10px] font-bold px-[7px] py-[2px] rounded-full"
                                      style="background:#fee2e2; color:#991b1b;">OT Rejected</span>
                            @endif
                        </div>
                    </div>

                    {{-- Chevron --}}
                    <div class="shrink-0" style="color:{{ $status === 'incomplete' ? '#f4a53c' : '#8d9889' }};">
                        <svg class="w-[14px] h-[14px]" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="m9 6 6 6-6 6"/>
                        </svg>
                    </div>
                </a>

                {{-- Resolve bar — incomplete records only --}}
                @if($status === 'incomplete')
                <div x-data="{ dismissed: false }" x-show="!dismissed"
                     class="flex items-center gap-2 pl-[14px] pr-3 py-2 border-t"
                     style="border-color:#f8d99a; background:rgba(253,242,223,.5);"
                     role="alert">
                    <span class="flex items-center gap-1 text-[11px] font-semibold flex-1 leading-tight"
                          style="color:#8a5a12;">
                        <svg class="w-[11px] h-[11px] shrink-0" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/>
                        </svg>
                        No time out · {{ $dtr->date->format('M j') }}
                    </span>
                    <a href="{{ route('staff.dtr.edit', $dtr) }}"
                       class="text-[11px] font-bold px-2.5 py-[5px] rounded-[8px] text-white shrink-0"
                       style="background:#0f1410; min-height:44px; display:inline-flex; align-items:center;">
                        Fix now
                    </a>
                    <button type="button"
                            class="text-[11px] font-bold px-2.5 py-[5px] rounded-[8px] border shrink-0"
                            style="background:#fff; color:#2e3a2a; border-color:#d9ddd6; min-height:44px;"
                            @click="dismissed = true">
                        Skip
                    </button>
                </div>
                @endif

            </div>{{-- /record card --}}
            @endforeach
        </div>

    </div>
    @endforeach

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $dtrs->links() }}
    </div>

@endif

</x-staff-layout>
