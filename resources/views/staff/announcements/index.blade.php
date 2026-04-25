<x-staff-layout>
    <x-slot name="title">Announcements</x-slot>

    <div class="px-4 pt-4 pb-24">

        <h1 class="text-lg font-bold text-gray-900 mb-4">Announcements</h1>

        @if($announcements->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                <p class="text-sm font-medium text-gray-400">No announcements yet.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($announcements as $a)
                    <a href="{{ route('staff.announcements.show', $a) }}"
                       class="flex items-start gap-3 bg-white rounded-2xl border px-4 py-3.5 transition active:scale-[.98]"
                       style="border-color: {{ $a->is_read ? '#EBEBEB' : '#C8ECA4' }}; background: {{ $a->is_read ? '#fff' : '#F4FBF0' }}; text-decoration:none;">

                        {{-- Unread dot --}}
                        <div class="mt-1 shrink-0">
                            @if(! $a->is_read)
                                <span class="block w-2.5 h-2.5 rounded-full" style="background:#5BBF27;"></span>
                            @else
                                <span class="block w-2.5 h-2.5 rounded-full bg-gray-200"></span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate" style="color:#111;">{{ $a->subject }}</p>
                            <p class="text-xs mt-0.5" style="color:#999;">
                                {{ $a->published_at?->format('M j, Y') }}
                                @if(! $a->is_read)
                                    · <span style="color:#5BBF27; font-weight:600;">New</span>
                                @endif
                            </p>
                        </div>

                        <svg class="w-4 h-4 shrink-0 mt-1" style="color:#ccc;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        @endif

    </div>

</x-staff-layout>
