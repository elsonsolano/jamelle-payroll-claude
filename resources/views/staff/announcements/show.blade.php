<x-staff-layout>
    <x-slot name="title">{{ $announcement->subject }}</x-slot>

    <div class="px-4 pt-4 pb-24">

        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('staff.announcements.index') }}" class="text-gray-400 p-1 -ml-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-base font-bold text-gray-900 leading-tight">{{ $announcement->subject }}</h1>
        </div>

        <p class="text-xs text-gray-400 mb-4">
            {{ $announcement->published_at?->format('F j, Y · g:i A') }}
        </p>

        <div class="bg-white rounded-2xl border border-gray-100 p-4 announcement-body text-sm text-gray-800 leading-relaxed">
            {!! $announcement->body !!}
        </div>

    </div>

    @push('styles')
    <style>
        .announcement-body h2 { font-size: 1.125rem; font-weight: 700; margin: .75rem 0 .25rem; color: #111; }
        .announcement-body h3 { font-size: 1rem; font-weight: 600; margin: .5rem 0 .25rem; color: #111; }
        .announcement-body ul { list-style: disc; padding-left: 1.25rem; margin: .5rem 0; }
        .announcement-body ol { list-style: decimal; padding-left: 1.25rem; margin: .5rem 0; }
        .announcement-body li { margin: .25rem 0; }
        .announcement-body a  { color: #4f46e5; text-decoration: underline; }
        .announcement-body img { max-width: 100%; border-radius: .75rem; margin: .75rem 0; }
        .announcement-body p  { margin: .4rem 0; }
    </style>
    @endpush

</x-staff-layout>
