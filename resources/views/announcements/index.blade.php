<x-app-layout>
    <x-slot name="title">Announcements</x-slot>

    <x-slot name="actions">
        <a href="{{ route('announcements.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Announcement
        </a>
    </x-slot>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Subject</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Status</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Date</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Reads</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($announcements as $a)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-gray-900">{{ $a->subject }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">by {{ $a->author?->name ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($a->status === 'published')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Published</span>
                            @elseif($a->status === 'scheduled')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Scheduled</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Draft</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs">
                            @if($a->status === 'published' && $a->published_at)
                                {{ $a->published_at->format('M j, Y g:i A') }}
                            @elseif($a->status === 'scheduled' && $a->scheduled_at)
                                {{ $a->scheduled_at->format('M j, Y g:i A') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            @if($a->status === 'published')
                                <a href="{{ route('announcements.show', $a) }}"
                                   class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    {{ number_format($a->reads_count) }}
                                </a>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div x-data="{ open: false }" class="inline-flex items-center gap-2">
                                <a href="{{ route('announcements.edit', $a) }}"
                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                <button type="button" @click="open = true"
                                        class="text-sm font-medium text-red-500 hover:text-red-700">Delete</button>

                                <div x-show="open" x-cloak
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                                     @click.self="open = false">
                                    <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full">
                                        <p class="font-semibold text-gray-900">Delete announcement?</p>
                                        <p class="text-sm text-gray-500 mt-1">This cannot be undone.</p>
                                        <div class="flex justify-end gap-3 mt-5">
                                            <button type="button" @click="open = false"
                                                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</button>
                                            <form method="POST" action="{{ route('announcements.destroy', $a) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                            <p class="text-sm text-gray-400">No announcements yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($announcements->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>

</x-app-layout>
