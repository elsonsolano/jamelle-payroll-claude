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

    <div x-data="{ hasPhilhealth: {{ old('has_philhealth', $cutoff->has_philhealth) ? 'true' : 'false' }} }"
         class="rounded-lg border border-gray-200 p-4 space-y-3">
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="has_philhealth" value="1"
                   x-model="hasPhilhealth"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-medium text-gray-700">Apply PhilHealth Deduction to this cutoff</span>
        </label>

        <div x-show="hasPhilhealth" x-cloak class="space-y-2 pl-7">
            @if($partnerOptions->isEmpty())
                <p class="text-xs text-amber-600">No other cutoffs found for this branch in the same month. Generate the partner cutoff first.</p>
            @else
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Partner Cutoff (1st half of month)</label>
                    <select name="philhealth_partner_cutoff_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select partner cutoff…</option>
                        @foreach($partnerOptions as $partner)
                            <option value="{{ $partner->id }}"
                                {{ old('philhealth_partner_cutoff_id', $suggestedPartnerId) == $partner->id ? 'selected' : '' }}>
                                {{ $partner->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('philhealth_partner_cutoff_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            @endif
            <div class="flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-100 p-3">
                <svg class="w-4 h-4 text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-xs text-blue-700 space-y-1">
                    <p><strong>Formula:</strong> 2.5% × (partner basic pay + this cutoff's basic pay) per employee.</p>
                    <p><strong>Step 1:</strong> Generate the partner cutoff's payroll first.</p>
                    <p><strong>Step 2:</strong> Come back and generate this cutoff — PhilHealth amounts will be filled in automatically.</p>
                </div>
            </div>
        </div>
    </div>
    @endisset

</div>
