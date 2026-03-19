<div class="space-y-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cutoff Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $cutoff->name ?? '') }}"
               class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g. March 1-15, 2026" required>
        <p class="mt-1 text-xs text-gray-400">A descriptive name for this pay period.</p>
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Branch <span class="text-red-500">*</span></label>
        <select name="branch_id"
                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                required>
            <option value="">Select branch…</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}"
                    {{ old('branch_id', $cutoff->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
        @error('branch_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
            <input type="date" name="start_date"
                   value="{{ old('start_date', isset($cutoff->start_date) ? $cutoff->start_date->format('Y-m-d') : '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   required>
            @error('start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
            <input type="date" name="end_date"
                   value="{{ old('end_date', isset($cutoff->end_date) ? $cutoff->end_date->format('Y-m-d') : '') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                   required>
            @error('end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    @isset($cutoff->id)
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select name="status"
                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="draft"      {{ old('status', $cutoff->status) === 'draft'      ? 'selected' : '' }}>Draft</option>
            <option value="processing" {{ old('status', $cutoff->status) === 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="finalized"  {{ old('status', $cutoff->status) === 'finalized'  ? 'selected' : '' }}>Finalized</option>
        </select>
        @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    @endisset

</div>
