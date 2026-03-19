<div class="space-y-5">

    {{-- Name --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $branch->name ?? '') }}"
               class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g. Abreeza" required>
        @error('name')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Address --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
        <input type="text" name="address" value="{{ old('address', $branch->address ?? '') }}"
               class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g. Davao City">
        @error('address')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Work Hours --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Work Start Time <span class="text-red-500">*</span></label>
            <input type="time" name="work_start_time"
                   value="{{ old('work_start_time', isset($branch) ? \Carbon\Carbon::parse($branch->work_start_time)->format('H:i') : '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   required>
            @error('work_start_time')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Work End Time <span class="text-red-500">*</span></label>
            <input type="time" name="work_end_time"
                   value="{{ old('work_end_time', isset($branch) ? \Carbon\Carbon::parse($branch->work_end_time)->format('H:i') : '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   required>
            @error('work_end_time')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <p class="text-xs text-gray-500">Work hours define the standard shift for this branch. Employees must work 8 hours (9 hours total including 1-hour break). Overtime applies beyond 8 working hours.</p>

</div>
