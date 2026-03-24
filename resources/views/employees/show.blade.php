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

    <div class="max-w-3xl w-full space-y-6">

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
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
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
                    <dt class="text-gray-500">Birthday</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">
                        {{ $employee->birthday ? $employee->birthday->format('F d, Y') : '—' }}
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
                <div>
                    <dt class="text-gray-500">SSS No.</dt>
                    <dd class="font-mono font-medium text-gray-900 mt-0.5">{{ $employee->sss_no ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">PhilHealth No.</dt>
                    <dd class="font-mono font-medium text-gray-900 mt-0.5">{{ $employee->phic_no ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Pag-IBIG No.</dt>
                    <dd class="font-mono font-medium text-gray-900 mt-0.5">{{ $employee->pagibig_no ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">TIN No.</dt>
                    <dd class="font-mono font-medium text-gray-900 mt-0.5">{{ $employee->tin_no ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Contact Number</dt>
                    <dd class="font-medium text-gray-900 mt-0.5">{{ $employee->contact_number ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Emergency Contact --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Emergency Contact</h3>
            @if($employee->emergency_contact_name)
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Name</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $employee->emergency_contact_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Relationship</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $employee->emergency_contact_relationship ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Contact Number</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">{{ $employee->emergency_contact_number ?: '—' }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-400">No emergency contact set.</p>
            @endif
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

        {{-- Staff Login Account --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Staff Login Account</h3>

            @if($employee->user)
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                        {{ strtoupper(substr($employee->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $employee->user->email }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            @if($employee->user->can_approve_ot)
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Can Approve OT</span>
                            @endif
                            @if($employee->user->must_change_password)
                                <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">Password change required</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('employees.account.update', $employee) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="can_approve_ot" value="{{ $employee->user->can_approve_ot ? '0' : '1' }}">
                        <button type="submit"
                                class="text-sm border border-gray-300 px-3 py-1.5 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            {{ $employee->user->can_approve_ot ? 'Remove OT Approver' : 'Make OT Approver' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('employees.account.reset-password', $employee) }}">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Reset this employee\'s password?')"
                                class="text-sm border border-red-200 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-50 transition">
                            Reset Password
                        </button>
                    </form>
                </div>
            @else
                <p class="text-sm text-gray-400 mb-4">No login account yet. Create one to allow this employee to log in.</p>
                <form method="POST" action="{{ route('employees.account.create', $employee) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email Address</label>
                        <input type="email" name="email"
                               value="{{ old('email', $employee->email) }}"
                               placeholder="employee@example.com"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="can_approve_ot" value="1" class="rounded border-gray-300 text-indigo-600">
                        Can approve overtime
                    </label>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Create Account
                    </button>
                </form>
            @endif
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
            <a href="{{ route('employees.allowances.index', $employee) }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Manage Allowances
            </a>
            {{-- Fetch Logs button hidden (timemark not in use) --}}
        </div>

    </div>

</x-app-layout>
