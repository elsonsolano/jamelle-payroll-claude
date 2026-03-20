<x-staff-layout title="OT Approvals">

    @if($pendingDtrs->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-400 text-sm">No pending overtime requests.</p>
        </div>
    @else
        <p class="text-sm text-gray-500 mb-3">{{ $pendingDtrs->count() }} pending request(s)</p>
        <div class="space-y-3">
            @foreach($pendingDtrs as $dtr)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4"
                 x-data="{ showReject: false }">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $dtr->employee->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ $dtr->employee->branch->name }} &nbsp;·&nbsp; {{ $dtr->employee->position }}</p>
                    </div>
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Pending</span>
                </div>

                <div class="bg-gray-50 rounded-xl p-3 mb-3 text-xs text-gray-600 space-y-1">
                    <p><span class="font-medium">Date:</span> {{ $dtr->date->format('D, M d Y') }}</p>
                    <p><span class="font-medium">Time Out:</span> {{ substr($dtr->time_out ?? '—', 0, 5) }}</p>
                    <p><span class="font-medium">OT Hours:</span> {{ $dtr->overtime_hours }}h</p>
                </div>

                <div class="flex gap-2">
                    <form method="POST" action="{{ route('staff.ot-approvals.approve', $dtr) }}" class="flex-1">
                        @csrf
                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2.5 rounded-xl transition">
                            Approve
                        </button>
                    </form>
                    <button type="button" @click="showReject = !showReject"
                            class="flex-1 border border-red-300 text-red-600 text-sm font-semibold py-2.5 rounded-xl hover:bg-red-50 transition">
                        Reject
                    </button>
                </div>

                {{-- Reject form --}}
                <div x-show="showReject" x-cloak class="mt-3">
                    <form method="POST" action="{{ route('staff.ot-approvals.reject', $dtr) }}">
                        @csrf
                        <textarea name="reason" rows="2" placeholder="Reason (optional)"
                                  class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 mb-2 resize-none"></textarea>
                        <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2.5 rounded-xl transition">
                            Confirm Rejection
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</x-staff-layout>
