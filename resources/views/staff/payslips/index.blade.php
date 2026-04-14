<x-staff-layout>
    <x-slot name="title">My Payslips</x-slot>

    <div class="space-y-3">

        <div class="flex items-center gap-3 mb-1">
            <a href="{{ route('staff.profile') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-lg font-bold text-gray-900">My Payslips</h1>
        </div>

        @forelse($payslips as $entry)
            <a href="{{ route('staff.payslips.show', $entry) }}"
               class="block bg-white rounded-2xl border border-gray-100 px-5 py-4 hover:border-green-200 hover:shadow-sm transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $entry->payrollCutoff->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $entry->payrollCutoff->start_date->format('M d') }} – {{ $entry->payrollCutoff->end_date->format('M d, Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600 text-base">PHP {{ number_format($entry->net_pay, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Net Pay</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-2xl border border-gray-100 px-5 py-12 text-center">
                <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-gray-400">No payslips yet.</p>
            </div>
        @endforelse

    </div>
</x-staff-layout>
