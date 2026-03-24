<x-app-layout>
    <x-slot name="title">Allowances — {{ $employee->full_name }}</x-slot>

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

        {{-- Add Allowance --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <button @click="showAddForm = !showAddForm"
                    class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition">
                <span class="font-semibold text-gray-800">Add Allowance</span>
                <svg :class="showAddForm ? 'rotate-180' : ''" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="showAddForm" x-cloak class="border-t border-gray-100 p-5">
                <form method="POST" action="{{ route('employees.allowances.store', $employee) }}">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Daily Amount (₱)</label>
                            <input type="number" name="daily_amount" value="{{ old('daily_amount') }}"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                            @error('daily_amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Description (optional)</label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                   placeholder="e.g. Transportation, Meal"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100">
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Add Allowance
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
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-800">
            <p class="font-semibold mb-1">How allowances work</p>
            <p>Active allowances are automatically computed when payroll is generated: <strong>daily amount × days worked</strong> in the cutoff period. Only days the employee clocked in count. Deactivate to temporarily exclude without deleting.</p>
        </div>

        {{-- Allowances List --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Allowances</h2>
                <span class="text-xs text-gray-400">{{ $allowances->where('active', true)->count() }} active</span>
            </div>

            @forelse($allowances as $allowance)
                <div class="border-b border-gray-100 last:border-0">

                    {{-- View Row --}}
                    <div x-show="editId !== {{ $allowance->id }}" class="px-5 py-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            ALL
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-900">Daily Allowance</p>
                                @if(!$allowance->active)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">Inactive</span>
                                @endif
                            </div>
                            @if($allowance->description)
                                <p class="text-xs text-gray-400 truncate">{{ $allowance->description }}</p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-bold text-gray-900">₱{{ number_format($allowance->daily_amount, 2) }}/day</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <form method="POST" action="{{ route('employees.allowances.toggle', [$employee, $allowance]) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="text-sm font-medium {{ $allowance->active ? 'text-amber-500 hover:text-amber-700' : 'text-green-600 hover:text-green-800' }} transition">
                                    {{ $allowance->active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <button @click="editId = {{ $allowance->id }}"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                            <form method="POST" action="{{ route('employees.allowances.destroy', [$employee, $allowance]) }}"
                                  onsubmit="return confirm('Delete this allowance?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                            </form>
                        </div>
                    </div>

                    {{-- Inline Edit --}}
                    <div x-show="editId === {{ $allowance->id }}" x-cloak class="px-5 py-4 bg-indigo-50/50">
                        <p class="text-sm font-semibold text-gray-700 mb-4">Edit Allowance</p>
                        <form method="POST" action="{{ route('employees.allowances.update', [$employee, $allowance]) }}">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Daily Amount (₱)</label>
                                    <input type="number" name="daily_amount" value="{{ $allowance->daily_amount }}"
                                           min="0" step="0.01"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Description (optional)</label>
                                    <input type="text" name="description" value="{{ $allowance->description }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-200">
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
                    No allowances yet. Add one above.
                </div>
            @endforelse

            @if($allowances->where('active', true)->isNotEmpty())
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex justify-between text-sm">
                    <span class="font-medium text-gray-600">Active Daily Allowance Total</span>
                    <span class="font-bold text-emerald-600">
                        ₱{{ number_format($allowances->where('active', true)->sum('daily_amount'), 2) }}/day
                    </span>
                </div>
            @endif
        </div>

    </div>

</x-app-layout>
