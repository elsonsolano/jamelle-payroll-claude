@php
    $types = [
        'SSS'          => 'SSS',
        'PhilHealth'   => 'PhilHealth',
        'PagIBIG'      => 'Pag-IBIG',
        'loan'         => 'Loan',
        'cash_advance' => 'Cash Advance',
        'uniform'      => 'Uniform',
        'other'        => 'Other',
    ];
@endphp

<div class="space-y-4">

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
            <select name="type"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    required>
                <option value="">Select type…</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}"
                        {{ old('type', $deduction?->type ?? '') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₱) <span class="text-red-500">*</span></label>
            <input type="number" name="amount"
                   value="{{ old('amount', $deduction?->amount ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="0.00" step="0.01" min="0" required>
            @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Apply On <span class="text-red-500">*</span></label>
        <select name="cutoff_period"
                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                required>
            <option value="both"   {{ old('cutoff_period', $deduction?->cutoff_period ?? 'both') === 'both'   ? 'selected' : '' }}>Both cutoffs</option>
            <option value="first"  {{ old('cutoff_period', $deduction?->cutoff_period ?? 'both') === 'first'  ? 'selected' : '' }}>1st cutoff only (payday 15th)</option>
            <option value="second" {{ old('cutoff_period', $deduction?->cutoff_period ?? 'both') === 'second' ? 'selected' : '' }}>2nd cutoff only (payday 30th/31st)</option>
        </select>
        @error('cutoff_period')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <input type="text" name="description"
               value="{{ old('description', $deduction?->description ?? '') }}"
               class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g. SSS Contribution, Loan #2…">
        @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

</div>
