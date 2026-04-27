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
        @php
            $data = $n->data;
            $type = $data['type'] ?? '';
            $message = $data['message'] ?? 'Notification';
            $isScheduleApproved = $type === 'schedule_change_approved';
            $isScheduleRejected = $type === 'schedule_change_rejected';
            $isScheduleChange = $isScheduleApproved || $isScheduleRejected;
            $scheduleUrl = $data['url'] ?? null;

            if ($isScheduleChange && empty($scheduleUrl) && !empty($data['date'])) {
                $scheduleUrl = route('staff.schedule', ['date' => $data['date']]) . '#schedule-date-' . $data['date'];
            }

            $cardClass = $n->read_at ? 'border-gray-100 bg-white' : 'border-green-200 bg-green-50';

            if ($isScheduleRejected) {
                $cardClass = 'border-red-200 bg-red-50';
            } elseif ($isScheduleApproved) {
                $cardClass = 'border-green-200 bg-green-50';
            }

            if ($type === 'announcement') {
                $subject = $data['subject'] ?? 'New announcement';
                $message = 'Announcement: ' . \Illuminate\Support\Str::limit($subject, 60);
            }
        @endphp
        <div class="rounded-xl border {{ $cardClass }} p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    @if($type === 'ot_approved' || $isScheduleApproved)
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    @elseif($type === 'ot_rejected' || $isScheduleRejected)
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                    @elseif($type === 'payslip_available')
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    @elseif($type === 'announcement')
                        <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592L5.436 14H4a2 2 0 01-2-2V9a2 2 0 012-2h1.436l2.147-5.832A1.76 1.76 0 0111 1.76v4.122zM19 7a3 3 0 010 6m2-8a6 6 0 010 10"/></svg>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    @if($isScheduleRejected)
                        <p class="text-sm font-semibold text-red-900">{{ $message }}</p>
                    @elseif($isScheduleApproved)
                        <p class="text-xs font-bold uppercase tracking-wide text-green-700">Approved</p>
                        <p class="text-sm font-semibold text-green-900 mt-0.5">{{ $message }}</p>
                    @else
                        <p class="text-sm text-gray-800">{{ $message }}</p>
                    @endif
                    @if(!empty($data['approver_name']))
                        <p class="text-xs text-gray-500 mt-0.5">By: {{ $data['approver_name'] }}</p>
                    @endif
                    @if(!$isScheduleRejected && (!empty($data['reason']) || !empty($data['rejection_reason'])))
                        <p class="text-xs text-red-600 mt-0.5">Reason: {{ $data['reason'] ?? $data['rejection_reason'] }}</p>
                    @endif
                    @if($isScheduleChange && $scheduleUrl)
                        <a href="{{ $scheduleUrl }}" class="text-xs {{ $isScheduleRejected ? 'text-red-700' : 'text-green-700' }} font-bold mt-2 inline-flex items-center gap-1">
                            View Schedule
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                    @if($type === 'payslip_available')
                        <a href="{{ route('staff.payslips.index') }}" class="text-xs text-indigo-600 font-medium mt-0.5 inline-block">View Payslips →</a>
                    @endif
                    @if($type === 'announcement' && !empty($data['announcement_id']))
                        <a href="{{ route('staff.announcements.show', $data['announcement_id']) }}" class="text-xs text-sky-600 font-medium mt-0.5 inline-block">View Announcement →</a>
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
