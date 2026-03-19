<x-app-layout>
    <x-slot name="title">Holidays</x-slot>

    @php
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();
        $daysInMonth = $currentDate->daysInMonth;
        $firstDayOfWeek = $currentDate->copy()->startOfMonth()->dayOfWeek; // 0=Sun
        $today = \Carbon\Carbon::today();
    @endphp

    {{-- Legend --}}
    <x-slot name="actions">
        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Regular Holiday</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span> Special Non-Working</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Special Working</span>
        </div>
    </x-slot>

    <div
        x-data="{
            showModal: false,
            mode: 'add',
            selectedDate: '',
            selectedDateLabel: '',
            holidayId: null,
            holidayName: '',
            holidayType: 'regular',

            openAdd(dateStr, label) {
                this.mode = 'add';
                this.selectedDate = dateStr;
                this.selectedDateLabel = label;
                this.holidayId = null;
                this.holidayName = '';
                this.holidayType = 'regular';
                this.showModal = true;
            },
            openEdit(id, name, type, dateStr, label) {
                this.mode = 'edit';
                this.selectedDate = dateStr;
                this.selectedDateLabel = label;
                this.holidayId = id;
                this.holidayName = name;
                this.holidayType = type;
                this.showModal = true;
            },
        }"
        @keydown.escape.window="showModal = false"
    >

        {{-- Month navigation --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('holidays.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
               class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                ← Prev
            </a>
            <h2 class="text-lg font-bold text-gray-800">{{ $currentDate->format('F Y') }}</h2>
            <a href="{{ route('holidays.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
               class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Next →
            </a>
        </div>

        {{-- Calendar grid --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

            {{-- Day headers --}}
            <div class="grid grid-cols-7 border-b border-gray-200">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                    <div class="py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider
                        {{ in_array($day, ['Sun','Sat']) ? 'bg-gray-50' : '' }}">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Day cells --}}
            <div class="grid grid-cols-7">

                {{-- Empty cells before first day --}}
                @for($i = 0; $i < $firstDayOfWeek; $i++)
                    <div class="min-h-[90px] border-b border-r border-gray-100 bg-gray-50/50"></div>
                @endfor

                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date     = $currentDate->copy()->day($day);
                        $dateStr  = $date->toDateString();
                        $label    = $date->format('F d, Y');
                        $isToday  = $date->isSameDay($today);
                        $isWeekend = $date->isWeekend();
                        $holiday  = $holidays->get($day);
                        $col      = ($firstDayOfWeek + $day - 1) % 7; // 0=Sun, 6=Sat
                    @endphp

                    <div
                        class="min-h-[90px] border-b border-r border-gray-100 p-2 cursor-pointer transition
                            {{ $isWeekend ? 'bg-gray-50/70' : 'hover:bg-indigo-50/40' }}
                            {{ $holiday ? 'hover:brightness-95' : '' }}"
                        @click="
                            @if($holiday)
                                openEdit({{ $holiday->id }}, '{{ addslashes($holiday->name) }}', '{{ $holiday->type }}', '{{ $dateStr }}', '{{ $label }}')
                            @else
                                openAdd('{{ $dateStr }}', '{{ $label }}')
                            @endif
                        "
                    >
                        {{-- Day number --}}
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium
                                {{ $isToday ? 'w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs' : ($isWeekend ? 'text-gray-400' : 'text-gray-700') }}">
                                {{ $day }}
                            </span>
                        </div>

                        {{-- Holiday badge --}}
                        @if($holiday)
                            @php
                                $badgeColor = match($holiday->type) {
                                    'regular'             => 'bg-red-100 text-red-700 border border-red-200',
                                    'special_non_working' => 'bg-orange-100 text-orange-700 border border-orange-200',
                                    'special_working'     => 'bg-blue-100 text-blue-700 border border-blue-200',
                                };
                            @endphp
                            <div class="mt-1 px-1.5 py-0.5 rounded text-xs font-medium leading-tight {{ $badgeColor }}">
                                {{ $holiday->name }}
                            </div>
                        @endif
                    </div>
                @endfor

                {{-- Empty cells after last day --}}
                @php $lastCol = ($firstDayOfWeek + $daysInMonth - 1) % 7; @endphp
                @if($lastCol < 6)
                    @for($i = $lastCol + 1; $i <= 6; $i++)
                        <div class="min-h-[90px] border-b border-r border-gray-100 bg-gray-50/50"></div>
                    @endfor
                @endif

            </div>
        </div>

        {{-- Modal --}}
        <div
            x-show="showModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>

            {{-- Panel --}}
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.stop>

                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900" x-text="mode === 'add' ? 'Add Holiday' : 'Edit Holiday'"></h3>
                        <p class="text-sm text-gray-500 mt-0.5" x-text="selectedDateLabel"></p>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Add form --}}
                <form
                    x-show="mode === 'add'"
                    method="POST"
                    action="{{ route('holidays.store') }}"
                >
                    @csrf
                    <input type="hidden" name="date" :value="selectedDate">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Holiday Name</label>
                            <input type="text" name="name" x-model="holidayName" required
                                   placeholder="e.g. Christmas Day"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select name="type" x-model="holidayType"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="regular">Regular Holiday (200% if worked, 100% if not)</option>
                                <option value="special_non_working">Special Non-Working (130% if worked, no pay if not)</option>
                                <option value="special_working">Special Working (100%, treated as regular day)</option>
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Add Holiday
                        </button>
                    </div>
                </form>

                {{-- Edit form --}}
                <div x-show="mode === 'edit'" class="space-y-4">
                    <form method="POST" :action="`/holidays/${holidayId}`">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Holiday Name</label>
                                <input type="text" name="name" x-model="holidayName" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select name="type" x-model="holidayType"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="regular">Regular Holiday (200% if worked, 100% if not)</option>
                                    <option value="special_non_working">Special Non-Working (130% if worked, no pay if not)</option>
                                    <option value="special_working">Special Working (100%, treated as regular day)</option>
                                </select>
                            </div>
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                Save Changes
                            </button>
                        </div>
                    </form>

                    <form method="POST" :action="`/holidays/${holidayId}`"
                          @submit.prevent="if(confirm('Remove this holiday?')) $el.submit()">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium rounded-lg transition">
                            Remove Holiday
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </div>

</x-app-layout>
