<x-staff-layout title="Notifications">

    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">{{ $notifications->total() }} total</p>
        @if(Auth::user()->unreadNotifications()->count() > 0)
        <form method="POST" action="{{ route('staff.notifications.mark-read') }}">
            @csrf
            <button type="submit" class="text-xs text-green-600 font-medium">Mark all read</button>
        </form>
        @endif
    </div>

    <div class="space-y-2">
        @forelse($notifications as $n)
        @php $data = $n->data; @endphp
        <div class="bg-white rounded-xl border {{ $n->read_at ? 'border-gray-100' : 'border-green-200 bg-green-50' }} p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    @if(($data['type'] ?? '') === 'ot_approved')
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    @elseif(($data['type'] ?? '') === 'ot_rejected')
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800">{{ $data['message'] ?? 'Notification' }}</p>
                    @if(!empty($data['reason']))
                        <p class="text-xs text-red-500 mt-0.5">Reason: {{ $data['reason'] }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                </div>
                @if(!$n->read_at)
                    <div class="w-2 h-2 rounded-full bg-green-500 mt-1 flex-shrink-0"></div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-400 text-sm">No notifications yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>

</x-staff-layout>
