<x-app-layout>
    <x-slot name="title">{{ $announcement->subject }}</x-slot>

    <div class="max-w-3xl">

        <div class="flex items-center gap-3 mb-5">
            <a href="{{ route('announcements.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900 truncate">{{ $announcement->subject }}</h1>
        </div>

        {{-- Announcement preview --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-5">
            <div class="flex items-center justify-between gap-4 mb-4 pb-4 border-b border-gray-100">
                <div>
                    <p class="text-xs text-gray-400">Published by {{ $announcement->author?->name ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $announcement->published_at?->format('F j, Y g:i A') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($announcement->status === 'published')
                        <form method="POST" action="{{ route('announcements.resend-push', $announcement) }}"
                              onsubmit="return confirm('Resend this push notification to all subscribed staff devices?');">
                            @csrf
                            <button type="submit"
                                    class="text-xs font-medium text-amber-600 hover:text-amber-700 transition">
                                Resend Push
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('announcements.edit', $announcement) }}"
                       class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition">Edit</a>
                </div>
            </div>
            <div class="announcement-body text-sm text-gray-800">
                {!! $announcement->body !!}
            </div>
        </div>

        {{-- Read list --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <p class="font-semibold text-gray-800 text-sm">Read by</p>
                <span class="text-xs font-medium text-gray-500">{{ number_format($reads->count()) }} {{ Str::plural('person', $reads->count()) }}</span>
            </div>

            @if($reads->isEmpty())
                <div class="px-5 py-8 text-center">
                    <p class="text-sm text-gray-400">No one has read this yet.</p>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-5 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Email</th>
                            <th class="text-right px-5 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Read at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($reads as $r)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $r->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ $r->user?->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-right text-gray-500 text-xs whitespace-nowrap">{{ $r->read_at->format('M j, Y g:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

    </div>

    @push('styles')
    <style>
        .announcement-body h2 { font-size: 1.125rem; font-weight: 700; margin: .75rem 0 .25rem; }
        .announcement-body h3 { font-size: 1rem; font-weight: 600; margin: .5rem 0 .25rem; }
        .announcement-body ul { list-style: disc; padding-left: 1.25rem; margin: .25rem 0; }
        .announcement-body ol { list-style: decimal; padding-left: 1.25rem; margin: .25rem 0; }
        .announcement-body a  { color: #4f46e5; text-decoration: underline; }
        .announcement-body img { max-width: 100%; border-radius: .375rem; margin: .5rem 0; }
        .announcement-body p  { margin: .25rem 0; }
    </style>
    @endpush

</x-app-layout>
