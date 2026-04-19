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
               class="flex flex-col items-center gap-0.5 px-4 py-1 {{ request()->routeIs('staff.dashboard') ? 'text-green-600' : 'text-gray-400' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-xs font-medium">Home</span>
            </a>

            @php $pendingOtCount = Auth::user()->employee?->dtrs()->where('ot_status', 'pending')->count() ?? 0; @endphp
            <a href="{{ route('staff.dtr.index') }}"
               class="relative flex flex-col items-center gap-0.5 px-4 py-1 {{ request()->routeIs('staff.dtr.*') ? 'text-green-600' : 'text-gray-400' }}">
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
               class="flex flex-col items-center gap-0.5 px-4 py-1 {{ request()->routeIs('staff.schedule') ? 'text-green-600' : 'text-gray-400' }}">
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

            <a href="{{ route('staff.profile') }}"
               class="flex flex-col items-center gap-0.5 px-4 py-1 {{ request()->routeIs('staff.profile') ? 'text-green-600' : 'text-gray-400' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-xs font-medium">Profile</span>
            </a>

        </div>
    </nav>

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
