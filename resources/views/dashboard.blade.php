<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    {{-- Probation ending soon banner (super admin only) --}}
    @if(auth()->user()->isSuperAdmin() && $probationEndingSoon->isNotEmpty())
        <div class="mb-6 space-y-2">
            @foreach($probationEndingSoon as $emp)
                @php
                    $daysLeft = today()->diffInDays($emp->probation_end_date);
                    $daysLabel = $daysLeft === 0 ? 'today' : 'in ' . $daysLeft . ' ' . ($daysLeft === 1 ? 'day' : 'days');
                @endphp
                <div class="flex items-center gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>
                        <a href="{{ route('employees.show', $emp) }}"
                           class="font-semibold hover:underline">{{ $emp->full_name }}</a>
                        probation status will end {{ $daysLabel }}.
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Active Employees</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalEmployees }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Branches</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalBranches }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Active Cutoffs</p>
                <p class="text-2xl font-bold text-gray-900">{{ $activeCutoffs }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-sky-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">DTR Records Today</p>
                <p class="text-2xl font-bold text-gray-900">{{ $dtrToday }}</p>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Employees by Branch --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Employees by Branch</h2>
            </div>
            <div class="p-5 space-y-4">
                @forelse($employeesByBranch as $branch)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $branch->name }}</span>
                            <span class="text-gray-500">{{ $branch->employees_count }} employees</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php
                                $max = $employeesByBranch->max('employees_count') ?: 1;
                                $pct = $max > 0 ? round(($branch->employees_count / $max) * 100) : 0;
                            @endphp
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No branches found.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Payroll Cutoffs --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Recent Payroll Cutoffs</h2>
                <a href="{{ route('payroll.cutoffs.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentCutoffs as $cutoff)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $cutoff->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $cutoff->branch?->name ?? '—' }} &middot;
                                {{ \Carbon\Carbon::parse($cutoff->start_date)->format('M d') }} –
                                {{ \Carbon\Carbon::parse($cutoff->end_date)->format('M d, Y') }}
                            </p>
                        </div>
                        <span @class([
                            'text-xs font-medium px-2 py-1 rounded-full',
                            'bg-gray-100 text-gray-600'   => $cutoff->status === 'draft',
                            'bg-amber-100 text-amber-700' => $cutoff->status === 'processing',
                            'bg-green-100 text-green-700' => $cutoff->status === 'finalized',
                        ])>
                            {{ ucfirst($cutoff->status) }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-sm text-gray-400">No payroll cutoffs yet.</div>
                @endforelse
            </div>
        </div>


        {{-- Month Events --}}
        <script>
        function monthEvents(data) {
            return {
                currentMonth: new Date().getMonth(),
                currentYear:  new Date().getFullYear(),
                holidays:     data.holidays,
                birthdays:    data.birthdays,
                anniversaries: data.anniversaries,

                get monthName() {
                    return new Date(this.currentYear, this.currentMonth)
                        .toLocaleString('en-US', { month: 'long' });
                },

                prevMonth() {
                    if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; }
                    else this.currentMonth--;
                },
                nextMonth() {
                    if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; }
                    else this.currentMonth++;
                },
                goToToday() {
                    this.currentMonth = new Date().getMonth();
                    this.currentYear  = new Date().getFullYear();
                },

                eventsForDay(d) {
                    const m = this.currentMonth + 1, y = this.currentYear;
                    const pad = n => String(n).padStart(2, '0');
                    const fullDate = `${y}-${pad(m)}-${pad(d)}`;
                    const events = [];
                    this.holidays.forEach(h => {
                        if (h.date === fullDate) events.push({ ...h, eventType: 'holiday' });
                    });
                    this.birthdays.forEach(b => {
                        if (b.month === m && b.day === d) events.push({ ...b, eventType: 'birthday' });
                    });
                    this.anniversaries.forEach(a => {
                        if (a.month === m && a.day === d && a.hire_year < y)
                            events.push({ ...a, eventType: 'anniversary', years: y - a.hire_year });
                    });
                    return events;
                },

                eventLabel(ev) {
                    if (ev.eventType === 'holiday') {
                        return { regular: 'Regular Holiday', special_non_working: 'Special Non-Working Holiday', special_working: 'Special Working Day' }[ev.type] ?? ev.type;
                    }
                    if (ev.eventType === 'birthday') return 'Birthday';
                    if (ev.eventType === 'anniversary') {
                        return `${ev.years} year${ev.years === 1 ? '' : 's'} with the company`;
                    }
                    return '';
                },

                get events() {
                    const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                    const list = [];
                    for (let d = 1; d <= daysInMonth; d++) {
                        this.eventsForDay(d).forEach(ev => list.push({ ...ev, day: d }));
                    }
                    return list.sort((a, b) => a.day - b.day);
                },
            };
        }
        </script>
        <div class="bg-white rounded-xl border border-gray-200 flex flex-col"
             x-data="monthEvents({{ Js::from($calendarEvents) }})">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <h2 class="font-semibold text-gray-800" x-text="monthName + ' ' + currentYear"></h2>
                <div class="flex items-center gap-1">
                    <button @click="prevMonth()" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click="goToToday()" class="px-2 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100 rounded-lg transition">Today</button>
                    <button @click="nextMonth()" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-5 overflow-y-auto flex-1" style="max-height:320px">
                <template x-if="events.length === 0">
                    <p class="text-sm text-gray-400">No events this month.</p>
                </template>
                <div class="space-y-3">
                    <template x-for="(item, i) in events" :key="i">
                        <div class="flex items-start gap-3">
                            <div class="w-7 text-center flex-shrink-0">
                                <p class="text-sm font-bold text-gray-800 leading-tight" x-text="item.day"></p>
                            </div>
                            <span :class="{
                                'w-2 h-2 rounded-full mt-1 flex-shrink-0': true,
                                'bg-red-500':    item.eventType === 'holiday' && item.type === 'regular',
                                'bg-orange-400': item.eventType === 'holiday' && item.type === 'special_non_working',
                                'bg-sky-400':    item.eventType === 'holiday' && item.type === 'special_working',
                                'bg-purple-500': item.eventType === 'birthday',
                                'bg-amber-500':  item.eventType === 'anniversary',
                            }"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" x-text="item.name"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="eventLabel(item)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    {{-- Branch Schedule Grid — Next 15 Days --}}
    <div class="mt-8">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Branch Schedules — Next 15 Days</h2>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            @foreach($scheduleGrid['branches'] as $branchData)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                {{-- Card Header --}}
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h3 class="font-semibold text-gray-700 text-sm">{{ $branchData['branch'] }}</h3>
                    <span class="text-xs text-gray-400">{{ count($branchData['employees']) }} employee(s)</span>
                </div>

                {{-- Scrollable Grid --}}
                <div class="overflow-auto" style="max-height: 300px;">
                    @if(empty($branchData['employees']))
                        <p class="px-5 py-4 text-sm text-gray-400">No active employees.</p>
                    @else
                    <table class="min-w-max text-xs border-collapse w-full">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="sticky left-0 z-20 bg-gray-50 px-4 py-2 text-left font-medium text-gray-500 border-b border-r border-gray-200 min-w-[150px] whitespace-nowrap">
                                    Employee
                                </th>
                                @foreach($scheduleGrid['dates'] as $dateStr)
                                @php $d = \Carbon\Carbon::parse($dateStr); @endphp
                                <th class="px-2 py-2 text-center font-medium border-b border-gray-200 min-w-[78px] whitespace-nowrap
                                    {{ $d->isToday() ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-50 text-gray-500' }}">
                                    <div class="text-[10px] font-normal">{{ $d->format('D') }}</div>
                                    <div>{{ $d->format('M d') }}</div>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchData['employees'] as $empRow)
                            <tr class="border-b border-gray-100 hover:bg-gray-50/60 transition">
                                <td class="sticky left-0 bg-white px-4 py-2 font-medium text-gray-700 border-r border-gray-200 whitespace-nowrap z-10">
                                    {{ $empRow['name'] }}
                                </td>
                                @foreach($scheduleGrid['dates'] as $dateStr)
                                @php
                                    $day = $empRow['days'][$dateStr];
                                    $isToday = \Carbon\Carbon::parse($dateStr)->isToday();
                                @endphp
                                <td class="px-1 py-1.5 text-center align-middle {{ $isToday ? 'bg-indigo-50/30' : '' }}">
                                    @if($day['status'] === 'working')
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="px-1.5 py-0.5 bg-green-100 text-green-700 rounded font-medium whitespace-nowrap text-[10px]">
                                                @if($day['start'] && $day['end'])
                                                    {{ \Carbon\Carbon::parse($day['start'])->format('G:i') }}–{{ \Carbon\Carbon::parse($day['end'])->format('G:i') }}
                                                @else
                                                    Working
                                                @endif
                                            </span>
                                            @if($day['notes'])
                                                <span class="px-1 py-0.5 bg-amber-100 text-amber-700 rounded text-[9px] whitespace-nowrap">
                                                    {{ $day['notes'] }}
                                                </span>
                                            @endif
                                        </div>
                                    @elseif($day['status'] === 'off')
                                        <span class="text-gray-300 text-base leading-none">—</span>
                                    @else
                                        <span class="text-gray-200 text-base leading-none">·</span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

</x-app-layout>
