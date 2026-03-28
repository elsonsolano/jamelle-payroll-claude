<x-staff-layout>
    <x-slot name="title">My Schedule</x-slot>

    <div class="space-y-5">

        {{-- Week views --}}
        @foreach($weeks as $weekIndex => $week)
            @php
                $weekDays  = $week->values();
                $firstDate = $weekDays->first()['date'];
                $lastDate  = $weekDays->last()['date'];
                $isCurrentWeek = $firstDate->lte(today()) && $lastDate->gte(today());
            @endphp

            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-700">
                        {{ $firstDate->format('M d') }} – {{ $lastDate->format('M d, Y') }}
                    </p>
                    @if($isCurrentWeek)
                        <span class="text-xs font-semibold bg-green-100 text-green-700 px-2.5 py-0.5 rounded-full">This Week</span>
                    @elseif($weekIndex === 1)
                        <span class="text-xs font-semibold bg-gray-100 text-gray-500 px-2.5 py-0.5 rounded-full">Next Week</span>
                    @endif
                </div>

                <div class="grid grid-cols-7 divide-x divide-gray-100">
                    @foreach($weekDays as $day)
                        @php
                            $isToday   = $day['date']->isToday();
                            $isPast    = $day['date']->isPast() && !$isToday;
                            $isOff     = $day['is_day_off'];
                            $noSched   = $day['source'] === 'none';
                        @endphp
                        <div @class([
                            'flex flex-col items-center py-3 px-1 text-center',
                            'bg-green-50'  => $isToday,
                            'opacity-40'   => $isPast,
                        ])>
                            {{-- Day name --}}
                            <p @class([
                                'text-xs font-semibold mb-1',
                                'text-green-600' => $isToday,
                                'text-gray-400'  => !$isToday,
                            ])>{{ $day['date']->format('D') }}</p>

                            {{-- Date number --}}
                            <div @class([
                                'w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold mb-2',
                                'bg-green-500 text-white' => $isToday,
                                'text-gray-700'           => !$isToday && !$isPast,
                            ])>{{ $day['date']->format('j') }}</div>

                            {{-- Shift info --}}
                            @if($noSched)
                                <span class="text-gray-300 text-xs">—</span>
                            @elseif($isOff)
                                <span class="text-xs font-semibold text-orange-500">OFF</span>
                            @else
                                <p class="text-xs font-medium text-gray-700 leading-tight">
                                    {{ $day['start'] ? \Carbon\Carbon::parse($day['start'])->format('g:i') : '—' }}
                                </p>
                                <p class="text-xs text-gray-400 leading-tight">
                                    {{ $day['end'] ? \Carbon\Carbon::parse($day['end'])->format('g A') : '' }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Upcoming detail list (next 30 days) --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-700">Upcoming Schedule</p>
            </div>
            <div class="divide-y divide-gray-100">
                @php
                    $upcoming = collect($days)->filter(fn($d) => !$d['date']->isPast() || $d['date']->isToday())->take(30);
                @endphp
                @foreach($upcoming as $day)
                    @php
                        $isToday = $day['date']->isToday();
                        $isOff   = $day['is_day_off'];
                        $noSched = $day['source'] === 'none';
                    @endphp
                    <div @class([
                        'flex items-center px-4 py-3 gap-4',
                        'bg-green-50' => $isToday,
                    ])>
                        {{-- Date --}}
                        <div class="w-12 text-center shrink-0">
                            <p class="text-xs font-semibold {{ $isToday ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $day['date']->format('D') }}
                            </p>
                            <p class="text-lg font-bold {{ $isToday ? 'text-green-600' : 'text-gray-800' }}">
                                {{ $day['date']->format('j') }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $day['date']->format('M') }}</p>
                        </div>

                        {{-- Shift --}}
                        <div class="flex-1">
                            @if($noSched)
                                <p class="text-sm text-gray-400">No schedule set</p>
                            @elseif($isOff)
                                <p class="text-sm font-semibold text-orange-500">Rest Day</p>
                            @else
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $day['start'] ? \Carbon\Carbon::parse($day['start'])->format('g:i A') : '—' }}
                                    –
                                    {{ $day['end'] ? \Carbon\Carbon::parse($day['end'])->format('g:i A') : '—' }}
                                </p>
                                @if($day['branch'])
                                    <p class="text-xs text-indigo-500 mt-0.5">{{ $day['branch'] }}</p>
                                @endif
                                @if($day['notes'])
                                    <p class="text-xs text-amber-500 mt-0.5">{{ $day['notes'] }}</p>
                                @endif
                            @endif
                        </div>

                        {{-- Today badge --}}
                        @if($isToday)
                            <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded-full shrink-0">Today</span>
                        @endif

                        {{-- Source indicator --}}
                        @if(!$noSched && $day['source'] === 'daily')
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0" title="Date-specific schedule"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Legend --}}
        <div class="flex items-center gap-4 px-1 text-xs text-gray-400">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 inline-block"></span>
                Date-specific schedule
            </span>
            <span class="flex items-center gap-1.5">
                <span class="font-semibold text-orange-400">OFF</span>
                Rest day
            </span>
        </div>

    </div>
</x-staff-layout>
