<x-app-layout>
    <x-slot name="title">Schedules</x-slot>

    <x-slot name="actions">
        <a href="{{ route('schedule-uploads.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Upload Schedule
        </a>
    </x-slot>

    @php $fmt12 = fn($t) => $t ? date('g:i A', strtotime($t)) : '—'; @endphp

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="flex border-b border-gray-200 mb-5 gap-1">
        <a href="{{ route('schedule-uploads.index', ['tab' => 'uploads']) }}"
           class="px-4 py-2.5 text-sm font-semibold border-b-2 transition
                  {{ $activeTab === 'uploads'
                      ? 'border-indigo-600 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Uploads
        </a>
        <a href="{{ route('schedule-uploads.index', ['tab' => 'change-requests']) }}"
           class="px-4 py-2.5 text-sm font-semibold border-b-2 transition flex items-center gap-2
                  {{ $activeTab === 'change-requests'
                      ? 'border-indigo-600 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            Change Requests
            @if($pendingChangeCount > 0)
                <span class="text-xs font-bold bg-amber-500 text-white rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1.5 leading-none">
                    {{ $pendingChangeCount > 99 ? '99+' : $pendingChangeCount }}
                </span>
            @endif
        </a>
    </div>

    {{-- ─── Uploads Tab ─── --}}
    @if($activeTab === 'uploads')
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
                                <td class="px-5 py-3">
                                    <span class="font-medium text-gray-900">{{ $upload->label ?? '—' }}</span>
                                    @if($upload->name)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $upload->name }}</p>
                                    @endif
                                </td>
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
                                    <div class="flex items-center justify-end gap-3">
                                        @if($upload->status === 'review')
                                            <a href="{{ route('schedule-uploads.review', $upload) }}"
                                               class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Review & Apply</a>
                                        @endif
                                        <form method="POST" action="{{ route('schedule-uploads.destroy', $upload) }}"
                                              onsubmit="return confirm('Delete this schedule upload?\n\n{{ addslashes($upload->label ?? 'This upload') }} will be removed. Any applied daily schedules will remain intact.')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="text-sm text-red-500 hover:text-red-700 font-medium transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
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
    @endif

    {{-- ─── Change Requests Tab ─── --}}
    @if($activeTab === 'change-requests')

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('schedule-uploads.index') }}" class="flex flex-wrap items-end gap-3 mb-4">
            <input type="hidden" name="tab" value="change-requests">

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
                <select name="branch" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 bg-white min-w-[160px]">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $filterBranch == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 bg-white min-w-[130px]">
                    <option value="pending"  {{ $filterStatus === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ $filterStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $filterStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="all"      {{ $filterStatus === 'all'      ? 'selected' : '' }}>All</option>
                </select>
            </div>

            @if($filterBranch || $filterStatus !== 'pending')
                <a href="{{ route('schedule-uploads.index', ['tab' => 'change-requests']) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 underline pb-2">Reset</a>
            @endif
        </form>

        @if($changeRequests->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-sm">No schedule change requests found.</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left">
                            <th class="px-5 py-3 font-semibold text-gray-600">Employee</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Date</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Current</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Requested</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Reason</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                        @foreach($changeRequests as $req)
                            @php
                                $isPending    = $req->status === 'pending';
                                $reqStart     = $req->requested_work_start_time
                                    ? \Carbon\Carbon::parse($req->requested_work_start_time)->format('H:i')
                                    : '';
                                $reqEnd       = $req->requested_work_end_time
                                    ? \Carbon\Carbon::parse($req->requested_work_end_time)->format('H:i')
                                    : '';
                            @endphp
                            <tbody x-data="{
                                    showApprove: false,
                                    showReject: false,
                                    isDayOff: {{ $req->is_day_off ? 'true' : 'false' }},
                                    approvedStart: '{{ $reqStart }}',
                                    approvedEnd: '{{ $reqEnd }}'
                                }"
                                class="border-b border-gray-100">
                            <tr class="hover:bg-gray-50 transition align-top">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-900">{{ $req->employee->full_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $req->employee->branch->name }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-700 whitespace-nowrap">
                                    {{ $req->date->format('D, M d Y') }}
                                </td>
                                <td class="px-5 py-3 text-gray-600">
                                    @if($req->is_current_day_off)
                                        <span class="text-orange-500 font-medium text-xs">Rest Day</span>
                                    @elseif($req->current_work_start_time)
                                        <span class="whitespace-nowrap">{{ $fmt12($req->current_work_start_time) }}</span><br>
                                        <span class="text-xs text-gray-400">to {{ $fmt12($req->current_work_end_time) }}</span>
                                    @else
                                        <span class="text-gray-400 text-xs">No schedule</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-indigo-700 font-medium">
                                    @if($req->is_day_off)
                                        <span class="text-orange-500 font-medium text-xs">Day Off</span>
                                    @else
                                        <span class="whitespace-nowrap">{{ $fmt12($req->requested_work_start_time) }}</span><br>
                                        <span class="text-xs text-indigo-400">to {{ $fmt12($req->requested_work_end_time) }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-600 max-w-[220px]">
                                    <p class="line-clamp-2 hover:line-clamp-none focus:line-clamp-none text-xs leading-relaxed cursor-default"
                                       tabindex="0">
                                        {{ $req->reason }}
                                    </p>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    @if($req->status === 'pending')
                                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">Pending</span>
                                    @elseif($req->status === 'approved')
                                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 text-green-700">Approved</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-red-100 text-red-700">Rejected</span>
                                    @else
                                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">Cancelled</span>
                                    @endif
                                    @if($req->reviewer && !$isPending)
                                        <p class="text-xs text-gray-400 mt-1">by {{ $req->reviewer->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $req->reviewed_at->format('M d, g:i A') }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    @if($isPending)
                                        <button type="button"
                                                @click="showApprove = !showApprove; showReject = false"
                                                :class="showApprove ? 'bg-green-600 text-white' : 'border border-green-500 text-green-600 hover:bg-green-50'"
                                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                            Approve
                                        </button>
                                        <button type="button"
                                                @click="showReject = !showReject; showApprove = false"
                                                :class="showReject ? 'bg-red-600 text-white' : 'border border-red-300 text-red-600 hover:bg-red-50'"
                                                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition ml-1">
                                            Reject
                                        </button>
                                    @elseif($req->status === 'approved')
                                        <div class="text-xs text-gray-500 text-right">
                                            @if($req->approved_start_time)
                                                <p class="font-medium text-green-700">{{ $fmt12($req->approved_start_time) }} – {{ $fmt12($req->approved_end_time) }}</p>
                                            @elseif($req->is_day_off)
                                                <p class="font-medium text-orange-600">Day Off granted</p>
                                            @endif
                                        </div>
                                    @elseif($req->status === 'rejected' && $req->rejection_reason)
                                        <p class="text-xs text-red-500 max-w-[160px] text-right line-clamp-2 hover:line-clamp-none focus:line-clamp-none cursor-default"
                                           tabindex="0">
                                            {{ $req->rejection_reason }}
                                        </p>
                                    @endif
                                </td>
                            </tr>

                            {{-- Inline approve/reject panel --}}
                            @if($isPending)
                                <tr x-show="showApprove || showReject" x-cloak
                                    class="bg-gray-50">
                                    <td colspan="7" class="px-5 py-4">

                                        {{-- Approve form --}}
                                        <div x-show="showApprove" x-cloak>
                                            <p class="text-xs font-semibold text-gray-500 mb-3">Approve with this schedule:</p>
                                            <form method="POST"
                                                  action="{{ route('admin.schedule-change-requests.approve', $req) }}"
                                                  class="flex flex-wrap items-end gap-4">
                                                @csrf
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" x-model="isDayOff" name="is_day_off" value="1"
                                                           class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                                    <span class="text-sm text-gray-700">Mark as Day Off</span>
                                                </label>
                                                <div x-show="!isDayOff" x-cloak class="flex items-end gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">Start Time</label>
                                                        <input type="time" name="approved_start_time" x-model="approvedStart" required
                                                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-500 mb-1">End Time</label>
                                                        <input type="time" name="approved_end_time" x-model="approvedEnd" required
                                                               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                                                    </div>
                                                </div>
                                                <button type="submit"
                                                        class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                                    Confirm Approval
                                                </button>
                                                <button type="button" @click="showApprove = false"
                                                        class="text-sm text-gray-400 hover:text-gray-600 transition">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>

                                        {{-- Reject form --}}
                                        <div x-show="showReject" x-cloak>
                                            <p class="text-xs font-semibold text-gray-500 mb-3">Reject this request:</p>
                                            <form method="POST"
                                                  action="{{ route('admin.schedule-change-requests.reject', $req) }}"
                                                  class="flex flex-wrap items-end gap-4">
                                                @csrf
                                                <div class="flex-1 min-w-[240px]">
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Reason (optional)</label>
                                                    <input type="text" name="reason" placeholder="e.g. Schedule already finalized"
                                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400">
                                                </div>
                                                <button type="submit"
                                                        class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                                    Confirm Rejection
                                                </button>
                                                <button type="button" @click="showReject = false"
                                                        class="text-sm text-gray-400 hover:text-gray-600 transition">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>

                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        @endforeach
                </table>

                @if($changeRequests->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">{{ $changeRequests->links() }}</div>
                @endif
            </div>
        @endif
    @endif
</x-app-layout>
