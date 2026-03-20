<x-staff-layout title="Dashboard">

    {{-- Welcome card --}}
    <div class="bg-indigo-600 text-white rounded-2xl p-4 mb-4">
        <p class="text-sm text-indigo-200">Welcome back,</p>
        <h2 class="text-xl font-bold">{{ Auth::user()->employee->first_name }}</h2>
        <p class="text-xs text-indigo-300 mt-0.5">{{ Auth::user()->employee->position }}</p>
    </div>

    {{-- Quick stats --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Pending OT</p>
            <p class="text-2xl font-bold text-amber-500">{{ $pendingOtCount }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Unread Notifications</p>
            <p class="text-2xl font-bold text-indigo-500">{{ $unreadCount }}</p>
        </div>
        @if($pendingApprovalCount > 0)
        <div class="col-span-2 bg-amber-50 rounded-2xl border border-amber-200 p-4 shadow-sm">
            <p class="text-xs text-amber-700 mb-1">OT Approvals Waiting</p>
            <div class="flex items-center justify-between">
                <p class="text-2xl font-bold text-amber-600">{{ $pendingApprovalCount }}</p>
                <a href="{{ route('staff.ot-approvals.index') }}" class="text-xs bg-amber-600 text-white px-3 py-1.5 rounded-lg font-medium">Review</a>
            </div>
        </div>
        @endif
    </div>

    {{-- Log DTR button --}}
    <a href="{{ route('staff.dtr.create') }}"
       class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center font-semibold py-4 rounded-2xl shadow mb-4 transition text-sm">
        + Log Today's DTR
    </a>

    {{-- Recent DTRs --}}
    <h3 class="text-sm font-semibold text-gray-700 mb-2">Recent DTRs</h3>
    <div class="space-y-2">
        @forelse($recentDtrs as $dtr)
        <div class="bg-white rounded-xl border border-gray-100 p-3 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $dtr->date->format('D, M d Y') }}</p>
                <p class="text-xs text-gray-400">
                    {{ $dtr->time_in ? substr($dtr->time_in, 0, 5) : '--' }}
                    –
                    {{ $dtr->time_out ? substr($dtr->time_out, 0, 5) : '--' }}
                    &nbsp;·&nbsp;{{ $dtr->total_hours }}h
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($dtr->ot_status !== 'none')
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $dtr->ot_status === 'approved' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $dtr->ot_status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                        {{ $dtr->ot_status === 'rejected' ? 'bg-red-100 text-red-700' : '' }}">
                        OT {{ ucfirst($dtr->ot_status) }}
                    </span>
                @endif
                <a href="{{ route('staff.dtr.edit', $dtr) }}" class="text-gray-400 hover:text-indigo-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
            </div>
        </div>
        @empty
        <div class="text-center py-8 text-gray-400 text-sm">No DTRs yet. Start logging!</div>
        @endforelse
    </div>

    @if($recentDtrs->count() > 0)
    <a href="{{ route('staff.dtr.index') }}" class="block text-center text-indigo-600 text-sm mt-3 font-medium">View All DTRs</a>
    @endif

</x-staff-layout>
