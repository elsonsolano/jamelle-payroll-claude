<x-staff-layout title="Dashboard">

    {{-- Welcome card --}}
    <div class="bg-green-600 text-white rounded-2xl p-4 mb-4">
        <p class="text-sm text-green-200">Welcome back,</p>
        <h2 class="text-xl font-bold">{{ Auth::user()->employee->first_name }}</h2>
        <p class="text-xs text-green-300 mt-0.5">{{ Auth::user()->employee->position }}</p>
    </div>

    {{-- Quick stats --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Pending OT</p>
            <p class="text-2xl font-bold text-amber-500">{{ $pendingOtCount }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Unread Notifications</p>
            <p class="text-2xl font-bold text-green-500">{{ $unreadCount }}</p>
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

    {{-- Today's DTR Card --}}
    @php
        $today      = today();
        $todayLabel = $today->format('D, M d Y');
        $events = [
            'time_in'  => 'Clock In',
            'am_out'   => 'Start Break',
            'pm_in'    => 'End Break',
            'time_out' => 'Clock Out',
        ];
        $fmt12 = fn($t) => $t ? date('g:i A', strtotime($t)) : null;

        // Determine the next loggable event
        $nextEvent = null;
        if ($todayDtr) {
            if (!$todayDtr->time_out) {
                // time_out is available once time_in is set
                if (!$todayDtr->time_in) {
                    $nextEvent = 'time_in';
                } elseif (!$todayDtr->am_out) {
                    $nextEvent = 'am_out';
                } elseif (!$todayDtr->pm_in) {
                    $nextEvent = 'pm_in';
                } else {
                    $nextEvent = 'time_out';
                }
            }
        } else {
            $nextEvent = 'time_in';
        }
    @endphp

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm mb-4"
         x-data="{ open: false, event: '', label: '', time: '' }">

        {{-- Card Header --}}
        <div class="flex items-center justify-between px-4 pt-4 pb-2">
            <div>
                <p class="text-xs text-gray-400">Today</p>
                <p class="text-sm font-semibold text-gray-800">{{ $todayLabel }}</p>
            </div>
            @if($todayDtr)
                <a href="{{ route('staff.dtr.edit', $todayDtr) }}"
                   class="flex items-center gap-1 text-xs text-indigo-600 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            @endif
        </div>

        {{-- Event Rows --}}
        <div class="px-4 pb-3 space-y-2">
            @foreach($events as $field => $label)
                @php
                    $loggedTime = $todayDtr?->{$field} ?? null;
                    $isLogged   = !is_null($loggedTime);
                    $isNext     = $nextEvent === $field;
                    // Locked: not logged, not next, and either no DTR or a prior required field is missing
                    $isLocked   = !$isLogged && !$isNext;
                @endphp

                <div @class([
                    'flex items-center justify-between rounded-xl px-3 py-2.5',
                    'bg-green-50'  => $isLogged,
                    'bg-indigo-50' => $isNext,
                    'bg-gray-50'   => $isLocked,
                ])>
                    <div class="flex items-center gap-2.5">
                        {{-- Status icon --}}
                        @if($isLogged)
                            <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        @elseif($isNext)
                            <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0">
                                <div class="w-2 h-2 rounded-full bg-white"></div>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex-shrink-0"></div>
                        @endif

                        <div>
                            <p @class([
                                'text-sm font-medium',
                                'text-green-700'  => $isLogged,
                                'text-indigo-700' => $isNext,
                                'text-gray-400'   => $isLocked,
                            ])>{{ $label }}</p>
                            @if($isLogged)
                                <p class="text-xs text-green-600">{{ $fmt12($loggedTime) }}</p>
                            @endif
                        </div>
                    </div>

                    @if($isNext)
                        <button type="button"
                                @click="event = '{{ $field }}'; label = '{{ $label }}'; time = ''; open = true"
                                class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-3 py-1.5 rounded-lg transition">
                            Tap to Log
                        </button>
                    @endif
                </div>
            @endforeach

            {{-- Total hours when fully logged --}}
            @if($todayDtr && $todayDtr->time_in && $todayDtr->time_out)
                <div class="pt-1 flex justify-end">
                    <span class="text-xs text-gray-500 font-medium">
                        Total: <span class="text-gray-800">{{ $todayDtr->total_hours }}h</span>
                    </span>
                </div>
            @endif
        </div>

        {{-- Log Event Bottom Sheet Modal --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex flex-col justify-end"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

            {{-- Sheet --}}
            <div class="relative bg-white rounded-t-2xl p-5 shadow-xl"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full">

                <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>

                <p class="text-xs text-gray-400 mb-0.5">{{ $todayLabel }}</p>
                <h3 class="text-lg font-bold text-gray-900 mb-4" x-text="label"></h3>

                <form method="POST" action="{{ route('staff.dtr.log-event') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $today->format('Y-m-d') }}">
                    <input type="hidden" name="event" :value="event">

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Time <span class="text-gray-400 font-normal">(from your timemark)</span>
                        </label>
                        <input type="time" name="time" x-model="time" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 text-lg font-semibold text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="open = false"
                                class="flex-1 border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl text-sm">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl text-sm transition">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- Recent DTRs --}}
    @if($recentDtrs->isNotEmpty())
    <h3 class="text-sm font-semibold text-gray-700 mb-2">Recent DTRs</h3>
    <div class="space-y-2">
        @foreach($recentDtrs as $dtr)
        <div class="bg-white rounded-xl border border-gray-100 p-3 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $dtr->date->format('D, M d Y') }}</p>
                <p class="text-xs text-gray-400">
                    {{ $dtr->time_in ? date('g:i A', strtotime($dtr->time_in)) : '--' }}
                    –
                    {{ $dtr->time_out ? date('g:i A', strtotime($dtr->time_out)) : '--' }}
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
                <a href="{{ route('staff.dtr.edit', $dtr) }}" class="text-gray-400 hover:text-green-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </a>
            </div>
        </div>
        @endforeach
        <a href="{{ route('staff.dtr.index') }}" class="block text-center text-green-600 text-sm mt-3 font-medium">View All DTRs</a>
    </div>
    @endif

</x-staff-layout>
