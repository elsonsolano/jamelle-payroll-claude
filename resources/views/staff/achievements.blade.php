<x-staff-layout title="Achievements" :hide-header="true">

@push('head')
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800,900&display=swap" rel="stylesheet" />
@endpush

@if ($comingSoon)

<style>
@keyframes floatUp {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-8px); }
}
</style>
<script>
function achievementCountdown() {
    return {
        target: {{ $launchTimestamp }},
        h: '00', m: '00', s: '00',
        tick() {
            const diff = Math.max(0, this.target - Date.now());
            this.h = String(Math.floor(diff / 3600000)).padStart(2, '0');
            this.m = String(Math.floor(diff % 3600000 / 60000)).padStart(2, '0');
            this.s = String(Math.floor(diff % 60000 / 1000)).padStart(2, '0');
        },
        init() { this.tick(); setInterval(() => this.tick(), 1000); }
    };
}
</script>
<div x-data="achievementCountdown()"
     style="min-height:100dvh;background:#0d0d18;display:flex;flex-direction:column;
            align-items:center;justify-content:center;padding:40px 24px 60px;
            margin:-1rem -1rem 0;
            font-family:'Plus Jakarta Sans',sans-serif;text-align:center;">

    {{-- Floating icon --}}
    <div style="font-size:64px;margin-bottom:24px;animation:floatUp 3s ease-in-out infinite;">🍦</div>

    {{-- Headline --}}
    <div style="font-size:26px;font-weight:900;color:#fff;line-height:1.2;margin-bottom:10px;
                letter-spacing:-.5px;">
        Leaderboard &amp; Points<br>
        <span style="background:linear-gradient(90deg,#f59e0b,#f97316,#ec4899);
                     -webkit-background-clip:text;-webkit-text-fill-color:transparent;
                     background-clip:text;">
            Go Live Tomorrow
        </span>
    </div>

    {{-- Tagline --}}
    <div style="font-size:13px;color:rgba(255,255,255,.45);font-weight:600;
                letter-spacing:.04em;margin-bottom:36px;line-height:1.6;">
        Earn points · Climb 15 ranks · Win badges · Top the board
    </div>

    {{-- Countdown --}}
    <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:14px;">
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
            <div style="background:#1a1a2e;border:1px solid rgba(255,255,255,.12);
                        border-radius:14px;padding:14px 20px;min-width:72px;
                        box-shadow:0 0 20px rgba(249,115,22,.15);">
                <span x-text="h"
                      style="font-size:36px;font-weight:900;color:#fff;font-variant-numeric:tabular-nums;
                             line-height:1;display:block;"></span>
            </div>
            <span style="font-size:10px;font-weight:700;color:rgba(255,255,255,.3);
                         letter-spacing:.1em;text-transform:uppercase;">hours</span>
        </div>

        <div style="font-size:32px;font-weight:900;color:rgba(255,255,255,.3);
                    padding-bottom:24px;line-height:1;">:</div>

        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
            <div style="background:#1a1a2e;border:1px solid rgba(255,255,255,.12);
                        border-radius:14px;padding:14px 20px;min-width:72px;
                        box-shadow:0 0 20px rgba(249,115,22,.15);">
                <span x-text="m"
                      style="font-size:36px;font-weight:900;color:#fff;font-variant-numeric:tabular-nums;
                             line-height:1;display:block;"></span>
            </div>
            <span style="font-size:10px;font-weight:700;color:rgba(255,255,255,.3);
                         letter-spacing:.1em;text-transform:uppercase;">mins</span>
        </div>

        <div style="font-size:32px;font-weight:900;color:rgba(255,255,255,.3);
                    padding-bottom:24px;line-height:1;">:</div>

        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
            <div style="background:#1a1a2e;border:1px solid rgba(255,255,255,.12);
                        border-radius:14px;padding:14px 20px;min-width:72px;
                        box-shadow:0 0 20px rgba(249,115,22,.15);">
                <span x-text="s"
                      style="font-size:36px;font-weight:900;color:#fff;font-variant-numeric:tabular-nums;
                             line-height:1;display:block;"></span>
            </div>
            <span style="font-size:10px;font-weight:700;color:rgba(255,255,255,.3);
                         letter-spacing:.1em;text-transform:uppercase;">secs</span>
        </div>
    </div>

    {{-- Launch date label --}}
    <div style="font-size:12px;font-weight:700;color:rgba(255,255,255,.25);
                letter-spacing:.08em;text-transform:uppercase;">
        May 1 &nbsp;·&nbsp; 6:00 AM
    </div>

</div>

@else

@push('head')
<style>
*, *::before, *::after { box-sizing: border-box; }
.ach { font-family: 'Plus Jakarta Sans', sans-serif; }

@keyframes floatUp {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-6px); }
}
@keyframes shimmer {
    0%   { background-position: -200% center; }
    100% { background-position:  200% center; }
}
@keyframes sparkle {
    0%, 100% { opacity: .3; transform: scale(.8); }
    50%       { opacity: 1;  transform: scale(1.2); }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.88) translateY(20px); }
    to   { opacity: 1; transform: scale(1)   translateY(0); }
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(40px); }
    to   { opacity: 1; transform: translateY(0); }
}

.ach-mascot    { animation: floatUp 3s ease-in-out infinite; }
.ach-shimmer   { background-size: 200% 100%; animation: shimmer 2.5s linear infinite; }
.ach-fadein    { animation: fadeUp .4s ease both; }
.ach-modal     { animation: slideUp .3s ease; }
.ach-popin     { animation: popIn .3s ease; }

/* Modal overlay — must be in CSS so Alpine x-show can restore it correctly */
.ach-overlay {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.ach-tabs {
    background: #0d0d18;
    display: flex;
    gap: 0;
    padding: 26px 14px 0;
    position: sticky;
    top: 0;
    z-index: 5;
}

.ach-tab {
    align-items: center;
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    flex: 1;
    gap: 6px;
    justify-content: center;
    min-height: 36px;
    padding: 0 0 12px;
    text-align: center;
    transition: all .2s;
}

.ach-tab-icon {
    font-size: 13px;
    line-height: 1;
}

.ach-tab-label {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .04em;
    line-height: 1;
    text-transform: uppercase;
}

/* Dark bottom nav for achievements page only */
nav.fixed {
    background: #0d0d18 !important;
    border-top-color: rgba(255,255,255,0.08) !important;
}
</style>
@endpush

@php
    $avatarColors = [
        ['#5BBF27','#3D8C18'],['#F97316','#c45408'],['#3B82F6','#1d4ed8'],
        ['#A855F7','#7c3aed'],['#06B6D4','#0e7490'],['#F59E0B','#b45309'],
    ];
    $avatarFor = function(string $name) use ($avatarColors): array {
        $parts    = explode(' ', trim($name));
        $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
        $idx      = (ord($initials[0] ?? 'A') + ord($initials[1] ?? 'A')) % 6;
        return ['initials' => $initials ?: '??', 'from' => $avatarColors[$idx][0], 'to' => $avatarColors[$idx][1]];
    };
    $mascotFile = str_pad($rank['number'], 2, '0', STR_PAD_LEFT);
    $hasMascot  = file_exists(public_path("images/rank-mascots/mascot-{$mascotFile}.png"));
    $viewerRankNum = $leaderboard['viewerRank']['rank'] ?? null;
    $viewerBranch  = $leaderboard['viewerRank']['branch'] ?? ($employee->branch?->name ?? '');

    // Split leaderboard into podium (1-3) and rows (4-10)
    $top10  = $leaderboard['top10'];
    $podium = array_values(array_filter($top10, fn($r) => $r['rank'] <= 3));
    $rows   = array_values(array_filter($top10, fn($r) => $r['rank'] > 3));

    // Podium order: 2nd left, 1st centre, 3rd right
    $podiumByPos = [];
    foreach ($podium as $p) { $podiumByPos[$p['rank']] = $p; }
    $podiumOrder = array_filter([
        $podiumByPos[2] ?? null,
        $podiumByPos[1] ?? null,
        $podiumByPos[3] ?? null,
    ]);
    $hasLeaderboard = count($top10) > 0;
    if ($hasLeaderboard && count($podiumOrder) < 3) {
        $rows = $top10;
    }

    $podiumMeta = [
        1 => ['crown'=>'👑','ht'=>72,'from'=>'#FFD700','to'=>'#B8860B','glow'=>'rgba(255,215,0,0.3)','chip_bg'=>'#FFD700','chip_text'=>'#5a3a00','size'=>52,'font'=>22],
        2 => ['crown'=>'🥈','ht'=>54,'from'=>'#C0C0C0','to'=>'#808080','glow'=>'rgba(192,192,192,0.2)','chip_bg'=>'#C0C0C0','chip_text'=>'#111827','size'=>42,'font'=>18],
        3 => ['crown'=>'🥉','ht'=>44,'from'=>'#CD7F32','to'=>'#8B4513','glow'=>'rgba(205,127,50,0.2)','chip_bg'=>'#CD7F32','chip_text'=>'#111827','size'=>42,'font'=>18],
    ];

    $earnRows = [
        ['On-time time-in',              '+' . \App\Services\GamificationService::PTS_ON_TIME,             false],
        ['Same-day DTR filed',           '+' . \App\Services\GamificationService::PTS_SAME_DAY,            false],
        ['Perfect Cutoff bonus',         '+' . \App\Services\GamificationService::PTS_PERFECT_CUTOFF,      false],
        ['No-Late 7 badge earned',       '+' . \App\Services\GamificationService::PTS_BADGE_NO_LATE_7,     false],
        ['Same-Day Finisher badge earned','+' . \App\Services\GamificationService::PTS_BADGE_SAME_DAY_FINISHER, false],
        ['No Absences badge earned',     '+' . \App\Services\GamificationService::PTS_BADGE_NO_ABSENCES,   false],
        ['Late time-in',                 (string) \App\Services\GamificationService::PTS_PENALTY_LATE,     true],
        ['Absent (no time-in)',          (string) \App\Services\GamificationService::PTS_PENALTY_ABSENT,   true],
    ];
@endphp

<script>
function achievementsPage() {
    return {
        tab: 'leaderboard',
        showSearch: false,
        showHowTo: false,
        searchUrl: @json(route('staff.achievements.search')),
        searchQuery: '',
        searchResults: [],
        selectedPlayer: null,
        hasSearched: false,
        searchLoading: false,
        searchError: '',
        searchRequestId: 0,
        async doSearch() {
            const q = this.searchQuery.toLowerCase().trim();
            if (!q) {
                this.searchResults = [];
                this.selectedPlayer = null;
                this.hasSearched = false;
                this.searchError = '';
                return;
            }

            const requestId = ++this.searchRequestId;
            this.searchLoading = true;
            this.searchError = '';
            this.selectedPlayer = null;

            try {
                const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Search failed');
                }

                const data = await response.json();

                if (requestId !== this.searchRequestId) return;

                this.searchResults = data.results || [];
                this.hasSearched = true;
            } catch (error) {
                if (requestId !== this.searchRequestId) return;

                this.searchResults = [];
                this.hasSearched = true;
                this.searchError = 'Search is unavailable right now. Please try again.';
            } finally {
                if (requestId === this.searchRequestId) {
                    this.searchLoading = false;
                }
            }
        },
        selectPlayer(player) {
            this.selectedPlayer = player;
        },
        backToResults() {
            this.selectedPlayer = null;
        },
        closeSearch() {
            this.showSearch     = false;
            this.searchQuery    = '';
            this.searchResults  = [];
            this.selectedPlayer = null;
            this.hasSearched    = false;
            this.searchLoading  = false;
            this.searchError    = '';
        },
    };
}
</script>

<div x-data="achievementsPage()"
     x-init="localStorage.setItem('achievements-seen','1')"
     class="ach pb-20"
     style="background:#0d0d18; min-height:100vh; margin:-1rem -1rem -6rem;">

    {{-- ═══════════════════════════════════════════
         HERO HEADER
    ═══════════════════════════════════════════ --}}
    <div style="background:linear-gradient(175deg,#1a3a0a 0%,#0d1f05 60%,#0d0d18 100%);
                padding:16px 20px 0; position:relative; overflow:hidden;">

        {{-- Glow orbs --}}
        <div style="position:absolute;top:-40px;left:-40px;width:200px;height:200px;border-radius:50%;
                    background:radial-gradient(circle,rgba(91,191,39,.12) 0%,transparent 70%);pointer-events:none;"></div>
        <div style="position:absolute;top:10px;right:-30px;width:160px;height:160px;border-radius:50%;
                    background:radial-gradient(circle,rgba(91,191,39,.07) 0%,transparent 70%);pointer-events:none;"></div>

        {{-- Sparkle dots --}}
        <div style="position:absolute;top:8px;left:80px;width:4px;height:4px;border-radius:50%;
                    background:rgba(91,191,39,.6);animation:sparkle 1.5s ease-in-out infinite;pointer-events:none;"></div>
        <div style="position:absolute;top:30px;left:300px;width:5px;height:5px;border-radius:50%;
                    background:rgba(91,191,39,.6);animation:sparkle 1.9s ease-in-out .3s infinite;pointer-events:none;"></div>
        <div style="position:absolute;top:55px;left:150px;width:3px;height:3px;border-radius:50%;
                    background:rgba(91,191,39,.6);animation:sparkle 1.7s ease-in-out .6s infinite;pointer-events:none;"></div>
        <div style="position:absolute;top:18px;right:60px;width:4px;height:4px;border-radius:50%;
                    background:rgba(91,191,39,.6);animation:sparkle 2.1s ease-in-out .9s infinite;pointer-events:none;"></div>

        {{-- Top row: date + title + bell --}}
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div>
                <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.4);
                            letter-spacing:.1em;text-transform:uppercase;">
                    {{ today()->format('D, M j, Y') }}
                </div>
                <div style="font-size:24px;font-weight:900;color:#fff;
                            line-height:1.2;margin-top:2px;letter-spacing:-.02em;">
                    Achievements
                </div>
            </div>
            <a href="{{ route('staff.notifications.index') }}"
               style="width:38px;height:38px;border-radius:99px;
                      background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"
                          stroke="rgba(255,255,255,.7)" stroke-width="1.8"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

        {{-- Rank card --}}
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(91,191,39,.2);
                    border-radius:20px;padding:16px;margin-bottom:16px;
                    display:flex;align-items:center;gap:14px;">
            {{-- Mascot --}}
            <div style="flex-shrink:0;width:58px;height:58px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                @if($hasMascot)
                    <img src="{{ asset("images/rank-mascots/mascot-{$mascotFile}.png") }}"
                         alt="{{ $rank['name'] }}"
                         class="ach-mascot"
                         style="width:58px;height:58px;object-fit:cover;">
                @else
                    {{-- Empty Cup SVG fallback --}}
                    <svg width="58" height="58" viewBox="0 0 64 64" fill="none" class="ach-mascot">
                        <circle cx="32" cy="36" r="18" fill="#D1FAE5" stroke="#6EE7B7" stroke-width="2"/>
                        <circle cx="26" cy="34" r="2.5" fill="#065F46"/>
                        <circle cx="38" cy="34" r="2.5" fill="#065F46"/>
                        <path d="M26 42 Q32 47 38 42" stroke="#065F46" stroke-width="2" stroke-linecap="round" fill="none"/>
                        <path d="M32 18 Q32 12 38 10" stroke="#34D399" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                        <ellipse cx="38" cy="9" rx="5" ry="3.5" fill="#34D399" transform="rotate(-20 38 9)"/>
                        <path d="M32 18 Q32 12 26 9" stroke="#34D399" stroke-width="2" stroke-linecap="round" fill="none"/>
                        <ellipse cx="26" cy="9" rx="4" ry="3" fill="#6EE7B7" transform="rotate(20 26 9)"/>
                        <circle cx="22" cy="38" r="3" fill="#FCA5A5" opacity=".5"/>
                        <circle cx="42" cy="38" r="3" fill="#FCA5A5" opacity=".5"/>
                    </svg>
                @endif
            </div>

            {{-- Rank info --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:9px;font-weight:800;color:rgba(255,255,255,.35);
                            letter-spacing:.12em;text-transform:uppercase;margin-bottom:4px;">
                    Your Rank
                </div>
                <div style="font-size:18px;font-weight:900;color:#5BBF27;letter-spacing:-.01em;">
                    {{ $rank['name'] }}
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,.45);margin-top:2px;line-height:1.3;">
                    {{ $rank['desc'] }}
                </div>
            </div>

            {{-- Points + rank position --}}
            <div style="text-align:center;flex-shrink:0;">
                <div style="font-size:28px;font-weight:900;color:#fff;line-height:1;">
                    {{ number_format($totalPoints) }}
                </div>
                <div style="font-size:9px;font-weight:700;color:rgba(255,255,255,.35);
                            letter-spacing:.08em;text-transform:uppercase;margin-top:2px;">
                    pts
                </div>
                @if($viewerRankNum)
                    <div style="font-size:9px;font-weight:700;color:rgba(255,255,255,.25);margin-top:2px;">
                        #{{ $viewerRankNum }}
                    </div>
                @endif
            </div>
        </div>

        {{-- XP bar --}}
        <div style="background:rgba(255,255,255,.04);border-radius:16px;padding:14px 20px;margin-bottom:0;">
            @if(!$rank['is_max'])
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span style="font-size:11px;font-weight:700;color:rgba(255,255,255,.5);
                                 letter-spacing:.06em;text-transform:uppercase;">
                        {{ $rank['name'] }}
                    </span>
                    <span style="font-size:11px;font-weight:700;color:rgba(255,255,255,.35);letter-spacing:.04em;">
                        {{ number_format($rank['points_to_next']) }} pts to {{ $rank['next_name'] }} →
                    </span>
                </div>
                <div style="height:8px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden;">
                    <div class="ach-shimmer"
                         style="height:100%;width:{{ $rank['progress_pct'] }}%;
                                background:linear-gradient(90deg,#3D8C18,#5BBF27,#a3f060);
                                border-radius:99px;">
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:6px;">
                    <span style="font-size:10px;color:rgba(255,255,255,.3);font-weight:600;">
                        {{ number_format($rank['min_points']) }} pts
                    </span>
                    <span style="font-size:10px;color:rgba(255,255,255,.3);font-weight:600;">
                        {{ number_format($rank['next_min']) }} pts
                    </span>
                </div>
            @else
                <div style="text-align:center;font-size:13px;font-weight:800;color:#5BBF27;">
                    🏆 Max rank achieved!
                </div>
            @endif
        </div>

        {{-- Badges earned strip --}}
        <div style="display:flex;align-items:center;gap:8px;padding:12px 0 16px;">
            <div style="width:7px;height:7px;border-radius:99px;background:rgba(255,255,255,.15);flex-shrink:0;"></div>
            <span style="font-size:11px;color:rgba(255,255,255,.4);font-weight:700;">
                {{ $totalBadgesEarned }} {{ $totalBadgesEarned === 1 ? 'badge' : 'badges' }} earned (all time)
            </span>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         TAB BAR
    ═══════════════════════════════════════════ --}}
    <div class="ach-tabs">
        <button @click="tab='leaderboard'"
                class="ach-tab"
                :style="tab==='leaderboard'
                    ? 'color:#fff;border-bottom:2px solid #5BBF27;'
                    : 'color:rgba(255,255,255,.3);border-bottom:2px solid transparent;'">
            <span class="ach-tab-icon">🏆</span>
            <span class="ach-tab-label">Leaderboard</span>
        </button>
        <button @click="tab='badges'"
                class="ach-tab"
                :style="tab==='badges'
                    ? 'color:#fff;border-bottom:2px solid #5BBF27;'
                    : 'color:rgba(255,255,255,.3);border-bottom:2px solid transparent;'">
            <span class="ach-tab-icon">🎖</span>
            <span class="ach-tab-label">Badges</span>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════
         LEADERBOARD TAB
    ═══════════════════════════════════════════ --}}
    <div x-show="tab==='leaderboard'" x-cloak>

        {{-- Header + Search button --}}
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:16px 20px 0;
                    background:#0d0d18;">
            <div>
                <div style="font-size:13px;font-weight:800;color:#fff;">Top 10 Staff</div>
                <div style="font-size:10px;color:rgba(255,255,255,.3);font-weight:600;margin-top:1px;">
                    All-time points · {{ $viewerBranch }}
                </div>
            </div>
            <button @click="showSearch=true"
                    style="display:flex;align-items:center;gap:6px;
                           padding:8px 14px;border-radius:99px;
                           background:linear-gradient(135deg,rgba(61,140,24,.2),rgba(91,191,39,.13));
                           border:1px solid rgba(91,191,39,.25);
                           color:#5BBF27;font-size:11px;font-weight:800;
                           letter-spacing:.04em;
                           box-shadow:0 0 12px rgba(91,191,39,.2);cursor:pointer;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                    <circle cx="11" cy="11" r="8" stroke="#5BBF27" stroke-width="2"/>
                    <path d="M21 21l-4.35-4.35" stroke="#5BBF27" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Search
            </button>
        </div>

        {{-- Podium --}}
        @if(!$hasLeaderboard)
        <div style="background:#0d0d18;padding:18px 20px 0;">
            <div style="position:relative;height:154px;margin:0 auto;max-width:320px;">
                <div style="position:absolute;left:50%;top:0;transform:translateX(-50%);
                            color:rgba(245,158,11,.26);font-size:18px;line-height:1;">♛</div>

                <div style="position:absolute;left:50%;top:30px;transform:translateX(-50%);
                            width:54px;height:54px;border-radius:50%;
                            border:2px dashed rgba(245,158,11,.32);
                            background:rgba(245,158,11,.04);
                            display:flex;align-items:center;justify-content:center;
                            color:rgba(255,255,255,.08);font-size:18px;font-weight:900;">?</div>
                <div style="position:absolute;left:50%;bottom:0;transform:translateX(-50%);
                            width:90px;height:72px;border-radius:8px 8px 0 0;
                            background:linear-gradient(180deg,rgba(245,158,11,.13),rgba(245,158,11,.035));
                            border:1px solid rgba(245,158,11,.12);border-bottom:none;
                            display:flex;align-items:flex-start;justify-content:center;padding-top:24px;">
                    <span style="font-size:23px;font-weight:900;color:rgba(255,255,255,.08);">#1</span>
                </div>

                <div style="position:absolute;left:22px;top:54px;width:46px;height:46px;border-radius:50%;
                            border:2px dashed rgba(148,163,184,.2);
                            background:rgba(255,255,255,.025);
                            display:flex;align-items:center;justify-content:center;
                            color:rgba(255,255,255,.07);font-size:15px;font-weight:900;">?</div>
                <div style="position:absolute;left:0;bottom:0;width:88px;height:54px;border-radius:8px 8px 0 0;
                            background:linear-gradient(180deg,rgba(148,163,184,.09),rgba(148,163,184,.025));
                            border:1px solid rgba(148,163,184,.08);border-bottom:none;
                            display:flex;align-items:flex-start;justify-content:center;padding-top:20px;">
                    <span style="font-size:18px;font-weight:900;color:rgba(255,255,255,.07);">#2</span>
                </div>

                <div style="position:absolute;right:22px;top:62px;width:46px;height:46px;border-radius:50%;
                            border:2px dashed rgba(180,83,9,.18);
                            background:rgba(180,83,9,.025);
                            display:flex;align-items:center;justify-content:center;
                            color:rgba(255,255,255,.07);font-size:15px;font-weight:900;">?</div>
                <div style="position:absolute;right:0;bottom:0;width:88px;height:44px;border-radius:8px 8px 0 0;
                            background:linear-gradient(180deg,rgba(180,83,9,.08),rgba(180,83,9,.025));
                            border:1px solid rgba(180,83,9,.07);border-bottom:none;
                            display:flex;align-items:flex-start;justify-content:center;padding-top:15px;">
                    <span style="font-size:18px;font-weight:900;color:rgba(255,255,255,.07);">#3</span>
                </div>
            </div>

            <div style="padding:30px 10px 26px;text-align:center;">
                <div style="width:68px;height:68px;border-radius:50%;margin:0 auto 18px;
                            background:rgba(234,179,8,.035);
                            border:1px solid rgba(234,179,8,.18);
                            display:flex;align-items:center;justify-content:center;
                            box-shadow:0 0 24px rgba(234,179,8,.08);">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4z"
                              stroke="rgba(234,179,8,.68)" stroke-width="1.7"
                              stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 7H4v1a4 4 0 004 4M17 7h3v1a4 4 0 01-4 4"
                              stroke="rgba(234,179,8,.68)" stroke-width="1.7"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div style="font-size:18px;font-weight:900;color:#fff;letter-spacing:-.01em;">
                    No Champions Yet
                </div>
                <div style="font-size:13px;color:rgba(148,163,184,.78);font-weight:600;
                            line-height:1.55;max-width:250px;margin:10px auto 0;">
                    The leaderboard is waiting for its first hero. Start earning points and claim your spot!
                </div>
            </div>
        </div>
        @elseif(count($podiumOrder) >= 3)
        <div style="background:#0d0d18;padding-bottom:0;">
            <div style="display:flex;align-items:flex-end;gap:4px;padding:16px 20px 0;">
                @foreach($podiumOrder as $person)
                @php
                    $pos  = $person['rank'];
                    $pm   = $podiumMeta[$pos];
                    $av   = $avatarFor($person['name']);
                    $firstName = explode(' ', $person['name'])[0];
                @endphp
                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;">
                    {{-- Crown --}}
                    <div style="font-size:{{ $pos===1?18:14 }}px;line-height:1;">{{ $pm['crown'] }}</div>

                    {{-- Avatar --}}
                    <div style="position:relative;">
                        @if($person['profile_photo_url'] ?? null)
                            <img src="{{ $person['profile_photo_url'] }}" alt="{{ $person['name'] }}"
                                 style="width:{{ $pm['size'] }}px;height:{{ $pm['size'] }}px;border-radius:50%;object-fit:cover;flex-shrink:0;
                                        {{ $pos===1 ? 'box-shadow:0 0 0 2px #0d0d18,0 0 0 4px '.$pm['from'].'80,0 0 16px '.$pm['from'].'40;' : '' }}">
                        @else
                            <div style="width:{{ $pm['size'] }}px;height:{{ $pm['size'] }}px;border-radius:50%;
                                        background:linear-gradient(135deg,{{ $av['from'] }},{{ $av['to'] }});
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:{{ round($pm['size']*.32) }}px;font-weight:800;color:#fff;
                                        letter-spacing:.02em;
                                        {{ $pos===1 ? 'box-shadow:0 0 0 2px #0d0d18,0 0 0 4px '.$pm['from'].'80,0 0 16px '.$pm['from'].'40;' : '' }}">
                                {{ $av['initials'] }}
                            </div>
                        @endif
                        {{-- Points chip --}}
                        <div style="position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);
                                    background:{{ $pm['chip_bg'] }};color:{{ $pm['chip_text'] }};
                                    font-size:10px;font-weight:900;padding:2px 7px;border-radius:99px;
                                    min-width:25px;text-align:center;
                                    white-space:nowrap;letter-spacing:0;
                                    border:2px solid #0d0d18;
                                    box-shadow:0 2px 8px rgba(0,0,0,.28);">
                            {{ number_format($person['points']) }}
                        </div>
                    </div>

                    {{-- Name --}}
                    <div style="margin-top:10px;text-align:center;">
                        <div style="font-size:10px;font-weight:800;color:#fff;
                                    letter-spacing:.01em;line-height:1.3;max-width:72px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $firstName }}
                        </div>
                        <div style="font-size:9px;color:rgba(255,255,255,.35);font-weight:600;margin-top:2px;
                                    max-width:72px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $person['rank_name'] }}
                        </div>
                    </div>

                    {{-- Podium bar --}}
                    <div style="width:100%;height:{{ $pm['ht'] }}px;
                                background:linear-gradient(180deg,{{ $pm['from'] }},{{ $pm['to'] }});
                                border-radius:8px 8px 0 0;
                                display:flex;align-items:flex-start;justify-content:center;
                                padding-top:10px;
                                box-shadow:0 -4px 20px {{ $pm['glow'] }};">
                        <span style="display:inline-flex;align-items:center;justify-content:center;
                                     min-width:36px;height:28px;padding:0 9px;border-radius:999px;
                                     background:rgba(13,13,24,.78);
                                     border:1px solid rgba(255,255,255,.28);
                                     box-shadow:0 2px 8px rgba(0,0,0,.22);
                                     font-size:{{ $pos===1 ? 16 : 14 }}px;font-weight:900;
                                     color:#fff;line-height:1;letter-spacing:0;">
                            #{{ $pos }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Rows #4-10 --}}
        <div style="padding:{{ $hasLeaderboard ? '4px 12px 8px' : '0 6px 8px' }};display:flex;flex-direction:column;gap:{{ $hasLeaderboard ? '2px' : '10px' }};">
            @unless($hasLeaderboard)
                @for($i = 0; $i < 3; $i++)
                <div style="height:58px;border-radius:10px;
                            background:rgba(255,255,255,{{ $i === 0 ? '.026' : ($i === 1 ? '.02' : '.015') }});
                            border:1px solid rgba(255,255,255,{{ $i === 0 ? '.045' : ($i === 1 ? '.035' : '.025') }});
                            display:flex;align-items:center;gap:12px;padding:0 14px;opacity:{{ 1 - ($i * .18) }};">
                    <div style="width:22px;height:10px;border-radius:99px;background:rgba(255,255,255,.045);"></div>
                    <div style="width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.045);"></div>
                    <div style="flex:1;min-width:0;">
                        <div style="width:{{ [112, 92, 68][$i] }}px;max-width:80%;height:8px;border-radius:99px;background:rgba(255,255,255,.045);"></div>
                        <div style="width:{{ [76, 62, 46][$i] }}px;max-width:60%;height:6px;border-radius:99px;background:rgba(255,255,255,.03);margin-top:7px;"></div>
                    </div>
                    <div style="width:32px;height:10px;border-radius:99px;background:rgba(255,255,255,.04);"></div>
                </div>
                @endfor
            @endunless
            @foreach($rows as $i => $row)
            @php $av = $avatarFor($row['name']); @endphp
            <div class="ach-fadein"
                 style="display:flex;align-items:center;gap:12px;padding:10px 16px;
                        border-radius:12px;
                        {{ $row['is_viewer'] ? 'background:rgba(91,191,39,.08);border:1px solid rgba(91,191,39,.25);' : 'background:transparent;border:1px solid transparent;' }}
                        animation-delay:{{ $i * 0.05 }}s;">
                <span style="font-size:12px;font-weight:800;width:22px;text-align:center;flex-shrink:0;
                             color:{{ $row['is_viewer'] ? '#5BBF27' : 'rgba(255,255,255,.3)' }};">
                    #{{ $row['rank'] }}
                </span>
                @if($row['profile_photo_url'] ?? null)
                    <img src="{{ $row['profile_photo_url'] }}" alt="{{ $row['name'] }}"
                         style="width:36px;height:36px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                @else
                    <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;
                                background:linear-gradient(135deg,{{ $av['from'] }},{{ $av['to'] }});
                                display:flex;align-items:center;justify-content:center;
                                font-size:12px;font-weight:800;color:#fff;letter-spacing:.02em;">
                        {{ $av['initials'] }}
                    </div>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13px;font-weight:700;color:#fff;
                                     white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $row['name'] }}
                        </span>
                        @if($row['is_viewer'])
                            <span style="font-size:9px;font-weight:800;background:#5BBF27;color:#fff;
                                         padding:1px 6px;border-radius:99px;letter-spacing:.04em;flex-shrink:0;">
                                YOU
                            </span>
                        @endif
                    </div>
                    <div style="font-size:10px;color:rgba(255,255,255,.35);font-weight:600;margin-top:1px;">
                        {{ $row['rank_name'] }} · {{ $row['branch'] }}
                    </div>
                </div>
                <div style="flex-shrink:0;text-align:right;">
                    <span style="font-size:14px;font-weight:800;
                                 color:{{ $row['is_viewer'] ? '#5BBF27' : 'rgba(255,255,255,.7)' }};">
                        {{ number_format($row['points']) }}
                    </span>
                    <span style="font-size:9px;font-weight:600;color:rgba(255,255,255,.35);"> pts</span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Viewer row (if not in top 10) --}}
        @if(($leaderboard['viewerRank'] ?? null) && !$leaderboard['viewerInTop10'])
        @php $vr = $leaderboard['viewerRank']; $av = $avatarFor($vr['name']); @endphp
        <div style="height:1px;background:rgba(255,255,255,.05);margin:4px 20px;"></div>
        <div style="padding:2px 20px 4px;">
            <p style="font-size:11px;font-weight:700;color:rgba(255,255,255,.3);
                      letter-spacing:.06em;text-transform:uppercase;">Your rank</p>
        </div>
        <div style="padding:4px 12px 8px;">
            <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;
                        border-radius:12px;
                        background:rgba(91,191,39,.08);border:1px solid rgba(91,191,39,.25);">
                <span style="font-size:12px;font-weight:800;width:22px;text-align:center;flex-shrink:0;color:#5BBF27;">
                    #{{ $vr['rank'] }}
                </span>
                @if($vr['profile_photo_url'] ?? null)
                    <img src="{{ $vr['profile_photo_url'] }}" alt="{{ $vr['name'] }}"
                         style="width:36px;height:36px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                @else
                    <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;
                                background:linear-gradient(135deg,{{ $av['from'] }},{{ $av['to'] }});
                                display:flex;align-items:center;justify-content:center;
                                font-size:12px;font-weight:800;color:#fff;letter-spacing:.02em;">
                        {{ $av['initials'] }}
                    </div>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13px;font-weight:700;color:#fff;
                                     white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $vr['name'] }}
                        </span>
                        <span style="font-size:9px;font-weight:800;background:#5BBF27;color:#fff;
                                     padding:1px 6px;border-radius:99px;letter-spacing:.04em;flex-shrink:0;">
                            YOU
                        </span>
                    </div>
                    <div style="font-size:10px;color:rgba(255,255,255,.35);font-weight:600;margin-top:1px;">
                        {{ $vr['rank_name'] }} · {{ $vr['branch'] }}
                    </div>
                </div>
                <div style="flex-shrink:0;text-align:right;">
                    <span style="font-size:14px;font-weight:800;color:#5BBF27;">
                        {{ number_format($vr['points']) }}
                    </span>
                    <span style="font-size:9px;font-weight:600;color:rgba(255,255,255,.35);"> pts</span>
                </div>
            </div>
        </div>
        @endif

        {{-- How to Earn button --}}
        <div style="padding:12px 20px 40px;">
            <button @click="showHowTo=true"
                    style="width:100%;padding:14px;
                           background:rgba(255,255,255,.04);
                           border:1px solid rgba(255,255,255,.1);
                           border-radius:14px;
                           display:flex;align-items:center;justify-content:center;gap:8px;
                           color:rgba(255,255,255,.6);font-size:13px;font-weight:700;
                           cursor:pointer;">
                <span style="font-size:16px;">⚡</span>
                How to Earn Points
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="M9 18l6-6-6-6" stroke="rgba(255,255,255,.4)" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         BADGES TAB
    ═══════════════════════════════════════════ --}}
    <div x-show="tab==='badges'" x-cloak style="padding:20px 16px 32px;display:flex;flex-direction:column;gap:12px;">

        {{-- Cutoff label --}}
        @if($cutoff)
        <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.3);
                    letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
            Current Cutoff · {{ $cutoff->start_date->format('M j') }}–{{ $cutoff->end_date->format('M j') }}
        </div>
        @endif

        {{-- Badge cards --}}
        @foreach($badges as $badge)
        @php
            $pct = $badge['total'] > 0 ? min(100, round($badge['progress'] / $badge['total'] * 100)) : 0;
            $tag = $badge['type'] === 'streak' ? 'Streak' : 'Progress';
        @endphp
        <div class="ach-fadein"
             style="background:linear-gradient(135deg,{{ $badge['bg_color'] }},#0d0d18);
                    border:1px solid {{ $badge['color'] }}30;
                    border-radius:16px;padding:14px 16px;">
            <div style="display:flex;align-items:flex-start;gap:12px;">
                {{-- Icon box --}}
                <div style="width:44px;height:44px;border-radius:12px;flex-shrink:0;
                            background:{{ $badge['color'] }}18;
                            border:1.5px solid {{ $badge['color'] }}40;
                            display:flex;align-items:center;justify-content:center;
                            color:#fff;font-size:20px;font-weight:900;
                            text-shadow:0 0 8px rgba(255,255,255,.35);
                            box-shadow:0 0 12px {{ $badge['color'] }}20;">
                    {{ $badge['icon'] }}
                </div>
                <div style="flex:1;min-width:0;">
                    {{-- Name + tag --}}
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;">
                        <span style="font-size:13px;font-weight:800;color:#fff;">{{ $badge['name'] }}</span>
                        <span style="font-size:9px;font-weight:700;padding:2px 7px;border-radius:99px;
                                     background:{{ $badge['color'] }}22;color:{{ $badge['color'] }};
                                     letter-spacing:.05em;text-transform:uppercase;">
                            {{ $tag }}
                        </span>
                        @if($badge['earned'])
                            <span style="font-size:9px;font-weight:800;padding:2px 7px;border-radius:99px;
                                         background:#5BBF2730;color:#5BBF27;letter-spacing:.04em;
                                         text-transform:uppercase;">✓ Earned</span>
                        @endif
                    </div>
                    {{-- Description --}}
                    <div style="font-size:11px;color:rgba(255,255,255,.4);font-weight:500;
                                margin-bottom:10px;line-height:1.4;">
                        {{ $badge['tagline'] }}
                    </div>
                    {{-- Progress bar --}}
                    <div style="height:6px;background:rgba(255,255,255,.07);border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;
                                    background:linear-gradient(90deg,{{ $badge['color'] }}aa,{{ $badge['color'] }});
                                    border-radius:99px;transition:width .6s ease;">
                        </div>
                    </div>
                    {{-- Progress text + points --}}
                    <div style="display:flex;justify-content:space-between;margin-top:5px;">
                        <span style="font-size:10px;color:rgba(255,255,255,.3);font-weight:600;">
                            {{ $badge['progress'] }}/{{ $badge['total'] }} {{ $badge['type']==='streak' ? 'days' : 'workdays' }}
                        </span>
                        <span style="font-size:10px;font-weight:800;color:{{ $badge['color'] }};">
                            +{{ $badge['points'] }} pts
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        {{-- How to Earn button (green tint on badges tab) --}}
        <button @click="showHowTo=true"
                style="margin-top:4px;width:100%;padding:14px;
                       background:linear-gradient(135deg,rgba(91,191,39,.08),rgba(91,191,39,.04));
                       border:1px solid rgba(91,191,39,.2);
                       border-radius:14px;
                       display:flex;align-items:center;justify-content:center;gap:8px;
                       color:#5BBF27;font-size:13px;font-weight:700;
                       cursor:pointer;">
            <span style="font-size:16px;">⚡</span>
            How to Earn Points
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                <path d="M9 18l6-6-6-6" stroke="#5BBF27" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════
         SEARCH MODAL
    ═══════════════════════════════════════════ --}}
    <div x-show="showSearch" x-cloak
         class="ach-overlay"
         style="position:fixed;inset:0;background:rgba(0,0,0,.7);
                z-index:50;backdrop-filter:blur(4px);"
         @click="closeSearch()">
        <div class="ach-modal"
             style="background:#13131f;border-radius:20px 20px 0 0;
                    padding:8px 12px 36px;
                    border:1px solid rgba(255,255,255,.07);border-bottom:none;
                    max-height:80vh;overflow-y:auto;"
             @click.stop>
            {{-- Handle --}}
            <div style="width:36px;height:4px;background:rgba(255,255,255,.15);
                        border-radius:99px;margin:8px auto 20px;"></div>
            <div x-show="!selectedPlayer" x-cloak>
                <div style="font-size:16px;font-weight:800;color:#fff;margin-bottom:4px;">Search Player</div>
                <div style="font-size:12px;color:rgba(255,255,255,.4);margin-bottom:16px;">
                    Find someone's rank and points
                </div>

                {{-- Input row --}}
                <div style="display:flex;gap:8px;margin-bottom:18px;">
                    <input x-model="searchQuery"
                           @input.debounce.450ms="doSearch()"
                           @keydown.enter="doSearch()"
                           type="text"
                           placeholder="Type a name..."
                           style="flex:1;min-width:0;padding:12px 16px;
                                  background:rgba(255,255,255,.06);
                                  border:1px solid rgba(255,255,255,.12);
                                  border-radius:12px;color:#fff;font-size:14px;
                                  font-family:'Plus Jakarta Sans',sans-serif;outline:none;">
                    <button @click="doSearch()"
                            :disabled="searchLoading"
                            style="padding:12px 18px;border-radius:12px;
                                   background:linear-gradient(135deg,#3D8C18,#5BBF27);
                                   color:#fff;font-size:13px;font-weight:800;
                                   box-shadow:0 4px 16px rgba(91,191,39,.4);
                                   cursor:pointer;white-space:nowrap;border:none;">
                        <span x-text="searchLoading ? 'Searching' : 'Search'"></span>
                    </button>
                </div>

                <div x-show="searchLoading" x-cloak
                     style="font-size:12px;color:rgba(255,255,255,.35);font-weight:700;margin-bottom:10px;">
                    Searching active staff...
                </div>

                <div x-show="searchError" x-cloak
                     style="font-size:12px;color:#ef4444;font-weight:700;margin-bottom:10px;"
                     x-text="searchError"></div>

                <div x-show="hasSearched && !searchLoading && !searchError && searchResults.length > 0" x-cloak
                     style="font-size:11px;font-weight:800;color:rgba(255,255,255,.35);
                            letter-spacing:.06em;text-transform:uppercase;margin-bottom:10px;">
                    <span x-text="searchResults.length"></span> players found
                </div>

                {{-- No result --}}
                <div x-show="hasSearched && !searchLoading && !searchError && searchResults.length === 0" x-cloak
                     style="text-align:center;padding:22px 0;
                            color:rgba(255,255,255,.35);font-size:13px;">
                    No player found matching "<span x-text="searchQuery"></span>"
                </div>

                {{-- Result list --}}
                <div x-show="searchResults.length > 0" x-cloak
                     style="display:flex;flex-direction:column;gap:8px;">
                    <template x-for="player in searchResults" :key="player.employee_id">
                        <button type="button"
                                @click="selectPlayer(player)"
                                style="width:100%;display:flex;align-items:center;gap:12px;
                                       padding:10px 12px;border-radius:14px;
                                       background:rgba(255,255,255,.045);
                                       border:1px solid rgba(255,255,255,.08);
                                       color:inherit;text-align:left;cursor:pointer;">
                            <template x-if="player.profile_photo_url">
                                <img :src="player.profile_photo_url" :alt="player.name"
                                     style="width:42px;height:42px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                            </template>
                            <template x-if="!player.profile_photo_url">
                                <div :style="`width:42px;height:42px;border-radius:50%;flex-shrink:0;
                                              background:linear-gradient(135deg,${avatarGradient(player.name)[0]},${avatarGradient(player.name)[1]});
                                              display:flex;align-items:center;justify-content:center;
                                              font-size:13px;font-weight:900;color:#fff;letter-spacing:.02em;`"
                                     x-text="avatarInitials(player.name)">
                                </div>
                            </template>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:800;color:#fff;line-height:1.25;
                                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                     x-text="player.name"></div>
                                <div style="font-size:10px;color:rgba(255,255,255,.35);font-weight:600;margin-top:2px;
                                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                     x-text="`${player.rank_name} · ${player.branch}`"></div>
                            </div>
                            <div style="flex-shrink:0;text-align:right;">
                                <div>
                                    <span style="font-size:14px;font-weight:900;color:#5BBF27;"
                                          x-text="Number(player.points || 0).toLocaleString()"></span>
                                    <span style="font-size:9px;font-weight:700;color:rgba(255,255,255,.35);"> pts</span>
                                </div>
                                <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,.3);margin-top:1px;"
                                     x-text="player.rank ? `#${player.rank}` : '—'"></div>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;">
                                <path d="M9 18l6-6-6-6" stroke="rgba(255,255,255,.25)" stroke-width="2"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Selected player --}}
            <div x-show="selectedPlayer" x-cloak class="ach-popin">
                <button type="button" @click="backToResults()"
                        style="display:flex;align-items:center;gap:6px;margin-bottom:16px;
                               border:none;background:none;color:rgba(255,255,255,.45);
                               font-size:13px;font-weight:800;cursor:pointer;padding:0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to results
                </button>

                <div style="border-radius:16px;overflow:hidden;
                            background:rgba(255,255,255,.025);
                            border:1px solid rgba(255,255,255,.08);">
                    <div style="position:relative;background:#113f05;padding:22px 18px 28px;text-align:center;">
                        <div style="position:absolute;top:14px;right:14px;
                                    background:rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.14);
                                    border-radius:99px;padding:4px 10px;
                                    color:#5BBF27;font-size:11px;font-weight:900;"
                             x-text="`#${selectedPlayer?.rank || '?'}`"></div>
                        <template x-if="selectedPlayer?.profile_photo_url">
                            <img :src="selectedPlayer.profile_photo_url" :alt="selectedPlayer.name"
                                 style="width:78px;height:78px;border-radius:50%;margin:0 auto 12px;
                                        object-fit:cover;display:block;
                                        border:3px solid #0d0d18;
                                        box-shadow:0 0 0 2px rgba(91,191,39,.6),0 8px 24px rgba(0,0,0,.35);">
                        </template>
                        <template x-if="!selectedPlayer?.profile_photo_url">
                            <div :style="`width:78px;height:78px;border-radius:50%;margin:0 auto 12px;
                                          background:linear-gradient(135deg,${avatarGradient(selectedPlayer?.name||'')[0]},${avatarGradient(selectedPlayer?.name||'')[1]});
                                          display:flex;align-items:center;justify-content:center;
                                          font-size:24px;font-weight:900;color:#fff;
                                          border:3px solid #0d0d18;
                                          box-shadow:0 0 0 2px ${avatarGradient(selectedPlayer?.name||'')[0]},0 8px 24px rgba(0,0,0,.35);`"
                                 x-text="avatarInitials(selectedPlayer?.name || '')"></div>
                        </template>
                        <div style="font-size:18px;font-weight:900;color:#fff;line-height:1.2;"
                             x-text="selectedPlayer?.name"></div>
                        <div style="display:inline-flex;align-items:center;gap:5px;margin-top:8px;
                                    padding:4px 10px;border-radius:99px;
                                    background:rgba(255,255,255,.12);
                                    color:rgba(255,255,255,.65);font-size:11px;font-weight:800;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a7 7 0 00-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                            </svg>
                            <span x-text="selectedPlayer?.branch"></span>
                        </div>
                    </div>
                    <div style="margin:-18px 16px 20px;position:relative;
                                display:grid;grid-template-columns:1fr 1fr;
                                background:#1b1a2e;border:1px solid rgba(255,255,255,.08);
                                border-radius:14px;overflow:hidden;">
                        <div style="padding:16px 10px;text-align:center;">
                            <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,.35);
                                        letter-spacing:.08em;text-transform:uppercase;">Points</div>
                            <div style="font-size:26px;font-weight:900;color:#5BBF27;line-height:1.05;"
                                 x-text="Number(selectedPlayer?.points || 0).toLocaleString()"></div>
                            <div style="font-size:9px;font-weight:800;color:rgba(255,255,255,.3);
                                        letter-spacing:.08em;text-transform:uppercase;">All-time</div>
                        </div>
                        <div style="width:1px;background:rgba(255,255,255,.08);position:absolute;top:16px;bottom:16px;left:50%;"></div>
                        <div style="padding:16px 10px;text-align:center;">
                            <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,.35);
                                        letter-spacing:.08em;text-transform:uppercase;">Rank</div>
                            <div style="font-size:14px;font-weight:900;color:#A78BFA;margin-top:5px;line-height:1.2;"
                                 x-text="selectedPlayer?.rank_name"></div>
                            <div style="font-size:9px;font-weight:800;color:rgba(255,255,255,.3);
                                        letter-spacing:.08em;text-transform:uppercase;margin-top:2px;">Title</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         HOW TO EARN POINTS MODAL
    ═══════════════════════════════════════════ --}}
    <div x-show="showHowTo" x-cloak
         class="ach-overlay"
         style="position:fixed;inset:0;background:rgba(0,0,0,.7);
                z-index:50;backdrop-filter:blur(4px);"
         @click="showHowTo=false">
        <div class="ach-modal"
             style="background:#13131f;border-radius:20px 20px 0 0;
                    padding:8px 20px 36px;
                    border:1px solid rgba(255,255,255,.07);border-bottom:none;
                    max-height:70vh;overflow-y:auto;"
             @click.stop>
            <div style="width:36px;height:4px;background:rgba(255,255,255,.15);
                        border-radius:99px;margin:8px auto 20px;"></div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                <span style="font-size:22px;">⚡</span>
                <div>
                    <div style="font-size:16px;font-weight:800;color:#fff;">How to Earn Points</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.4);">Actions that affect your score</div>
                </div>
            </div>
            <div style="height:1px;background:rgba(255,255,255,.07);margin:16px 0;"></div>
            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach($earnRows as $i => [$action, $pts, $isPenalty])
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:11px 14px;border-radius:12px;
                            {{ $i % 2 === 0 ? 'background:rgba(255,255,255,.03);' : '' }}">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:6px;height:6px;border-radius:99px;flex-shrink:0;
                                    background:{{ $isPenalty ? '#ef4444' : '#5BBF27' }};
                                    box-shadow:0 0 6px {{ $isPenalty ? '#ef4444' : '#5BBF27' }};"></div>
                        <span style="font-size:13px;color:rgba(255,255,255,.8);font-weight:600;">
                            {{ $action }}
                        </span>
                    </div>
                    <span style="font-size:14px;font-weight:800;letter-spacing:.02em;
                                 color:{{ $isPenalty ? '#ef4444' : '#5BBF27' }};">
                        {{ $pts }}
                    </span>
                </div>
                @endforeach
            </div>
            <div style="margin-top:16px;padding:12px 14px;
                        background:rgba(91,191,39,.06);border-radius:12px;
                        border:1px solid rgba(91,191,39,.15);">
                <div style="font-size:11px;color:rgba(255,255,255,.5);line-height:1.6;">
                    💡 <strong style="color:rgba(255,255,255,.7);">Tip:</strong>
                    Keep your streak alive for bonus multipliers — 5 days gives +20%, 14 days gives +50%!
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const _avatarPalette = [
    ['#5BBF27','#3D8C18'],['#F97316','#c45408'],['#3B82F6','#1d4ed8'],
    ['#A855F7','#7c3aed'],['#06B6D4','#0e7490'],['#F59E0B','#b45309'],
];
function avatarInitials(name) {
    const parts = name.trim().split(' ');
    return ((parts[0]?.[0] || '') + (parts[1]?.[0] || '')).toUpperCase() || '??';
}
function avatarGradient(name) {
    const ini = avatarInitials(name);
    const idx = ((ini.charCodeAt(0) || 0) + (ini.charCodeAt(1) || 0)) % 6;
    return _avatarPalette[idx];
}
</script>

@endif

</x-staff-layout>
