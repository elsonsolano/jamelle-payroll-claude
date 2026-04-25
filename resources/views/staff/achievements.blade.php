<x-staff-layout title="Achievements">

@push('head')
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900&display=swap" rel="stylesheet" />
<style>
    .ach-font { font-family: 'Plus Jakarta Sans', sans-serif; }
    .achievement-mascot-idle {
        animation: mascot-achievement-float 3.4s ease-in-out infinite;
        transform-origin: center bottom;
        will-change: transform;
        transition: transform .18s ease, filter .18s ease;
    }
    .achievement-mascot-shell {
        transition: transform .18s ease, box-shadow .18s ease;
        will-change: transform;
        position: relative;
        border-radius: 9999px;
        padding: 2px;
        isolation: isolate;
    }
    .achievement-mascot-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background:
            conic-gradient(
                from 0deg,
                rgba(34, 197, 94, 0.14) 0deg,
                rgba(57, 255, 20, 0.95) 74deg,
                rgba(170, 255, 80, 0.72) 114deg,
                rgba(34, 197, 94, 0.14) 180deg,
                rgba(34, 197, 94, 0.08) 360deg
            );
        animation: mascot-achievement-ring 2.8s linear infinite;
        box-shadow:
            0 0 12px rgba(57, 255, 20, 0.2),
            0 0 20px rgba(57, 255, 20, 0.09);
        z-index: -1;
    }
    .achievement-mascot-core {
        width: 100%;
        height: 100%;
        border-radius: calc(9999px - 2px);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .achievement-rank-hero:hover .achievement-mascot-shell,
    .achievement-rank-hero:active .achievement-mascot-shell {
        transform: scale(1.045);
        box-shadow: 0 12px 22px rgba(232,114,42,.12);
    }
    .achievement-rank-hero:hover .achievement-mascot-idle,
    .achievement-rank-hero:active .achievement-mascot-idle {
        animation-play-state: paused;
        transform: translateY(-5px) rotate(2.2deg) scale(1.1);
        filter: drop-shadow(0 10px 18px rgba(232,114,42,.2));
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes mascot-achievement-float {
        0%, 100% { transform: translateY(0) rotate(-1.5deg) scale(1); }
        25% { transform: translateY(-3px) rotate(1deg) scale(1.02); }
        50% { transform: translateY(-6px) rotate(1.8deg) scale(1.04); }
        75% { transform: translateY(-2px) rotate(-0.8deg) scale(1.015); }
    }
    @keyframes mascot-achievement-ring {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    @media (prefers-reduced-motion: reduce) {
        .achievement-mascot-idle {
            animation: none;
            transition: none;
        }
    }
    .fade-up { animation: fadeUp 0.18s ease; }
</style>
@endpush

@php
    $fmt = fn($d) => \Carbon\Carbon::parse($d)->format('M j');
@endphp

<script>
function achievementsPage() {
    return {
        selected: null,
        showLog: false,
        openTip: null,
        toggle(id) {
            this.selected = this.selected === id ? null : id;
        },
    };
}
</script>

<div x-data="achievementsPage()" x-init="localStorage.setItem('achievements-seen','1')" class="ach-font -m-4 pb-4">

    {{-- ─── Header ─── --}}
    <div class="px-4 pt-4 pb-2">
        <p class="text-xs font-medium" style="color:#999;">{{ today()->format('D, M j, Y') }}</p>
        <h1 class="font-black leading-tight mt-0.5" style="font-size:26px; color:#111;">Achievements</h1>
    </div>

    <div class="px-4 flex flex-col gap-2.5 mt-3">

        {{-- ─── Combined Points + Rank Card ─── --}}
        <div class="rounded-2xl overflow-hidden" style="background:#fff; border:1.5px solid #EBEBEB;">

            {{-- Rank hero — centered, full-width --}}
            <div class="achievement-rank-hero flex flex-col items-center text-center px-4 pt-4 pb-4">
                <p class="text-xs font-bold uppercase tracking-widest" style="color:#C2510A; letter-spacing:.07em;">Your Rank</p>
                @php
                    $mascotFile = str_pad($rank['number'], 2, '0', STR_PAD_LEFT);
                    $hasMascot  = file_exists(public_path("images/rank-mascots/mascot-{$mascotFile}.png"));
                @endphp
                {{-- Mascot with points badge on upper-right --}}
                <div class="relative mt-3" style="width:96px; height:96px; flex-shrink:0;">
                    @if($hasMascot)
                        <div class="achievement-mascot-shell w-full h-full">
                            <div class="achievement-mascot-core" style="background:#FFF7ED;">
                                <img src="{{ asset("images/rank-mascots/mascot-{$mascotFile}.png") }}"
                                     alt="{{ $rank['name'] }}"
                                     class="w-full h-full object-cover achievement-mascot-idle">
                            </div>
                        </div>
                    @else
                        <div class="rounded-full w-full h-full flex items-center justify-center font-black text-white"
                             style="background:#E8722A; font-size:22px;">
                            #{{ $rank['number'] }}
                        </div>
                    @endif

                    {{-- Points badge — upper-right of the circle --}}
                    <div class="absolute flex items-center gap-0.5 rounded-full px-2 py-0.5 shadow-sm"
                         style="top:-6px; right:-10px; background:#5BBF27; border:2px solid #fff; white-space:nowrap;">
                        <span class="font-black text-white" style="font-size:11px; line-height:1.4;">{{ number_format($totalPoints) }}</span>
                        <span class="font-semibold text-white" style="font-size:9px; opacity:.85;">pts</span>
                    </div>
                </div>

                <p class="font-black mt-2 leading-tight" style="font-size:17px; color:#E8722A;">{{ $rank['name'] }}</p>
                <p class="text-xs mt-0.5 leading-tight" style="color:#E8722A; opacity:.7;">{{ $rank['desc'] }}</p>
                @if($thisCutoffPoints !== 0)
                    <p class="text-xs font-semibold mt-1.5" style="color:#3D8C18; opacity:.85;">{{ $thisCutoffPoints > 0 ? '+' : '' }}{{ $thisCutoffPoints }} pts recently</p>
                @endif
            </div>

            {{-- Progress bar row --}}
            <div class="px-4 pb-4">
                <div style="height:1px; background:#EBEBEB; margin-bottom:10px;"></div>
                @if(!$rank['is_max'])
                    <div class="flex justify-between mb-1.5" style="color:#3D8C18;">
                        <span class="text-xs font-medium">{{ $rank['name'] }}</span>
                        <span class="text-xs font-bold">{{ number_format($rank['points_to_next']) }} pts → {{ $rank['next_name'] }}</span>
                    </div>
                    <div class="rounded-full overflow-hidden" style="height:7px; background:#C8ECA4;">
                        <div class="h-full rounded-full" style="width:{{ $rank['progress_pct'] }}%; background:#5BBF27;"></div>
                    </div>
                @else
                    <p class="text-xs font-bold text-center" style="color:#5BBF27;">🏆 Max rank achieved!</p>
                @endif
            </div>

            {{-- Bottom strip --}}
            <div class="flex items-center gap-3 px-4 py-2.5" style="background:#EBF7E0; border-top:1px solid #EBEBEB;">
                <p class="text-xs flex-1" style="color:#3D8C18;">
                    <span class="font-bold" style="color:#111;">{{ $totalBadgesEarned }}</span> badges earned (all time)
                </p>
                @if(count($pointsLog) > 0)
                    <button type="button" @click="showLog = !showLog"
                            class="text-xs font-bold px-3 py-1 rounded-full shrink-0"
                            style="border:1.5px solid #5BBF27; color:#3D8C18; background:#fff;">
                        <span x-text="showLog ? 'Hide log' : 'View log'"></span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Points log (toggled by bottom strip button) --}}
        @if(count($pointsLog) > 0)
        <div x-show="showLog" x-cloak class="fade-up rounded-2xl overflow-hidden" style="background:#fff; border:1.5px solid #EBEBEB;">
            @foreach($pointsLog as $i => $entry)
                @php
                    $isPenalty = $entry['type'] === 'penalty';
                    $isBadge   = $entry['type'] === 'badge';
                @endphp
                <div class="flex items-center gap-3 px-4 py-2.5 {{ $i > 0 ? 'border-t' : '' }}" style="{{ $i > 0 ? 'border-color:#EBEBEB;' : '' }}">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 text-xs"
                         style="background:{{ $isBadge ? '#5BBF27' : ($isPenalty ? '#FEE2E2' : '#EBF7E0') }}; color:{{ $isBadge ? '#fff' : ($isPenalty ? '#DC2626' : '#3D8C18') }};">
                        {{ $isBadge ? '🏅' : ($isPenalty ? '▼' : '✦') }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold truncate" style="color:#111;">{{ $entry['description'] }}</p>
                        <p class="text-xs" style="color:#999;">{{ $entry['date'] }}</p>
                    </div>
                    <p class="text-xs font-bold shrink-0" style="color:{{ $isPenalty ? '#DC2626' : '#5BBF27' }};">{{ $isPenalty ? '' : '+' }}{{ $entry['points'] }}</p>
                </div>
            @endforeach
        </div>
        @endif

        {{-- ─── Badges Section ─── --}}
        <div class="flex items-center justify-between mt-1">
            <p class="font-extrabold" style="font-size:15px; color:#111;">Badges</p>
        </div>

        @foreach($badges as $badge)
        @php
            $isOpen   = false; // Alpine controls this
            $badgeId  = $badge['id'];
            $isStreak = $badge['type'] === 'streak';
        @endphp
        <div x-data @click="$dispatch('toggle-badge', '{{ $badgeId }}')"
             class="rounded-2xl overflow-hidden cursor-pointer select-none"
             :style="selected === '{{ $badgeId }}'
                 ? 'border:1.5px solid {{ $badge['border_color'] }}; background:#fff;'
                 : 'border:1.5px solid #EBEBEB; background:#fff;'"
             @toggle-badge.window="if ($event.detail === '{{ $badgeId }}') selected = selected === '{{ $badgeId }}' ? null : '{{ $badgeId }}'">

            {{-- Collapsed top row --}}
            <div class="flex items-center gap-3 p-4">
                {{-- Badge icon --}}
                <div class="relative shrink-0">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl"
                         style="background:{{ $badge['bg_color'] }}; border:1.5px solid {{ $badge['border_color'] }};">
                        {{ $badge['icon'] }}
                    </div>
                    @if($badge['times_earned'] > 0)
                        <div class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold text-white"
                             style="background:{{ $badge['color'] }}; font-size:9px;">
                            ×{{ $badge['times_earned'] }}
                        </div>
                    @endif
                </div>

                {{-- Badge info --}}
                <div class="flex-1 min-w-0">
                    <p class="font-extrabold text-sm truncate" style="color:#111;">{{ $badge['name'] }}</p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-xs font-bold rounded-full px-1.5 py-0.5"
                              style="background:{{ $badge['bg_color'] }}; color:{{ $badge['color'] }}; font-size:9px; border:1px solid {{ $badge['border_color'] }};">
                            {{ $isStreak ? 'Streak' : 'Progress' }}
                        </span>
                    </div>
                    <p class="text-xs mt-1 truncate" style="color:#999;">{{ $badge['tagline'] }}</p>
                    {{-- Progress bar --}}
                    <div class="mt-1.5 h-1.5 rounded-full overflow-hidden" style="background:#EBEBEB;">
                        @php
                            $pct = $badge['total'] > 0 ? min(100, round($badge['progress'] / $badge['total'] * 100)) : 0;
                        @endphp
                        <div class="h-full rounded-full" style="width:{{ $pct }}%; background:{{ $badge['color'] }};"></div>
                    </div>
                    <p class="text-xs mt-1 font-medium" style="color:#999;">
                        {{ $badge['progress'] }} / {{ $badge['total'] }}
                        @if($badge['type'] === 'streak') days @else workdays @endif
                    </p>
                </div>

                {{-- Points --}}
                <div class="shrink-0 text-right">
                    <p class="font-black leading-none" style="font-size:16px; color:#5BBF27;">+{{ $badge['points'] }}</p>
                    <p class="text-xs font-medium mt-0.5" style="color:#999; font-size:9px;">pts</p>
                </div>
            </div>

            {{-- Expanded detail --}}
            <div x-show="selected === '{{ $badgeId }}'" x-cloak
                 class="fade-up px-4 pb-4"
                 style="border-top:1.5px solid {{ $badge['border_color'] }}; background:{{ $badge['bg_color'] }};">

                @if($isStreak)
                    {{-- No-Late 5: 5-box day tracker --}}
                    <div class="flex gap-2 mt-3">
                        @foreach($badge['day_statuses'] as $di => $status)
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <div class="w-full rounded-lg flex items-center justify-center font-bold"
                                     style="height:36px;
                                         {{ $status === 'done' ? 'background:' . $badge['color'] . '; color:#fff;' : '' }}
                                         {{ $status === 'current' ? 'background:#fff; border:1.5px solid ' . $badge['color'] . '; color:' . $badge['color'] . ';' : '' }}
                                         {{ $status === 'future' ? 'background:#F5F5F5; border:1px solid #E0E0E0; color:#bbb;' : '' }}
                                     ">
                                    {{ $status === 'done' ? '✓' : ($status === 'current' ? '…' : '·') }}
                                </div>
                                <p style="font-size:9px; color:#999;">D{{ $di + 1 }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Progress calendar grid --}}
                    @if(count($badge['workday_statuses']) > 0)
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @foreach($badge['workday_statuses'] as $ws)
                                @php
                                    if ($badge['id'] === 'same_day_finisher') {
                                        $isDone = $ws['has_dtr']; // same-day not tracked per-day here; use has_dtr
                                    } else {
                                        $isDone = $ws['has_dtr'];
                                    }
                                @endphp
                                <div class="w-7 h-7 rounded-md flex items-center justify-center text-xs font-bold"
                                     style="{{ $ws['is_future'] ? 'background:#F0F0F0; border:1px solid #E0E0E0; color:#bbb; opacity:.4;' : ($isDone ? 'background:' . $badge['color'] . '; color:#fff;' : ($ws['is_today'] ? 'background:#fff; border:1.5px solid ' . $badge['color'] . '; color:' . $badge['color'] . ';' : 'background:#F0F0F0; border:1px solid #ddd; color:#bbb;')) }}">
                                    {{ $ws['is_future'] ? '○' : ($isDone ? '✓' : ($ws['is_today'] ? '·' : '○')) }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                <p class="text-xs mt-3 leading-relaxed" style="color:#555; line-height:1.6;">{{ $badge['desc'] }}</p>

                <div class="mt-2 rounded-xl p-3" style="background:#fff; border:1px solid {{ $badge['border_color'] }};">
                    <p class="text-xs" style="color:#555; line-height:1.5;">💡 {{ $badge['tip'] }}</p>
                </div>
            </div>
        </div>
        @endforeach

        {{-- ─── How to Earn Points ─── --}}
        <div class="rounded-2xl overflow-hidden mt-1" style="border:1.5px solid #EBEBEB; background:#fff;">
            <div class="px-4 py-3" style="border-bottom:1px solid #EBEBEB;">
                <p class="font-extrabold" style="font-size:15px; color:#111;">How to Earn Points</p>
            </div>
            @php
                $earnRows = [
                    [
                        'On-time time-in',
                        '+' . \App\Services\GamificationService::PTS_ON_TIME,
                        false,
                        'You clocked in on or before your scheduled start time. Earn ' . \App\Services\GamificationService::PTS_ON_TIME . ' points for every qualifying day you arrive on time.',
                    ],
                    [
                        'Same-day DTR filed',
                        '+' . \App\Services\GamificationService::PTS_SAME_DAY,
                        false,
                        'You submitted your time record on the same day you worked. Earn ' . \App\Services\GamificationService::PTS_SAME_DAY . ' points for logging your DTR before midnight of that workday.',
                    ],
                    [
                        'No-Late 5 badge earned',
                        '+' . \App\Services\GamificationService::PTS_BADGE_NO_LATE_5,
                        false,
                        'You completed 5 consecutive workdays without a single late time-in. Earn ' . \App\Services\GamificationService::PTS_BADGE_NO_LATE_5 . ' bonus points each time you earn this streak badge.',
                    ],
                    [
                        'Same-Day Finisher badge earned',
                        '+' . \App\Services\GamificationService::PTS_BADGE_SAME_DAY_FINISHER,
                        false,
                        'You filed every DTR on the same day you worked, for the entire payroll cutoff period. Earn ' . \App\Services\GamificationService::PTS_BADGE_SAME_DAY_FINISHER . ' bonus points when you complete this badge.',
                    ],
                    [
                        'No Absences badge earned',
                        '+' . \App\Services\GamificationService::PTS_BADGE_NO_ABSENCES,
                        false,
                        'You had zero absences throughout the entire payroll cutoff — every scheduled workday had a time-in. Earn ' . \App\Services\GamificationService::PTS_BADGE_NO_ABSENCES . ' bonus points when you complete this badge.',
                    ],
                    [
                        'Late time-in',
                        (string) \App\Services\GamificationService::PTS_PENALTY_LATE,
                        true,
                        'You clocked in after your scheduled start time. You lose ' . abs(\App\Services\GamificationService::PTS_PENALTY_LATE) . ' points for each day you are late.',
                    ],
                    [
                        'Absent (no time-in)',
                        (string) \App\Services\GamificationService::PTS_PENALTY_ABSENT,
                        true,
                        'You had no time-in recorded for a scheduled workday. You lose ' . abs(\App\Services\GamificationService::PTS_PENALTY_ABSENT) . ' points for each absence.',
                    ],
                ];
            @endphp
            @foreach($earnRows as $i => [$action, $pts, $isPenalty, $desc])
                <div class="{{ $i > 0 ? 'border-t' : '' }}" style="{{ $i > 0 ? 'border-color:#EBEBEB;' : '' }}">
                    <div class="flex items-center justify-between px-4 py-3">
                        <button type="button"
                                @click.stop="openTip = openTip === {{ $i }} ? null : {{ $i }}"
                                class="flex items-center gap-1.5 text-left flex-1 min-w-0">
                            <span class="text-sm font-medium" style="color:#111;">{{ $action }}</span>
                            <svg :style="openTip === {{ $i }} ? 'color:{{ $isPenalty ? '#DC2626' : '#5BBF27' }}' : 'color:#C8C8C8'"
                                 style="flex-shrink:0; transition:color .15s;"
                                 xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 16v-4M12 8h.01"/>
                            </svg>
                        </button>
                        <p class="text-sm font-bold shrink-0 ml-3" style="color:{{ $isPenalty ? '#DC2626' : '#5BBF27' }};">{{ $pts }}</p>
                    </div>
                    <div x-show="openTip === {{ $i }}" x-cloak class="px-4 pb-3 -mt-1 fade-up">
                        <p class="text-xs rounded-xl px-3 py-2.5" style="background:#F9FAFB; color:#555; border:1px solid #E5E7EB; line-height:1.6;">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>

    </div>{{-- /px-4 --}}

</div>

</x-staff-layout>
