<x-app-layout>
    <x-slot name="title">DTR Records</x-slot>

    <script>
    function employeeSelect(employees, initialId) {
        return {
            employees,
            search: '',
            open: false,
            selectedId: initialId ? String(initialId) : '',
            selectedName: '',
            init() {
                if (this.selectedId) {
                    const found = this.employees.find(e => String(e.id) === this.selectedId);
                    this.selectedName = found ? found.name : '';
                }
            },
            get filtered() {
                if (!this.search) return this.employees;
                const q = this.search.toLowerCase();
                return this.employees.filter(e => e.name.toLowerCase().includes(q));
            },
            select(id, name) {
                this.selectedId = id ? String(id) : '';
                this.selectedName = name;
                this.open = false;
                this.search = '';
            },
        };
    }
    </script>

    {{-- Filters --}}
    @php
        $todayStr     = today()->toDateString();
        $yesterdayStr = today()->subDay()->toDateString();
        $last7Str     = today()->subDays(6)->toDateString();
        $last15Str    = today()->subDays(14)->toDateString();
        $baseParams   = array_filter(['branch_id' => request('branch_id'), 'employee_id' => request('employee_id')]);
        $todayLink    = route('dtr.index', array_merge($baseParams, ['date_from' => $todayStr,     'date_to' => $todayStr]));
        $yestLink     = route('dtr.index', array_merge($baseParams, ['date_from' => $yesterdayStr, 'date_to' => $yesterdayStr]));
        $last7Link    = route('dtr.index', array_merge($baseParams, ['date_from' => $last7Str,     'date_to' => $todayStr]));
        $last15Link   = route('dtr.index', array_merge($baseParams, ['date_from' => $last15Str,    'date_to' => $todayStr]));
        $isToday      = request('date_from') === $todayStr     && request('date_to') === $todayStr     && !request('cutoff_id');
        $isYesterday  = request('date_from') === $yesterdayStr && request('date_to') === $yesterdayStr && !request('cutoff_id');
        $isLast7      = request('date_from') === $last7Str     && request('date_to') === $todayStr     && !request('cutoff_id');
        $isLast15     = request('date_from') === $last15Str    && request('date_to') === $todayStr     && !request('cutoff_id');
    @endphp

    <form method="GET" action="{{ route('dtr.index') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">

        {{-- Quick shortcuts --}}
        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
            <span class="text-xs font-medium text-gray-400">Quick:</span>
            <a href="{{ $todayLink }}"
               class="px-3 py-1 rounded-full text-xs font-medium border transition {{ $isToday ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600' }}">
                Today
            </a>
            <a href="{{ $yestLink }}"
               class="px-3 py-1 rounded-full text-xs font-medium border transition {{ $isYesterday ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600' }}">
                Yesterday
            </a>
            <a href="{{ $last7Link }}"
               class="px-3 py-1 rounded-full text-xs font-medium border transition {{ $isLast7 ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600' }}">
                Last 7 Days
            </a>
            <a href="{{ $last15Link }}"
               class="px-3 py-1 rounded-full text-xs font-medium border transition {{ $isLast15 ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600' }}">
                Last 15 Days
            </a>
        </div>

        {{-- Row 1: Branch + Employee --}}
        <div class="flex flex-wrap gap-3 items-end mb-3">

            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500">Branch</label>
                <select name="branch_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1"
                 x-data="employeeSelect({{ json_encode($employees->map(fn($e) => ['id' => $e->id, 'name' => $e->full_name])->values()) }}, '{{ request('employee_id') }}')"
                 @click.outside="open = false">
                <label class="text-xs font-medium text-gray-500">Employee</label>
                <input type="hidden" name="employee_id" :value="selectedId">
                <div class="relative">
                    <button type="button"
                            @click="open = !open; if (open) $nextTick(() => $refs.searchInput.focus())"
                            class="w-48 flex items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm text-left hover:border-gray-400 transition">
                        <span class="truncate" :class="selectedId ? 'text-gray-800' : 'text-gray-400'"
                              x-text="selectedId ? selectedName : 'All Employees'"></span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak
                         class="absolute z-20 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg">
                        <div class="p-2 border-b border-gray-100">
                            <input type="text" x-model="search" x-ref="searchInput"
                                   @click.stop @keydown.escape="open = false"
                                   placeholder="Search employee…"
                                   class="w-full rounded-md border-gray-300 text-sm px-2.5 py-1.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <ul class="max-h-60 overflow-y-auto py-1">
                            <li @click="select('', 'All Employees')"
                                class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-50"
                                :class="!selectedId ? 'font-medium text-indigo-600' : 'text-gray-500'">
                                All Employees
                            </li>
                            <template x-for="emp in filtered" :key="emp.id">
                                <li @click="select(emp.id, emp.name)"
                                    class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 truncate"
                                    :class="selectedId == emp.id ? 'font-medium text-indigo-600 bg-indigo-50' : 'text-gray-700'"
                                    x-text="emp.name"></li>
                            </template>
                            <li x-show="filtered.length === 0"
                                class="px-3 py-2 text-sm text-gray-400 italic">No employees found</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        {{-- Row 2: Date range OR Cutoff --}}
        <div class="flex flex-wrap items-end gap-3 mb-4">

            <div class="flex items-end gap-2">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <span class="text-gray-400 text-sm pb-2">→</span>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="flex items-end gap-2">
                <span class="text-xs text-gray-400 italic pb-2.5">or</span>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Cutoff Period</label>
                    <select name="cutoff_id"
                            class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-52">
                        <option value="">— select cutoff —</option>
                        @foreach($cutoffs as $cutoff)
                            <option value="{{ $cutoff->id }}" @selected(request('cutoff_id') == $cutoff->id)>
                                {{ $cutoff->name }} ({{ $cutoff->branch->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>

        {{-- Row 3: Filters + actions --}}
        <div class="flex items-center gap-4 pt-3 border-t border-gray-100">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                <input type="checkbox" name="pending_ot" value="1"
                       @checked(request()->boolean('pending_ot'))
                       onchange="this.form.submit()"
                       class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                Pending OT only
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                <input type="checkbox" name="pending_dtr" value="1"
                       @checked(request()->boolean('pending_dtr'))
                       onchange="this.form.submit()"
                       class="rounded border-gray-300 text-rose-500 focus:ring-rose-400">
                Pending DTR only
            </label>
            <button type="submit"
                    class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg transition">
                Filter
            </button>
            @if(request()->hasAny(['employee_id','branch_id','cutoff_id','date_from','date_to','pending_ot']))
                <a href="{{ route('dtr.index') }}" class="px-2 py-2 text-sm text-gray-500 hover:text-gray-800 transition">Clear filters</a>
            @endif
        </div>

    </form>

    {{-- Reject modal (Alpine) --}}
    <div x-data="{ open: false, action: '' }"
         x-on:open-reject.window="open = true; action = $event.detail.action"
         x-cloak>
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6" @click.outside="open = false">
                <h2 class="text-base font-semibold text-gray-800 mb-3">Reject OT Request</h2>
                <form :action="action" method="POST">
                    @csrf
                    <label class="text-sm text-gray-600 block mb-1">Reason <span class="text-gray-400">(optional)</span></label>
                    <textarea name="reason" rows="3"
                              class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-red-400 focus:border-red-400"
                              placeholder="Enter rejection reason…"></textarea>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" @click="open = false"
                                class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $dtrs->total() }} record(s) found</p>
            <div class="flex items-center gap-2">
                <a href="{{ route('dtr.export', request()->query()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export PDF
                </a>
                <a href="{{ route('dtr.export-excel', request()->query()) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left">
                        <th class="px-4 py-3 font-semibold text-gray-600">Employee</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Time In</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Start Break</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">End Break</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Time Out</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Hours</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Billable</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">OT</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Late</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">UT</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Status</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">OT Approval</th>
                        <th class="px-4 py-3 font-semibold text-gray-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dtrs as $dtr)
                        <tr @class([
                            'hover:bg-gray-50 transition',
                            'bg-amber-50 hover:bg-amber-100' => $dtr->ot_status === 'pending',
                        ])>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('employees.show', $dtr->employee) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">{{ $dtr->employee->full_name }}</a>
                                    @if($dtr->notes)
                                        <span data-tippy-content="{{ $dtr->notes }}" class="cursor-default text-gray-400 hover:text-gray-600" style="line-height:1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-6 4h4M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400">{{ $dtr->employee->branch->name }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                <p class="font-medium">{{ $dtr->date->format('M d, Y') }}</p>
                                <p class="text-xs text-gray-400">{{ $dtr->date->format('l') }}
                                    @if($dtr->is_rest_day)
                                        <span class="text-amber-600 font-medium">· Rest Day</span>
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-700">
                                {{ $dtr->time_in ? \Carbon\Carbon::parse($dtr->time_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">
                                {{ $dtr->am_out ? \Carbon\Carbon::parse($dtr->am_out)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">
                                {{ $dtr->pm_in ? \Carbon\Carbon::parse($dtr->pm_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-700">
                                {{ $dtr->time_out ? \Carbon\Carbon::parse($dtr->time_out)->format('h:i A') : '—' }}
                                @if($dtr->time_in && $dtr->time_out && \Carbon\Carbon::createFromTimeString($dtr->time_out)->lte(\Carbon\Carbon::createFromTimeString($dtr->time_in)))
                                    <span class="block text-orange-500 font-semibold text-xs">+1 day</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-gray-800">{{ number_format($dtr->total_hours, 2) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($dtr->time_in)
                                    @php $billable = min((float) $dtr->total_hours, 8.0); @endphp
                                    <span class="font-medium {{ $billable < $dtr->total_hours ? 'text-amber-600' : 'text-gray-800' }}">
                                        {{ number_format($billable, 2) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($dtr->ot_status === 'pending')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        +{{ number_format($dtr->overtime_hours, 2) }}h
                                    </span>
                                @elseif($dtr->ot_status === 'approved' && $dtr->overtime_hours > 0)
                                    <span class="text-xs font-medium text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                        +{{ number_format($dtr->overtime_hours, 2) }}h
                                    </span>
                                @elseif($dtr->ot_status === 'rejected')
                                    <span class="text-xs font-medium text-red-500 bg-red-50 px-2 py-0.5 rounded-full line-through">
                                        rejected
                                    </span>
                                @elseif($dtr->overtime_hours > 0)
                                    <span class="text-xs font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">
                                        +{{ number_format($dtr->overtime_hours, 2) }}h
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($dtr->late_mins > 0)
                                    <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded-full">
                                        {{ $dtr->late_mins }}m
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($dtr->undertime_mins > 0)
                                    <span class="text-xs font-medium text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full">
                                        {{ $dtr->undertime_mins }}m
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    data-dtr-id="{{ $dtr->id }}"
                                    onclick="toggleDtrStatus(this)"
                                    title="Click to toggle"
                                    class="text-xs font-medium px-2 py-0.5 rounded-full cursor-pointer transition
                                        {{ $dtr->status === 'Approved'
                                            ? 'text-green-700 bg-green-100 hover:bg-green-200'
                                            : 'text-rose-700 bg-rose-100 hover:bg-rose-200' }}">
                                    {{ $dtr->status }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($dtr->ot_status === 'pending')
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <form action="{{ route('dtr.approve-ot', $dtr) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs font-medium text-green-700 hover:text-green-900 bg-green-100 hover:bg-green-200 px-2 py-1 rounded-lg transition"
                                                    onclick="return confirm('Approve OT for {{ addslashes($dtr->employee->full_name) }}?')">
                                                Approve
                                            </button>
                                        </form>
                                        <button type="button"
                                                class="text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1 rounded-lg transition"
                                                @click="$dispatch('open-reject', { action: '{{ route('dtr.reject-ot', $dtr) }}' })">
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 justify-end">
                                    <a href="{{ route('dtr.edit', $dtr) }}"
                                       class="text-sm text-gray-500 hover:text-gray-800 font-medium">Edit</a>
                                    <a href="{{ route('dtr.show', $dtr) }}"
                                       class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-5 py-10 text-center text-gray-400">
                                No DTR records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dtrs->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $dtrs->links() }}
            </div>
        @endif
    </div>

@push('scripts')
<script src="https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js"></script>
<script src="https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.min.js"></script>
<script>
    tippy('[data-tippy-content]', {
        delay: 0,
        placement: 'top',
        theme: 'light-border',
    });

    async function toggleDtrStatus(btn) {
        const dtrId = btn.dataset.dtrId;
        btn.disabled = true;
        btn.style.opacity = '0.5';
        try {
            const res = await fetch(`/dtr/${dtrId}/toggle-status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();
            const isApproved = data.status === 'Approved';
            btn.textContent = data.status;
            btn.className = `text-xs font-medium px-2 py-0.5 rounded-full cursor-pointer transition ${
                isApproved
                    ? 'text-green-700 bg-green-100 hover:bg-green-200'
                    : 'text-rose-700 bg-rose-100 hover:bg-rose-200'
            }`;
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }
</script>
@endpush

</x-app-layout>
