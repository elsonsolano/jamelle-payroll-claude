<div class="space-y-5">

    {{-- Name --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
            <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="Juan" required>
            @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
            <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="dela Cruz" required>
            @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Employee Code & Position --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee Code <span class="text-red-500">*</span></label>
            <input type="text" name="employee_code" value="{{ old('employee_code', $employee->employee_code ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="e.g. EMP-001" required>
            @error('employee_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
            <input type="text" name="position" value="{{ old('position', $employee->position ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="e.g. Crew">
            @error('position')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Branch & Hired Date --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Branch <span class="text-red-500">*</span></label>
            <select name="branch_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    required>
                <option value="">Select branch…</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}"
                        {{ old('branch_id', $employee->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hired Date</label>
            <input type="date" name="hired_date"
                   value="{{ old('hired_date', isset($employee->hired_date) ? $employee->hired_date->format('Y-m-d') : '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            @error('hired_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Timemark ID --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Timemark ID <span class="text-red-500">*</span></label>
        <input type="text" name="timemark_id" value="{{ old('timemark_id', $employee->timemark_id ?? '') }}"
               class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Device user ID from Timemark" required>
        <p class="mt-1 text-xs text-gray-500">The unique user ID assigned to this employee on the Timemark biometric device.</p>
        @error('timemark_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Salary --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Salary Type <span class="text-red-500">*</span></label>
            <select name="salary_type"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    required>
                <option value="daily"   {{ old('salary_type', $employee->salary_type ?? 'daily') === 'daily'   ? 'selected' : '' }}>Daily Rate</option>
                <option value="monthly" {{ old('salary_type', $employee->salary_type ?? '')       === 'monthly' ? 'selected' : '' }}>Fixed Monthly Rate</option>
            </select>
            @error('salary_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rate (₱) <span class="text-red-500">*</span></label>
            <input type="number" name="rate" value="{{ old('rate', $employee->rate ?? '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="0.00" step="0.01" min="0" required>
            @error('rate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Active --}}
    <div class="flex items-center gap-3 pt-1">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" id="active"
               {{ old('active', $employee->active ?? true) ? 'checked' : '' }}
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <label for="active" class="text-sm font-medium text-gray-700">Active Employee</label>
    </div>

</div>
