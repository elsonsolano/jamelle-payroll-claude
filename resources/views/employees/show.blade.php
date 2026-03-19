<x-app-layout>
    <x-slot name="title">{{ $employee->full_name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('employees.edit', $employee) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit
        </a>
    </x-slot>

    <div class="max-w-3xl space-y-6">

        {{-- Profile Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-start gap-5">
                <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl font-bold text-gray-900">{{ $employee->full_name }}</h2>
                        @if($employee->active)
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $employee->position ?? 'No position set' }} &middot; {{ $employee->branch->name }}</p>
                </div>
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Employee Information</h3>
            <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500">Employee Code</dt>
                    <dd class="font-mono font-medium text-gray-900 mt-0.5">{{ $employee->employee_code }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Timemark ID</dt>
                    <dd class="font-mono font-medium text-gray-900 mt-0.5">{{ $employee->timemark_id }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Branch</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">{{ $employee->branch->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Hired Date</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">
                        {{ $employee->hired_date ? $employee->hired_date->format('F d, Y') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Salary Type</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">{{ ucfirst($employee->salary_type) }} Rate</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Rate</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">
                        ₱{{ number_format($employee->rate, 2) }}
                        <span class="text-gray-400 font-normal">/ {{ $employee->salary_type === 'daily' ? 'day' : 'month' }}</span>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Standing Deductions --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Standing Deductions</h3>
                <span class="text-xs text-gray-400">SSS, PhilHealth, Pag-IBIG, etc.</span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($employee->employeeStandingDeductions as $deduction)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $deduction->type }}</p>
                            @if($deduction->description)
                                <p class="text-xs text-gray-400">{{ $deduction->description }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">₱{{ number_format($deduction->amount, 2) }}</p>
                            <span @class([
                                'text-xs px-2 py-0.5 rounded-full font-medium',
                                'bg-green-100 text-green-700' => $deduction->active,
                                'bg-gray-100 text-gray-500'   => !$deduction->active,
                            ])>{{ $deduction->active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-sm text-gray-400">No standing deductions set.</div>
                @endforelse
            </div>
        </div>

        {{-- Fetch Attendance --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ open: false }">
            <div class="px-5 py-4 flex items-center justify-between hover:bg-gray-50 transition cursor-pointer select-none"
                 @click="open = !open">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-800">Fetch Attendance</span>
                </div>
                <svg :class="open ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>

            <div x-show="open" x-cloak class="border-t border-gray-100 p-5">
                <form method="POST" action="{{ route('timemark.fetch') }}">
                    @csrf
                    <input type="hidden" name="fetch_type" value="employee">
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                            <input type="date" name="date_from"
                                   value="{{ old('date_from', now()->startOfMonth()->format('Y-m-d')) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                            <input type="date" name="date_to"
                                   value="{{ old('date_to', now()->format('Y-m-d')) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Fetch
                        </button>
                        <span class="text-xs text-gray-400">Job will be queued and processed in the background.</span>
                    </div>
                </form>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('dtr.index', ['employee_id' => $employee->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                View DTR
            </a>
            <a href="{{ route('employees.schedules.index', $employee) }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Manage Schedule
            </a>
            <a href="{{ route('employees.deductions.index', $employee) }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Manage Deductions
            </a>
            <a href="{{ route('timemark.logs', ['employee_id' => $employee->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Fetch Logs
            </a>
        </div>

    </div>

</x-app-layout>
