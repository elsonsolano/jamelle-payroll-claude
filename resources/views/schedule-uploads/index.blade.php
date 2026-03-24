<x-app-layout>
    <x-slot name="title">Schedule Uploads</x-slot>

    <x-slot name="actions">
        <a href="{{ route('schedule-uploads.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Upload Schedule
        </a>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($uploads->isEmpty())
            <div class="px-5 py-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-sm">No schedule uploads yet.</p>
                <p class="text-gray-400 text-sm mt-1">Click <span class="font-medium text-indigo-600">Upload Schedule</span> to get started.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left">
                        <th class="px-5 py-3 font-semibold text-gray-600">Period</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Branch</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Uploaded By</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Uploaded</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($uploads as $upload)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $upload->label ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $upload->branch->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $upload->uploader->name }}</td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ $upload->created_at->format('M d, Y h:i A') }}</td>
                            <td class="px-5 py-3">
                                @if($upload->status === 'applied')
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 text-green-700">Applied</span>
                                @elseif($upload->status === 'review')
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">Needs Review</span>
                                @else
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">Pending</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($upload->status === 'review')
                                    <a href="{{ route('schedule-uploads.review', $upload) }}"
                                       class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Review & Apply</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($uploads->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">{{ $uploads->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
