<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

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

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

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
                                {{ $cutoff->branch->name }} &middot;
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

    </div>

    {{-- Calendar --}}
    <div class="mt-6 bg-white rounded-xl border border-gray-200 overflow-hidden"
         x-data="calendarWidget({{ Js::from($calendarEvents) }})">
        <div class="flex flex-col lg:flex-row">

            {{-- Grid --}}
            <div class="flex-1 p-5">
                {{-- Month nav --}}
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800" x-text="monthName + ' ' + currentYear"></h2>
                    <div class="flex items-center gap-1">
                        <button @click="prevMonth()" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="goToToday()" class="px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-lg transition">Today</button>
                        <button @click="nextMonth()" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Weekday headers --}}
                <div class="grid grid-cols-7 mb-1">
                    <template x-for="wd in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']">
                        <div class="text-center text-xs font-medium text-gray-400 py-1" x-text="wd"></div>
                    </template>
                </div>

                {{-- Day cells --}}
                <div class="grid grid-cols-7 border border-gray-100 rounded-lg overflow-hidden divide-x divide-y divide-gray-100">
                    <template x-for="(dayObj, idx) in calendarDays" :key="idx">
                        <div @click="selectDay(dayObj)"
                             :class="{
                                 'bg-white p-1.5 min-h-14 flex flex-col': true,
                                 'opacity-40': !dayObj.currentMonth,
                                 'cursor-pointer hover:bg-indigo-50 transition': eventsForDay(dayObj).length > 0,
                             }">
                            <div class="flex justify-start">
                                <span :class="{
                                          'text-xs font-medium w-6 h-6 flex items-center justify-center rounded-full': true,
                                          'bg-indigo-600 text-white': isToday(dayObj),
                                          'text-gray-700': !isToday(dayObj),
                                      }"
                                      x-text="dayObj.day"></span>
                            </div>
                            <div class="flex flex-wrap gap-0.5 mt-auto pt-1">
                                <template x-for="(ev, ei) in eventsForDay(dayObj).slice(0, 4)" :key="ei">
                                    <span :class="{
                                        'w-1.5 h-1.5 rounded-full flex-shrink-0': true,
                                        'bg-red-500':    ev.eventType === 'holiday' && ev.type === 'regular',
                                        'bg-orange-400': ev.eventType === 'holiday' && ev.type === 'special_non_working',
                                        'bg-sky-400':    ev.eventType === 'holiday' && ev.type === 'special_working',
                                        'bg-purple-500': ev.eventType === 'birthday',
                                        'bg-amber-500':  ev.eventType === 'anniversary',
                                    }"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap gap-x-5 gap-y-1.5 mt-3">
                    <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>Regular Holiday</span>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-orange-400 flex-shrink-0"></span>Special Non-Working</span>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-sky-400 flex-shrink-0"></span>Special Working</span>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-purple-500 flex-shrink-0"></span>Birthday</span>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>Work Anniversary</span>
                </div>
            </div>

            {{-- Side panel --}}
            <div class="lg:w-72 border-t lg:border-t-0 lg:border-l border-gray-100 p-5 flex flex-col">

                {{-- Selected day --}}
                <template x-if="selectedDay">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800 text-sm" x-text="selectedDayLabel"></h3>
                            <button @click="selectedDay = null" class="text-gray-400 hover:text-gray-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(ev, ei) in selectedDay.events" :key="ei">
                                <div class="flex items-start gap-2.5">
                                    <span :class="{
                                        'w-2 h-2 rounded-full mt-1 flex-shrink-0': true,
                                        'bg-red-500':    ev.eventType === 'holiday' && ev.type === 'regular',
                                        'bg-orange-400': ev.eventType === 'holiday' && ev.type === 'special_non_working',
                                        'bg-sky-400':    ev.eventType === 'holiday' && ev.type === 'special_working',
                                        'bg-purple-500': ev.eventType === 'birthday',
                                        'bg-amber-500':  ev.eventType === 'anniversary',
                                    }"></span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800" x-text="ev.name"></p>
                                        <p class="text-xs text-gray-400 mt-0.5" x-text="eventLabel(ev)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- This month overview (default) --}}
                <template x-if="!selectedDay">
                    <div class="flex flex-col flex-1">
                        <h3 class="font-semibold text-gray-800 text-sm mb-3" x-text="monthName + ' Events'"></h3>
                        <template x-if="upcomingThisMonth.length === 0">
                            <p class="text-sm text-gray-400">No events this month.</p>
                        </template>
                        <div class="space-y-3 overflow-y-auto" style="max-height:320px">
                            <template x-for="(item, ii) in upcomingThisMonth" :key="ii">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 text-center flex-shrink-0">
                                        <p class="text-sm font-bold text-gray-800 leading-tight" x-text="item.day"></p>
                                        <p class="text-xs text-gray-400 leading-tight" x-text="shortMonthName"></p>
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
                </template>

            </div>
        </div>
    </div>

</x-app-layout>

@push('scripts')
<script>
function calendarWidget(data) {
    return {
        currentMonth: new Date().getMonth(),
        currentYear:  new Date().getFullYear(),
        selectedDay:  null,
        holidays:     data.holidays,
        birthdays:    data.birthdays,
        anniversaries: data.anniversaries,

        get monthName() {
            return new Date(this.currentYear, this.currentMonth)
                .toLocaleString('en-US', { month: 'long' });
        },
        get shortMonthName() {
            return new Date(this.currentYear, this.currentMonth)
                .toLocaleString('en-US', { month: 'short' });
        },
        get selectedDayLabel() {
            if (!this.selectedDay) return '';
            return new Date(this.selectedDay.year, this.selectedDay.month - 1, this.selectedDay.day)
                .toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        },

        prevMonth() {
            if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; }
            else this.currentMonth--;
            this.selectedDay = null;
        },
        nextMonth() {
            if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; }
            else this.currentMonth++;
            this.selectedDay = null;
        },
        goToToday() {
            this.currentMonth = new Date().getMonth();
            this.currentYear  = new Date().getFullYear();
            this.selectedDay  = null;
        },

        get calendarDays() {
            const firstDay      = new Date(this.currentYear, this.currentMonth, 1).getDay();
            const daysInMonth   = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
            const daysInPrev    = new Date(this.currentYear, this.currentMonth, 0).getDate();
            const days = [];

            for (let i = firstDay - 1; i >= 0; i--) {
                const m = this.currentMonth === 0 ? 12 : this.currentMonth;
                const y = this.currentMonth === 0 ? this.currentYear - 1 : this.currentYear;
                days.push({ day: daysInPrev - i, month: m, year: y, currentMonth: false });
            }
            for (let d = 1; d <= daysInMonth; d++) {
                days.push({ day: d, month: this.currentMonth + 1, year: this.currentYear, currentMonth: true });
            }
            const remaining = 42 - days.length;
            for (let d = 1; d <= remaining; d++) {
                const m = this.currentMonth === 11 ? 1 : this.currentMonth + 2;
                const y = this.currentMonth === 11 ? this.currentYear + 1 : this.currentYear;
                days.push({ day: d, month: m, year: y, currentMonth: false });
            }
            return days;
        },

        eventsForDay(dayObj) {
            const m = dayObj.month, d = dayObj.day, y = dayObj.year;
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

        isToday(dayObj) {
            const t = new Date();
            return dayObj.day === t.getDate()
                && dayObj.month === t.getMonth() + 1
                && dayObj.year  === t.getFullYear();
        },

        selectDay(dayObj) {
            const events = this.eventsForDay(dayObj);
            if (!events.length) { this.selectedDay = null; return; }
            this.selectedDay = { ...dayObj, events };
        },

        eventLabel(ev) {
            if (ev.eventType === 'holiday') {
                return { regular: 'Regular Holiday', special_non_working: 'Special Non-Working Holiday', special_working: 'Special Working Day' }[ev.type] ?? ev.type;
            }
            if (ev.eventType === 'birthday') return 'Birthday';
            if (ev.eventType === 'anniversary') {
                const s = ev.years === 1 ? '' : 's';
                return `${ev.years} year${s} with the company`;
            }
            return '';
        },

        get upcomingThisMonth() {
            const m = this.currentMonth + 1, y = this.currentYear;
            const daysInMonth = new Date(y, this.currentMonth + 1, 0).getDate();
            const events = [];
            for (let d = 1; d <= daysInMonth; d++) {
                this.eventsForDay({ day: d, month: m, year: y, currentMonth: true })
                    .forEach(ev => events.push({ ...ev, day: d }));
            }
            return events.sort((a, b) => a.day - b.day);
        },
    };
}
</script>
@endpush
