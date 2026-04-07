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
        @isset($cutoff->id)
            {{-- Edit: single branch dropdown (cutoff is already branch-specific) --}}
            <select name="branch_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    required>
                <option value="">Select branch…</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}"
                        {{ old('branch_id', $cutoff->branch_id) == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @else
            {{-- Create: checkboxes so multiple branches can be created in one shot --}}
            <div x-data="{
                    selected: {{ json_encode(old('branch_ids', $branches->pluck('id')->all())) }},
                    all: {{ json_encode($branches->pluck('id')->all()) }},
                    get allChecked() { return this.all.every(id => this.selected.includes(id)); },
                    get someChecked() { return this.selected.length > 0 && !this.allChecked; },
                    toggleAll() { this.selected = this.allChecked ? [] : [...this.all]; }
                }"
                 class="border border-gray-200 rounded-lg divide-y divide-gray-100">

                {{-- Select All row --}}
                <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 rounded-t-lg">
                    <input type="checkbox"
                           :checked="allChecked"
                           :indeterminate="someChecked"
                           @change="toggleAll()"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-700">Select All</span>
                </label>

                {{-- Per-branch rows --}}
                @foreach($branches as $branch)
                <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 {{ $loop->last ? 'rounded-b-lg' : '' }}">
                    <input type="checkbox"
                           name="branch_ids[]"
                           value="{{ $branch->id }}"
                           x-model="selected"
                           :value="{{ $branch->id }}"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">{{ $branch->name }}</span>
                </label>
                @endforeach
            </div>
            @error('branch_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('branch_ids.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @endisset
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
