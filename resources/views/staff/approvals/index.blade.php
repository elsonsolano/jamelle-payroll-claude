<x-staff-layout title="Approvals">

    @php $fmt12 = fn($t) => $t ? date('g:i A', strtotime($t)) : '—'; @endphp

    {{-- Tab Navigation --}}
    <div class="flex bg-gray-100 rounded-xl p-1 mb-4">
        <a href="{{ route('staff.approvals.index', ['tab' => 'ot']) }}"
           class="flex-1 text-center text-sm font-semibold py-2 rounded-lg transition
                  {{ $activeTab === 'ot' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500' }}">
            Overtime
            @if($pendingDtrs->isNotEmpty())
                <span class="ml-1 text-xs bg-amber-500 text-white rounded-full px-1.5 py-0.5">{{ $pendingDtrs->count() }}</span>
            @endif
        </a>
        <a href="{{ route('staff.approvals.index', ['tab' => 'schedule']) }}"
           class="flex-1 text-center text-sm font-semibold py-2 rounded-lg transition
                  {{ $activeTab === 'schedule' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500' }}">
            Schedule
            @if($pendingScheduleChanges->isNotEmpty())
                <span class="ml-1 text-xs bg-amber-500 text-white rounded-full px-1.5 py-0.5">{{ $pendingScheduleChanges->count() }}</span>
            @endif
        </a>
    </div>

    {{-- ─── OT Tab ─── --}}
    @if($activeTab === 'ot')
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

                    <div class="bg-gray-50 rounded-xl p-3 mb-3 text-xs text-gray-600 grid grid-cols-2 gap-x-3 gap-y-1">
                        <p><span class="font-medium">Date:</span> {{ $dtr->date->format('D, M d Y') }}</p>
                        <p><span class="font-medium">OT Hours:</span> {{ $dtr->overtime_hours }}h</p>
                        <p><span class="font-medium">Time In:</span> {{ $fmt12($dtr->time_in) }}</p>
                        <p><span class="font-medium">Time Out:</span> {{ $fmt12($dtr->time_out) }}</p>
                        <p><span class="font-medium">Start Break:</span> {{ $fmt12($dtr->am_out) }}</p>
                        <p><span class="font-medium">End Break:</span> {{ $fmt12($dtr->pm_in) }}</p>
                    </div>

                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('staff.approvals.ot.approve', $dtr) }}" class="flex-1">
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

                    <div x-show="showReject" x-cloak class="mt-3">
                        <form method="POST" action="{{ route('staff.approvals.ot.reject', $dtr) }}">
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
    @endif

    {{-- ─── Schedule Changes Tab ─── --}}
    @if($activeTab === 'schedule')
        @if($pendingScheduleChanges->isEmpty())
            <div class="text-center py-16">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-sm">No pending schedule change requests.</p>
            </div>
        @else
            <p class="text-sm text-gray-500 mb-3">{{ $pendingScheduleChanges->count() }} pending request(s)</p>
            <div class="space-y-3">
                @foreach($pendingScheduleChanges as $req)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4"
                     x-data="{
                         showApprove: false,
                         showReject: false,
                         isDayOff: {{ $req->is_day_off ? 'true' : 'false' }},
                         approvedStart: '{{ $req->requested_work_start_time ? \Carbon\Carbon::parse($req->requested_work_start_time)->format('H:i') : '' }}',
                         approvedEnd: '{{ $req->requested_work_end_time ? \Carbon\Carbon::parse($req->requested_work_end_time)->format('H:i') : '' }}'
                     }">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $req->employee->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $req->employee->branch->name }} &nbsp;·&nbsp; {{ $req->employee->position }}</p>
                        </div>
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium shrink-0 ml-2">Pending</span>
                    </div>

                    {{-- Date --}}
                    <p class="text-xs font-semibold text-gray-500 mb-2">{{ $req->date->format('D, M d Y') }}</p>

                    {{-- Current vs Requested --}}
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-400 font-medium mb-1">Current</p>
                            @if($req->is_current_day_off)
                                <p class="text-sm font-semibold text-orange-500">Rest Day</p>
                            @elseif($req->current_work_start_time)
                                <p class="text-sm font-semibold text-gray-700">{{ $fmt12($req->current_work_start_time) }}</p>
                                <p class="text-xs text-gray-400">to {{ $fmt12($req->current_work_end_time) }}</p>
                            @else
                                <p class="text-sm text-gray-400">No schedule</p>
                            @endif
                        </div>
                        <div class="bg-indigo-50 rounded-xl p-3">
                            <p class="text-xs text-indigo-400 font-medium mb-1">Requested</p>
                            @if($req->is_day_off)
                                <p class="text-sm font-semibold text-orange-500">Day Off</p>
                            @else
                                <p class="text-sm font-semibold text-indigo-700">{{ $fmt12($req->requested_work_start_time) }}</p>
                                <p class="text-xs text-indigo-400">to {{ $fmt12($req->requested_work_end_time) }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Reason --}}
                    <div class="bg-amber-50 rounded-xl px-3 py-2 mb-3">
                        <p class="text-xs text-amber-600 font-medium mb-0.5">Reason</p>
                        <p class="text-sm text-gray-700">{{ $req->reason }}</p>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                        <button type="button" @click="showApprove = !showApprove; showReject = false"
                                :class="showApprove ? 'bg-green-600 text-white' : 'border border-green-500 text-green-600 hover:bg-green-50'"
                                class="flex-1 text-sm font-semibold py-2.5 rounded-xl transition">
                            Approve
                        </button>
                        <button type="button" @click="showReject = !showReject; showApprove = false"
                                :class="showReject ? 'bg-red-600 text-white' : 'border border-red-300 text-red-600 hover:bg-red-50'"
                                class="flex-1 text-sm font-semibold py-2.5 rounded-xl transition">
                            Reject
                        </button>
                    </div>

                    {{-- Approve form (with editable times) --}}
                    <div x-show="showApprove" x-cloak class="mt-3 border-t border-gray-100 pt-3">
                        <p class="text-xs font-semibold text-gray-500 mb-2">Approve with this schedule:</p>
                        <form method="POST" action="{{ route('staff.approvals.schedule.approve', $req) }}">
                            @csrf
                            <label class="flex items-center gap-2 mb-3 cursor-pointer">
                                <input type="checkbox" x-model="isDayOff" name="is_day_off" value="1"
                                       class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                <span class="text-sm text-gray-700">Mark as Day Off</span>
                            </label>
                            <div x-show="!isDayOff" x-cloak class="grid grid-cols-2 gap-2 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Start Time</label>
                                    <input type="time" name="approved_start_time" x-model="approvedStart" required
                                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">End Time</label>
                                    <input type="time" name="approved_end_time" x-model="approvedEnd" required
                                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>
                            @error('approved_start_time')
                                <p class="text-xs text-red-600 mb-2">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2.5 rounded-xl transition">
                                Confirm Approval
                            </button>
                        </form>
                    </div>

                    {{-- Reject form --}}
                    <div x-show="showReject" x-cloak class="mt-3 border-t border-gray-100 pt-3">
                        <form method="POST" action="{{ route('staff.approvals.schedule.reject', $req) }}">
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
    @endif

</x-staff-layout>
