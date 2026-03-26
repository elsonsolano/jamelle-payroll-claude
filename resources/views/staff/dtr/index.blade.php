<x-staff-layout title="My DTR">

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-700">All DTR Records</h2>
        <a href="{{ route('staff.dtr.create') }}"
           class="bg-green-600 text-white text-xs font-semibold px-3 py-2 rounded-xl">
            + New DTR
        </a>
    </div>

    <div class="space-y-2">
        @forelse($dtrs as $dtr)
        <div class="bg-white rounded-xl border border-gray-100 p-3 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">{{ $dtr->date->format('D, M d Y') }}</p>
                    @php
                        $fmt12 = fn($t) => $t ? date('g:i A', strtotime($t)) : '—';
                    @endphp
                    <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                        <p>In: {{ $fmt12($dtr->time_in) }}
                           &nbsp; Break: {{ $fmt12($dtr->am_out) }}</p>
                        <p>End Break: {{ $fmt12($dtr->pm_in) }}
                           &nbsp; Out: {{ $fmt12($dtr->time_out) }}</p>
                        @php $utMins = $dtr->total_hours > 0 ? max(0, (int)round((8 - $dtr->total_hours) * 60)) : 0; @endphp
                        <p>Total: {{ $dtr->total_hours }}h
                           &nbsp;·&nbsp; Late: {{ $dtr->late_mins }}m
                           &nbsp;·&nbsp; <span class="{{ $utMins > 0 ? 'text-red-500 font-semibold' : '' }}">UT: {{ $utMins }}m</span></p>
                        @if($dtr->ot_status !== 'none')
                        <p>OT: {{ $dtr->overtime_hours }}h</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1 ml-2">
                    @if($dtr->ot_status !== 'none')
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $dtr->ot_status === 'approved' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $dtr->ot_status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                            {{ $dtr->ot_status === 'rejected' ? 'bg-red-100 text-red-700' : '' }}">
                            OT {{ ucfirst($dtr->ot_status) }}
                        </span>
                        @if($dtr->ot_status === 'rejected' && $dtr->ot_rejection_reason)
                            <p class="text-xs text-red-500 max-w-32 text-right">{{ $dtr->ot_rejection_reason }}</p>
                        @endif
                    @endif
                    <a href="{{ route('staff.dtr.edit', $dtr) }}"
                       class="text-xs text-green-600 font-medium mt-1">Edit</a>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-400 text-sm">No DTR records yet.</div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $dtrs->links() }}
    </div>

</x-staff-layout>
