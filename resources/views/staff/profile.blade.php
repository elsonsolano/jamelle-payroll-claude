<x-staff-layout>
    <x-slot name="title">My Profile</x-slot>

    <div class="space-y-4">

        {{-- Identity card --}}
        <div class="bg-green-500 rounded-2xl p-5 text-white">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                    <span class="text-2xl font-bold text-white">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-lg font-bold leading-tight truncate">{{ $employee->full_name }}</p>
                    <p class="text-sm text-green-100 truncate">{{ $employee->position ?? '—' }}</p>
                    <p class="text-xs text-green-200 mt-0.5">{{ $employee->branch->name }}</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20 flex gap-6 text-sm">
                <div>
                    <p class="text-green-200 text-xs">Employee #</p>
                    <p class="font-semibold">{{ $employee->employee_code ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-green-200 text-xs">Date Hired</p>
                    <p class="font-semibold">{{ $employee->hired_date ? $employee->hired_date->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-green-200 text-xs">Birthday</p>
                    <p class="font-semibold">{{ $employee->birthday ? $employee->birthday->format('M d, Y') : '—' }}</p>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Contact</p>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Email</span>
                <span class="font-medium text-gray-800">{{ $employee->email ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Mobile</span>
                <span class="font-medium text-gray-800">{{ $employee->contact_number ?? '—' }}</span>
            </div>
        </div>

        {{-- Government IDs --}}
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Government IDs</p>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">SSS</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->sss_no ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">PhilHealth</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->phic_no ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Pag-IBIG</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->pagibig_no ?? '—' }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">TIN</span>
                <span class="font-medium text-gray-800 font-mono">{{ $employee->tin_no ?? '—' }}</span>
            </div>
        </div>

        {{-- Emergency Contact --}}
        @if($employee->emergency_contact_name)
        <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
            <div class="px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Emergency Contact</p>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Name</span>
                <span class="font-medium text-gray-800">{{ $employee->emergency_contact_name }}</span>
            </div>
            @if($employee->emergency_contact_relationship)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Relationship</span>
                <span class="font-medium text-gray-800">{{ $employee->emergency_contact_relationship }}</span>
            </div>
            @endif
            @if($employee->emergency_contact_number)
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Number</span>
                <span class="font-medium text-gray-800">{{ $employee->emergency_contact_number }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- My Payslips --}}
        <a href="{{ route('staff.payslips.index') }}"
           class="flex items-center justify-between w-full bg-white rounded-2xl border border-gray-100 px-5 py-4 hover:border-green-200 hover:shadow-sm transition">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-gray-800">My Payslips</span>
            </div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full py-3 rounded-2xl border border-red-200 text-red-600 text-sm font-semibold bg-white hover:bg-red-50 transition">
                Log Out
            </button>
        </form>

    </div>
</x-staff-layout>
