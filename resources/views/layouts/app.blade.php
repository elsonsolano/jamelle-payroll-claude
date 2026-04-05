<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">

<div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">

    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-30 w-64 bg-white text-gray-900 flex flex-col transition-transform duration-200 ease-in-out border-r-2 border-green-500
                  lg:relative lg:translate-x-0 lg:flex lg:flex-shrink-0">

        {{-- Logo --}}
        <div class="h-16 flex items-center px-6 border-b border-gray-200 flex-shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="Jamelle 1122 Corporation" class="h-14 w-auto">
            {{-- Close button (mobile only) --}}
            <button @click="sidebarOpen = false" class="ml-auto text-gray-400 hover:text-gray-700 lg:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            @php $user = Auth::user(); @endphp

            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </x-slot>
                Dashboard
            </x-sidebar-link>

            <div class="pt-3 pb-1 px-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Organization</p>
            </div>

            @if($user->isSuperAdmin())
            <x-sidebar-link :href="route('branches.index')" :active="request()->routeIs('branches.*')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </x-slot>
                Branches
            </x-sidebar-link>
            @endif

            <x-sidebar-link :href="route('employees.index')" :active="request()->routeIs('employees.*')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </x-slot>
                Employees
            </x-sidebar-link>

            @if($user->isSuperAdmin())
            <x-sidebar-link :href="route('admin-users.index')" :active="request()->routeIs('admin-users.*')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </x-slot>
                Admin Users
            </x-sidebar-link>

            <div class="pt-3 pb-1 px-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Attendance</p>
            </div>

            <x-sidebar-link :href="route('dtr.index')" :active="request()->routeIs('dtr.*')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </x-slot>
                DTR
            </x-sidebar-link>

            {{-- Timemark hidden: attendance is now manually entered by staff --}}

            <div class="pt-3 pb-1 px-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Payroll</p>
            </div>

            <x-sidebar-link :href="route('payroll.cutoffs.index')" :active="request()->routeIs('payroll.*')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </x-slot>
                Payroll Cutoffs
            </x-sidebar-link>
            @endif

            @if($user->hasPermission('schedules'))
            <x-sidebar-link :href="route('schedule-uploads.index')" :active="request()->routeIs('schedule-uploads.*')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </x-slot>
                Schedules
            </x-sidebar-link>
            @endif

            @if($user->isSuperAdmin())
            <x-sidebar-link :href="route('holidays.index')" :active="request()->routeIs('holidays.*')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </x-slot>
                Holidays
            </x-sidebar-link>

            <div class="pt-3 pb-1 px-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Reports</p>
            </div>

            <x-sidebar-link :href="route('reports.lates')" :active="request()->routeIs('reports.lates')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </x-slot>
                Lates
            </x-sidebar-link>

            <x-sidebar-link :href="route('reports.overtime')" :active="request()->routeIs('reports.overtime')" @click="sidebarOpen = false">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </x-slot>
                Overtime
            </x-sidebar-link>
            @endif

        </nav>

        {{-- User --}}
        <div class="border-t border-gray-200 p-4 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-sm font-semibold text-white flex-shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="text-gray-400 hover:text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">

        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-4 lg:px-6 flex-shrink-0 gap-3">
            {{-- Hamburger (mobile only) --}}
            <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-800 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-800 truncate">{{ $title ?? 'Dashboard' }}</h1>
            <div class="ml-auto flex items-center gap-2 flex-shrink-0">
                {{ $actions ?? '' }}
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
                    {{ session('warning') }}
                </div>
            @endif

            {{ $slot }}

            <p class="text-center text-xs text-gray-400 mt-8 mb-2">
                Powered by <a href="https://www.instagram.com/futuristech.ph/" target="_blank" rel="noopener" class="hover:text-gray-600 transition">Futuristech.ph</a>
            </p>
        </main>
    </div>

</div>

@stack('scripts')
</body>
</html>
