<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#22c55e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Jamelle Payroll">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=source-serif-4:ital,wght@1,400;1,500&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900">

{{-- Mobile-first layout: top bar + content + bottom nav --}}
<div class="min-h-screen flex flex-col max-w-lg mx-auto bg-white shadow-lg">

    {{-- Top Bar --}}
    @if(!$hideHeader)
    <header class="text-white px-4 py-3 flex items-center gap-3 sticky top-0 z-10 shadow" style="background:#6ea830;">
        <div class="flex-1">
            <h1 class="text-base font-semibold leading-tight">{{ $title ?? 'Dashboard' }}</h1>
            <p class="text-xs text-green-100 leading-tight">{{ Auth::user()->employee->branch->name ?? '' }}</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Notification bell --}}
            <a href="{{ route('staff.notifications.index') }}" class="relative p-1">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @php $unread = Auth::user()->unreadNotifications()->count(); @endphp
                @if($unread > 0)
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                        {{ $unread > 9 ? '9+' : $unread }}
                    </span>
                @endif
            </a>
            <a href="{{ route('staff.profile') }}"
               class="w-8 h-8 rounded-full bg-white/20 border border-white/40 flex items-center justify-center overflow-hidden"
               aria-label="Profile">
                @if(Auth::user()->profile_photo_url)
                    <img src="{{ Auth::user()->profile_photo_url }}" alt="" class="w-full h-full object-cover">
                @else
                    <span class="text-xs font-bold text-white">
                        {{ strtoupper(substr(Auth::user()->employee->first_name ?? Auth::user()->name, 0, 1)) }}
                    </span>
                @endif
            </a>
        </div>
    </header>
    @endif

    {{-- Impersonation banner --}}
    @if(session()->has('impersonator_id'))
        <div class="bg-amber-400 text-amber-900 px-4 py-2 flex items-center justify-between text-sm font-medium">
            <span>Viewing as {{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('impersonation.exit') }}">
                @csrf
                <button type="submit" class="underline font-semibold hover:text-amber-950">
                    Return to Admin
                </button>
            </form>
        </div>
    @endif

    {{-- iOS Notification Permission Banner (shown after PWA install, requires user tap) --}}
    <div id="notif-permission-banner" class="hidden mx-4 mt-3 rounded-xl border border-amber-200 overflow-hidden text-sm">
        <div class="flex items-center gap-3 px-4 py-3 bg-amber-50">
            <div class="flex-1">
                <p class="font-semibold text-amber-800">Enable Notifications</p>
                <p class="text-amber-700 text-xs">Tap to receive reminders and updates</p>
            </div>
            <button id="notif-enable-btn"
                    class="shrink-0 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                Enable
            </button>
            <button id="notif-dismiss-btn" class="shrink-0 text-amber-400 hover:text-amber-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Install App Banner (injected by JS) --}}
    <div id="pwa-install-banner" class="hidden mx-4 mt-3 rounded-xl border overflow-hidden text-sm">
        {{-- Android --}}
        <div id="pwa-android" class="hidden items-center gap-3 px-4 py-3 bg-green-50 border-green-200">
            <div class="flex-1">
                <p class="font-semibold text-green-800">Install Jamelle Payroll</p>
                <p class="text-green-700 text-xs">Add to home screen for a better experience</p>
            </div>
            <button id="pwa-install-btn"
                    class="shrink-0 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">
                Install
            </button>
            <button id="pwa-android-dismiss" class="shrink-0 text-green-400 hover:text-green-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        {{-- iOS --}}
        <div id="pwa-ios" class="hidden px-4 py-3 bg-blue-50 border-blue-200">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                    <p class="font-semibold text-blue-800">Install Jamelle Payroll</p>
                    <p class="text-blue-700 text-xs mt-1">
                        Tap
                        <svg class="inline w-4 h-4 mx-0.5 align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <strong>Share</strong>, then tap <strong>"Add to Home Screen"</strong>
                    </p>
                </div>
                <button id="pwa-ios-dismiss" class="shrink-0 text-blue-400 hover:text-blue-600 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Page content --}}
    <main class="flex-1 p-4 pb-24">
        {{-- Flash messages (rendered here so they appear below the header) --}}
        @if(session('success'))
            <div class="mb-3 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-3 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Bottom Navigation --}}
    <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-lg bg-white border-t border-gray-200 z-10">
        <div class="flex items-center justify-around py-2">

            <a href="{{ route('staff.dashboard') }}"
               class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('staff.dashboard') ? 'text-green-600' : 'text-gray-400' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-xs font-medium">Home</span>
            </a>

            @php $pendingOtCount = Auth::user()->employee?->dtrs()->where('ot_status', 'pending')->count() ?? 0; @endphp
            <a href="{{ route('staff.dtr.index') }}"
               class="relative flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('staff.dtr.*') ? 'text-green-600' : 'text-gray-400' }}">
                <div class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    @if($pendingOtCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-amber-400 rounded-full border-2 border-white"></span>
                    @endif
                </div>
                <span class="text-xs font-medium">DTR</span>
            </a>

            @php
                $pendingScheduleChangeCount = Auth::user()->employee
                    ? \App\Models\ScheduleChangeRequest::where('employee_id', Auth::user()->employee->id)
                        ->whereIn('status', ['pending', 'rejected'])
                        ->count()
                    : 0;
            @endphp
            <a href="{{ route('staff.schedule') }}"
               class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('staff.schedule') ? 'text-green-600' : 'text-gray-400' }}">
                <div class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    @if($pendingScheduleChangeCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-amber-400 rounded-full border-2 border-white"></span>
                    @endif
                </div>
                <span class="text-xs font-medium">Schedule</span>
            </a>

            @php
                $unreadAnnouncementCount = \App\Models\Announcement::published()
                    ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', Auth::id()))
                    ->count();
            @endphp
            <a href="{{ route('staff.announcements.index') }}"
               class="relative flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('staff.announcements.*') ? 'text-green-600' : 'text-gray-400' }}">
                <div class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                    @if($unreadAnnouncementCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 rounded-full border-2 border-white"></span>
                    @endif
                </div>
                <span class="text-xs font-medium">News</span>
            </a>

            <a href="{{ route('staff.profile') }}"
               class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('staff.profile*') ? 'text-green-600' : 'text-gray-400' }}">
                @if(Auth::user()->profile_photo_url)
                    <img src="{{ Auth::user()->profile_photo_url }}" alt="" class="w-6 h-6 rounded-full object-cover border {{ request()->routeIs('staff.profile*') ? 'border-green-600' : 'border-gray-300' }}">
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                @endif
                <span class="text-xs font-medium">Profile</span>
            </a>

        </div>
    </nav>

    @php
        $rankUpEvent = Auth::check() && Auth::user()->isStaff()
            ? \App\Models\RankUpEvent::with('employee')
                ->where('user_id', Auth::id())
                ->whereNull('seen_at')
                ->latest('occurred_at')
                ->latest('id')
                ->first()
            : null;
    @endphp

    @if($rankUpEvent)
        @php
            $rankUpMascotFile = str_pad((string) $rankUpEvent->new_rank_number, 2, '0', STR_PAD_LEFT);
            $rankUpMascotPath = "images/rank-mascots/mascot-{$rankUpMascotFile}.png";
            $rankUpMascotUrl = file_exists(public_path($rankUpMascotPath)) ? asset($rankUpMascotPath) : null;
            $rankUpFirstName = $rankUpEvent->employee?->first_name ?? Auth::user()->name;
        @endphp

        <style>
            .rankup-modal-backdrop {
                background:
                    radial-gradient(circle at 50% 26%, rgba(105, 255, 43, .18), transparent 34%),
                    rgba(0, 0, 0, .9);
                backdrop-filter: blur(10px);
            }
            .rankup-card {
                background: linear-gradient(180deg, #0e1b10 0%, #11131a 100%);
                border: 1px solid rgba(74, 222, 45, .38);
                box-shadow: 0 0 34px rgba(74, 222, 45, .13), inset 0 0 36px rgba(74, 222, 45, .06);
            }
            .rankup-aura {
                background:
                    radial-gradient(circle, rgba(246, 219, 43, .45) 0%, rgba(246, 219, 43, .2) 30%, transparent 62%),
                    radial-gradient(circle, rgba(74, 222, 45, .24) 0%, transparent 58%);
            }
            .rankup-confetti {
                inset: 0;
                overflow: hidden;
                pointer-events: none;
            }
            .rankup-confetti span {
                animation:
                    rankup-confetti-fall var(--fall-duration) linear infinite,
                    rankup-confetti-sway var(--sway-duration) ease-in-out infinite;
                background: var(--confetti-color);
                border-radius: var(--confetti-radius);
                height: var(--confetti-height);
                left: var(--confetti-left);
                opacity: .95;
                position: absolute;
                top: -12vh;
                transform: translate3d(0, 0, 0) rotate(var(--confetti-rotate));
                width: var(--confetti-width);
                will-change: transform;
            }
            @keyframes rankup-confetti-fall {
                0% {
                    top: -12vh;
                    opacity: 0;
                    transform: translate3d(0, 0, 0) rotate(var(--confetti-rotate));
                }
                8% { opacity: .95; }
                92% { opacity: .9; }
                100% {
                    top: 112vh;
                    opacity: 0;
                    transform: translate3d(var(--drift), 0, 0) rotate(calc(var(--confetti-rotate) + 540deg));
                }
            }
            @keyframes rankup-confetti-sway {
                0%, 100% { margin-left: 0; }
                50% { margin-left: var(--sway); }
            }
            @media (prefers-reduced-motion: reduce) {
                .rankup-confetti span {
                    animation: none;
                    opacity: .65;
                    top: var(--static-top);
                }
            }
        </style>

        <script>
            function rankUpModal(config) {
                return {
                    open: true,
                    sharing: false,
                    shareError: '',
                    data: config,
                    async markSeen() {
                        await this.post(this.data.seenUrl);
                        this.open = false;
                    },
                    async markShared() {
                        await this.post(this.data.sharedUrl);
                        this.open = false;
                    },
                    async post(url) {
                        try {
                            await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                            });
                        } catch (e) {
                            console.warn('Rank-up event update failed', e);
                        }
                    },
                    async share() {
                        this.sharing = true;
                        this.shareError = '';

                        const title = 'Rank up!';
                        const text = `${this.data.firstName} ranked up to ${this.data.rankName} with ${this.data.points} pts!`;

                        try {
                            const blob = await this.createShareImage();
                            const file = new File([blob], `rank-up-${this.data.rankSlug}.png`, { type: 'image/png' });

                            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                                await navigator.share({ title, text, files: [file] });
                                await this.markShared();
                                return;
                            }

                            if (navigator.share) {
                                await navigator.share({ title, text, url: window.location.origin });
                                await this.markShared();
                                return;
                            }

                            this.shareError = 'Sharing is not supported on this device.';
                        } catch (e) {
                            if (e && e.name === 'AbortError') return;
                            this.shareError = 'Sharing is not supported on this device.';
                        } finally {
                            this.sharing = false;
                        }
                    },
                    async createShareImage() {
                        const canvas = document.createElement('canvas');
                        canvas.width = 1080;
                        canvas.height = 1920;
                        const ctx = canvas.getContext('2d');

                        const grd = ctx.createLinearGradient(0, 0, 0, canvas.height);
                        grd.addColorStop(0, '#080912');
                        grd.addColorStop(.45, '#071407');
                        grd.addColorStop(1, '#050608');
                        ctx.fillStyle = grd;
                        ctx.fillRect(0, 0, canvas.width, canvas.height);

                        this.glow(ctx, 540, 720, 270, 'rgba(232, 214, 29, .34)');
                        this.glow(ctx, 540, 560, 380, 'rgba(74, 222, 45, .18)');

                        this.roundRect(ctx, 96, 132, 888, 1420, 78);
                        const panel = ctx.createLinearGradient(96, 132, 96, 1552);
                        panel.addColorStop(0, '#0e1d0f');
                        panel.addColorStop(1, '#12141c');
                        ctx.fillStyle = panel;
                        ctx.fill();
                        ctx.strokeStyle = 'rgba(74, 222, 45, .55)';
                        ctx.lineWidth = 4;
                        ctx.stroke();

                        this.pill(ctx, 360, 216, 360, 72, '#163d10', '#55c928', 'RANK UP!');

                        ctx.textAlign = 'center';
                        ctx.fillStyle = 'rgba(255,255,255,.52)';
                        ctx.font = '700 42px Inter, Arial, sans-serif';
                        ctx.fillText('Congratulations,', 540, 356);

                        ctx.fillStyle = '#ffffff';
                        ctx.font = '900 70px Inter, Arial, sans-serif';
                        ctx.fillText(`${this.data.firstName}!`, 540, 432);

                        ctx.fillStyle = 'rgba(255,255,255,.42)';
                        ctx.font = '800 36px Inter, Arial, sans-serif';
                        ctx.fillText('You have ranked up!', 540, 494);

                        if (this.data.mascotUrl) {
                            const img = await this.loadImage(this.data.mascotUrl);
                            this.glow(ctx, 540, 700, 190, 'rgba(74, 222, 45, .26)');
                            ctx.save();
                            ctx.beginPath();
                            ctx.arc(540, 700, 132, 0, Math.PI * 2);
                            ctx.clip();
                            ctx.drawImage(img, 408, 568, 264, 264);
                            ctx.restore();
                            ctx.beginPath();
                            ctx.arc(540, 700, 132, 0, Math.PI * 2);
                            ctx.strokeStyle = '#43e02a';
                            ctx.lineWidth = 10;
                            ctx.shadowColor = 'rgba(74, 222, 45, .5)';
                            ctx.shadowBlur = 28;
                            ctx.stroke();
                            ctx.shadowBlur = 0;
                        } else {
                            ctx.beginPath();
                            ctx.arc(540, 700, 150, 0, Math.PI * 2);
                            ctx.fillStyle = '#0a200d';
                            ctx.fill();
                            ctx.strokeStyle = '#43c51f';
                            ctx.lineWidth = 10;
                            ctx.stroke();
                            ctx.font = '900 92px Inter, Arial, sans-serif';
                            ctx.fillText('👑', 540, 730);
                        }

                        ctx.fillStyle = 'rgba(255,255,255,.35)';
                        ctx.font = '900 30px Inter, Arial, sans-serif';
                        ctx.fillText('NEW RANK', 540, 875);

                        ctx.fillStyle = '#46bf1c';
                        ctx.font = '900 76px Inter, Arial, sans-serif';
                        this.wrapCentered(ctx, this.data.rankName, 540, 975, 760, 82);

                        ctx.fillStyle = 'rgba(255,255,255,.36)';
                        ctx.font = '800 34px Inter, Arial, sans-serif';
                        ctx.fillText(`${this.data.points.toLocaleString()} pts milestone reached`, 540, 1096);

                        ctx.strokeStyle = 'rgba(255,255,255,.13)';
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        ctx.moveTo(170, 1210);
                        ctx.lineTo(910, 1210);
                        ctx.stroke();

                        ctx.fillStyle = '#ffffff';
                        ctx.font = '900 42px Inter, Arial, sans-serif';
                        ctx.fillText('Jamelle Payroll', 540, 1328);
                        ctx.fillStyle = 'rgba(255,255,255,.36)';
                        ctx.font = '700 28px Inter, Arial, sans-serif';
                        ctx.fillText('Celebrating growth, one shift at a time', 540, 1380);

                        return new Promise((resolve) => canvas.toBlob(resolve, 'image/png', .95));
                    },
                    loadImage(src) {
                        return new Promise((resolve, reject) => {
                            const img = new Image();
                            img.onload = () => resolve(img);
                            img.onerror = reject;
                            img.src = src;
                        });
                    },
                    roundRect(ctx, x, y, w, h, r) {
                        ctx.beginPath();
                        ctx.moveTo(x + r, y);
                        ctx.arcTo(x + w, y, x + w, y + h, r);
                        ctx.arcTo(x + w, y + h, x, y + h, r);
                        ctx.arcTo(x, y + h, x, y, r);
                        ctx.arcTo(x, y, x + w, y, r);
                        ctx.closePath();
                    },
                    glow(ctx, x, y, r, color) {
                        const g = ctx.createRadialGradient(x, y, 0, x, y, r);
                        g.addColorStop(0, color);
                        g.addColorStop(1, 'rgba(0,0,0,0)');
                        ctx.fillStyle = g;
                        ctx.fillRect(0, 0, 1080, 1920);
                    },
                    pill(ctx, x, y, w, h, bg, stroke, text) {
                        this.roundRect(ctx, x, y, w, h, h / 2);
                        ctx.fillStyle = bg;
                        ctx.fill();
                        ctx.strokeStyle = stroke;
                        ctx.lineWidth = 3;
                        ctx.stroke();
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#8bf05a';
                        ctx.font = '900 28px Inter, Arial, sans-serif';
                        ctx.fillText(`⚡  ${text}`, x + w / 2, y + 46);
                    },
                    wrapCentered(ctx, text, x, y, maxWidth, lineHeight) {
                        const words = text.split(' ');
                        const lines = [];
                        let line = '';

                        for (const word of words) {
                            const test = line ? `${line} ${word}` : word;
                            if (ctx.measureText(test).width > maxWidth && line) {
                                lines.push(line);
                                line = word;
                            } else {
                                line = test;
                            }
                        }
                        lines.push(line);

                        lines.forEach((row, index) => ctx.fillText(row, x, y + index * lineHeight));
                    },
                };
            }
        </script>

        <div x-data="rankUpModal(@js([
                'firstName' => $rankUpFirstName,
                'rankName' => $rankUpEvent->new_rank_name,
                'rankSlug' => str($rankUpEvent->new_rank_name)->slug()->toString(),
                'points' => $rankUpEvent->points,
                'mascotUrl' => $rankUpMascotUrl,
                'seenUrl' => route('staff.rank-up-events.seen', $rankUpEvent),
                'sharedUrl' => route('staff.rank-up-events.shared', $rankUpEvent),
            ]))"
             x-show="open"
             x-cloak
             class="rankup-modal-backdrop fixed inset-0 z-[80] flex items-center justify-center px-3 py-6">
            <div class="rankup-confetti absolute" aria-hidden="true">
                @foreach([
                    ['7%', '#2f80ed', '14px', '14px', '3px', '0deg', '7.5s', '3.4s', '34px', '-18px', '8vh'],
                    ['16%', '#1f9d72', '18px', '18px', '999px', '12deg', '8.3s', '3.1s', '-28px', '16px', '14vh'],
                    ['27%', '#d6c700', '16px', '16px', '2px', '45deg', '6.8s', '2.9s', '42px', '-24px', '5vh'],
                    ['39%', '#38a629', '20px', '20px', '999px', '0deg', '8.7s', '3.8s', '-38px', '20px', '12vh'],
                    ['50%', '#c96a1b', '16px', '16px', '999px', '18deg', '7.2s', '3.2s', '26px', '-16px', '9vh'],
                    ['62%', '#44b936', '22px', '9px', '2px', '34deg', '9.1s', '3.5s', '-34px', '24px', '18vh'],
                    ['76%', '#c74c7a', '16px', '16px', '3px', '25deg', '7.9s', '3s', '32px', '-18px', '7vh'],
                    ['88%', '#2f80ed', '12px', '18px', '2px', '8deg', '8.9s', '3.6s', '-28px', '16px', '15vh'],
                    ['10%', '#55c928', '14px', '8px', '2px', '30deg', '9.5s', '4.1s', '36px', '-20px', '24vh'],
                    ['32%', '#f4c430', '10px', '18px', '2px', '16deg', '8.1s', '3.4s', '-32px', '18px', '21vh'],
                    ['57%', '#2f80ed', '18px', '10px', '2px', '42deg', '7.6s', '3.7s', '30px', '-18px', '27vh'],
                    ['81%', '#55c928', '14px', '14px', '999px', '0deg', '9.2s', '3.3s', '-40px', '22px', '22vh'],
                ] as $piece)
                    <span style="
                        --confetti-left: {{ $piece[0] }};
                        --confetti-color: {{ $piece[1] }};
                        --confetti-width: {{ $piece[2] }};
                        --confetti-height: {{ $piece[3] }};
                        --confetti-radius: {{ $piece[4] }};
                        --confetti-rotate: {{ $piece[5] }};
                        --fall-duration: {{ $piece[6] }};
                        --sway-duration: {{ $piece[7] }};
                        --drift: {{ $piece[8] }};
                        --sway: {{ $piece[9] }};
                        --static-top: {{ $piece[10] }};
                        animation-delay: -{{ $loop->index * 0.47 }}s;
                    "></span>
                @endforeach
            </div>

            <div class="rankup-card relative z-10 w-full max-w-[320px] rounded-[28px] px-6 pt-4 pb-7 text-center text-white">
                <div class="inline-flex items-center gap-2 rounded-full border border-green-500/70 bg-green-950/70 px-4 py-1.5 text-[11px] font-black tracking-widest text-green-400">
                    <span class="text-amber-400">⚡</span>
                    RANK UP!
                </div>

                <div class="mt-4 text-left pl-[70px]">
                    <p class="text-sm font-extrabold text-white/45">Congratulations,</p>
                    <p class="text-2xl font-black leading-none text-white">
                        {{ $rankUpFirstName }}! <span aria-hidden="true">🎉</span>
                    </p>
                    <p class="mt-1 text-sm font-extrabold text-white/35">You have ranked up!</p>
                </div>

                <div class="relative mx-auto mt-4 flex h-36 w-36 items-center justify-center">
                    <div class="rankup-aura absolute inset-[-18px] rounded-full"></div>
                    <div class="relative flex h-28 w-28 items-center justify-center">
                        @if($rankUpMascotUrl)
                            <img src="{{ $rankUpMascotUrl }}"
                                 alt="{{ $rankUpEvent->new_rank_name }}"
                                 class="h-28 w-28 rounded-full border-[3px] border-green-500 object-cover shadow-[0_0_28px_rgba(74,222,45,.45)]">
                        @else
                            <span class="flex h-24 w-24 items-center justify-center rounded-full border-[3px] border-green-500 bg-[#0b220d] text-3xl shadow-[0_0_24px_rgba(74,222,45,.24)]">👑</span>
                        @endif
                    </div>
                </div>

                <p class="mt-1 text-[11px] font-black tracking-widest text-white/30">NEW RANK</p>
                <p class="mt-1 text-2xl font-black leading-tight text-[#46bf1c]">{{ $rankUpEvent->new_rank_name }}</p>
                <p class="mt-1 text-sm font-extrabold text-white/35">{{ number_format($rankUpEvent->points) }} pts milestone reached</p>

                <div class="my-5 h-px bg-white/10"></div>

                <button type="button"
                        @click="share()"
                        :disabled="sharing"
                        class="flex w-full items-center justify-center gap-3 rounded-xl bg-[#45bf20] py-4 text-sm font-black text-white shadow-[0_12px_24px_rgba(69,191,32,.22)] disabled:opacity-70">
                    <span aria-hidden="true">📲</span>
                    <span x-text="sharing ? 'Preparing...' : 'Share'"></span>
                </button>

                <div class="mt-3 rounded-xl border border-white/10 bg-white/[.04] px-3 py-3 text-xs font-extrabold text-white/35">
                    💬 Share it to your GC to let everyone know!
                </div>

                <p x-show="shareError" x-cloak class="mt-3 text-xs font-semibold text-amber-300" x-text="shareError"></p>

                <button type="button"
                        @click="markSeen()"
                        class="mt-6 text-xs font-black text-white/35">
                    Maybe later
                </button>
            </div>
        </div>
    @endif

</div>

<script>
    // PWA Install Banner
    (function () {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        if (isStandalone) return; // already installed, show nothing

        const banner   = document.getElementById('pwa-install-banner');
        const android  = document.getElementById('pwa-android');
        const ios      = document.getElementById('pwa-ios');
        const isIOS    = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;

        function showBanner(el) {
            banner.classList.remove('hidden');
            el.classList.remove('hidden');
            el.classList.add('flex');
        }

        // --- Android ---
        let deferredPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showBanner(android);
        });

        document.getElementById('pwa-install-btn').addEventListener('click', async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (outcome === 'accepted') {
                banner.classList.add('hidden');
            }
        });

        document.getElementById('pwa-android-dismiss').addEventListener('click', () => {
            banner.classList.add('hidden');
        });

        window.addEventListener('appinstalled', () => {
            banner.classList.add('hidden');
            deferredPrompt = null;
        });

        // --- iOS ---
        if (isIOS && !localStorage.getItem('pwa-ios-dismissed')) {
            showBanner(ios);
        }

        document.getElementById('pwa-ios-dismiss').addEventListener('click', () => {
            localStorage.setItem('pwa-ios-dismissed', '1');
            banner.classList.add('hidden');
        });
    })();

    const VAPID_PUBLIC_KEY = '{{ config('services.vapid.public_key') }}';

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
    }

    async function subscribeToPush(registration) {
        try {
            console.log('[Push] SW state:', registration.active?.state);
            const existing = await registration.pushManager.getSubscription();
            console.log('[Push] Existing subscription:', existing ? 'yes' : 'none');
            const subscription = existing || await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });
            console.log('[Push] Subscription endpoint:', subscription.endpoint);

            const res = await fetch('{{ route('push-subscriptions.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(subscription.toJSON()),
            });
            console.log('[Push] Server response:', res.status);
        } catch (e) {
            console.error('[Push] Subscription failed:', e);
        }
    }

    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('/sw.js').catch(console.error);

        navigator.serviceWorker.ready.then(registration => {
            console.log('[Push] SW ready, permission:', Notification.permission);

            if (Notification.permission === 'granted') {
                subscribeToPush(registration);
            } else if (Notification.permission === 'default') {
                if (isIOS) {
                    // iOS requires permission from a user gesture — show the banner instead
                    const banner = document.getElementById('notif-permission-banner');
                    if (!localStorage.getItem('notif-banner-dismissed')) {
                        banner.classList.remove('hidden');
                    }

                    document.getElementById('notif-enable-btn').addEventListener('click', () => {
                        Notification.requestPermission().then(permission => {
                            console.log('[Push] iOS permission result:', permission);
                            banner.classList.add('hidden');
                            if (permission === 'granted') subscribeToPush(registration);
                        });
                    });

                    document.getElementById('notif-dismiss-btn').addEventListener('click', () => {
                        localStorage.setItem('notif-banner-dismissed', '1');
                        banner.classList.add('hidden');
                    });
                } else {
                    // Android: auto-request is fine
                    Notification.requestPermission().then(permission => {
                        console.log('[Push] Permission result:', permission);
                        if (permission === 'granted') subscribeToPush(registration);
                    });
                }
            }
        });
    } else {
        console.warn('[Push] Not supported:', 'serviceWorker' in navigator, 'PushManager' in window);
    }

</script>

</body>
</html>
