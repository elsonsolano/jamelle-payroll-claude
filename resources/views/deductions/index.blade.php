<x-app-layout>
    <x-slot name="title">Deductions — {{ $employee->full_name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('employees.show', $employee) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to Employee
        </a>
    </x-slot>

    <div class="max-w-2xl space-y-6" x-data="{ showAddForm: {{ $errors->any() ? 'true' : 'false' }}, editId: null }">

        {{-- Employee Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold flex-shrink-0">
                {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
            </div>
            <div class="flex-1">
                <p class="font-semibold text-gray-900">{{ $employee->full_name }}</p>
                <p class="text-xs text-gray-400">{{ $employee->position ?? 'No position' }} · {{ $employee->branch->name }}</p>
            </div>
            <div class="text-right text-xs text-gray-500">
                <p class="font-medium text-gray-700">{{ ucfirst($employee->salary_type) }} Rate</p>
                <p>₱{{ number_format($employee->rate, 2) }} / {{ $employee->salary_type === 'daily' ? 'day' : 'month' }}</p>
            </div>
        </div>

        {{-- Add Deduction --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <button @click="showAddForm = !showAddForm"
                    class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition">
                <span class="font-semibold text-gray-800">Add Deduction</span>
                <svg :class="showAddForm ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="showAddForm" x-cloak class="border-t border-gray-100 p-5">
                <form method="POST" action="{{ route('employees.deductions.store', $employee) }}">
                    @csrf
                    @include('deductions._form', ['deduction' => null])
                    <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Add Deduction
                        </button>
                        <button type="button" @click="showAddForm = false"
                                class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Info Box --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800">
            <p class="font-semibold mb-1">How standing deductions work</p>
            <p>Active deductions are automatically applied when payroll is generated. Use <strong>Apply On</strong> to control whether a deduction is taken on both cutoffs, only the 1st (1–15), or only the 2nd (16–end of month). Deactivate a deduction to temporarily exclude it without deleting it.</p>
        </div>

        {{-- Deductions List --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Standing Deductions</h2>
                <span class="text-xs text-gray-400">{{ $deductions->where('active', true)->count() }} active</span>
            </div>

            @forelse($deductions as $deduction)
                <div class="border-b border-gray-100 last:border-0" >

                    {{-- View Row --}}
                    <div x-show="editId !== {{ $deduction->id }}" class="px-5 py-4 flex items-center gap-4">
                        {{-- Type Badge --}}
                        <div @class([
                            'w-10 h-10 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0',
                            'bg-blue-100 text-blue-700'   => $deduction->type === 'SSS',
                            'bg-green-100 text-green-700' => $deduction->type === 'PhilHealth',
                            'bg-purple-100 text-purple-700' => $deduction->type === 'PagIBIG',
                            'bg-orange-100 text-orange-700' => $deduction->type === 'loan',
                            'bg-pink-100 text-pink-700'   => $deduction->type === 'cash_advance',
                            'bg-yellow-100 text-yellow-700' => $deduction->type === 'uniform',
                            'bg-gray-100 text-gray-600'   => $deduction->type === 'other',
                        ])>
                            {{ strtoupper(substr($deduction->type, 0, 3)) }}
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-900">
                                    {{ match($deduction->type) {
                                        'SSS' => 'SSS',
                                        'PhilHealth' => 'PhilHealth',
                                        'PagIBIG' => 'Pag-IBIG',
                                        'loan' => 'Loan',
                                        'cash_advance' => 'Cash Advance',
                                        'uniform' => 'Uniform',
                                        default => 'Other',
                                    } }}
                                </p>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                    {{ $deduction->cutoff_period === 'both'   ? 'bg-indigo-50 text-indigo-600' : '' }}
                                    {{ $deduction->cutoff_period === 'first'  ? 'bg-teal-50 text-teal-600' : '' }}
                                    {{ $deduction->cutoff_period === 'second' ? 'bg-violet-50 text-violet-600' : '' }}">
                                    {{ match($deduction->cutoff_period) {
                                        'first'  => 'Payday 15th',
                                        'second' => 'Payday 30th/31st',
                                        default  => 'Both cutoffs',
                                    } }}
                                </span>
                                @if(!$deduction->active)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">Inactive</span>
                                @endif
                            </div>
                            @if($deduction->description)
                                <p class="text-xs text-gray-400 truncate">{{ $deduction->description }}</p>
                            @endif
                        </div>

                        <div class="text-right flex-shrink-0">
                            <p class="font-bold text-gray-900">₱{{ number_format($deduction->amount, 2) }}</p>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            {{-- Toggle Active --}}
                            <form method="POST" action="{{ route('employees.deductions.toggle', [$employee, $deduction]) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        title="{{ $deduction->active ? 'Deactivate' : 'Activate' }}"
                                        class="text-sm font-medium {{ $deduction->active ? 'text-amber-500 hover:text-amber-700' : 'text-green-600 hover:text-green-800' }} transition">
                                    {{ $deduction->active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            <button @click="editId = {{ $deduction->id }}"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                Edit
                            </button>

                            <form method="POST" action="{{ route('employees.deductions.destroy', [$employee, $deduction]) }}"
                                  onsubmit="return confirm('Delete this deduction?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                            </form>
                        </div>
                    </div>

                    {{-- Inline Edit Form --}}
                    <div x-show="editId === {{ $deduction->id }}" x-cloak class="px-5 py-4 bg-indigo-50/50">
                        <p class="text-sm font-semibold text-gray-700 mb-4">Edit Deduction</p>
                        <form method="POST" action="{{ route('employees.deductions.update', [$employee, $deduction]) }}">
                            @csrf @method('PUT')
                            @include('deductions._form', ['deduction' => $deduction])
                            <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-200">
                                <button type="submit"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                    Update
                                </button>
                                <button type="button" @click="editId = null"
                                        class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-gray-400">
                    No standing deductions yet. Add one above.
                </div>
            @endforelse

            @if($deductions->isNotEmpty())
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex justify-between text-sm">
                    <span class="font-medium text-gray-600">Total Active Deductions / Cutoff</span>
                    <span class="font-bold text-red-500">
                        ₱{{ number_format($deductions->where('active', true)->sum('amount'), 2) }}
                    </span>
                </div>
            @endif
        </div>

    </div>

</x-app-layout>
