<x-app-layout>
    <x-slot name="title">Review Schedule — {{ $schedule->label ?? 'Upload' }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('schedule-uploads.index') }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back
        </a>
    </x-slot>

    @php
        $rows        = $data['rows'] ?? [];
        $unmatched   = $data['unmatched_names'] ?? [];
        $employeeMap = $employees->keyBy('id');

        // Flatten assignments for Alpine state: index => {employee_id, date, ...}
        $flatAssignments = [];
        foreach ($rows as $row) {
            foreach ($row['assignments'] as $a) {
                $flatAssignments[] = [
                    'name'            => $a['name'],
                    'employee_id'     => $a['employee_id'],
                    'date'            => $row['date'],
                    'day'             => $row['day'],
                    'work_start_time' => $a['work_start_time'],
                    'work_end_time'   => $a['work_end_time'],
                    'is_day_off'      => $a['is_day_off'],
                    'branch_override' => $a['branch_override'],
                    'notes'           => $a['notes'],
                ];
            }
        }
    @endphp

    <div x-data="scheduleReview()" x-init="init({{ json_encode($flatAssignments) }}, {{ json_encode($unmatched) }})" class="space-y-5">

        {{-- Header card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex flex-wrap items-center gap-6">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Period</p>
                    <p class="font-semibold text-gray-800">{{ $schedule->label ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Branch</p>
                    <p class="font-semibold text-gray-800">{{ $schedule->branch->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Total Entries</p>
                    <p class="font-semibold text-gray-800">{{ count($flatAssignments) }}</p>
                </div>
                @if(count($unmatched) > 0)
                    <div class="ml-auto">
                        <span class="text-xs font-medium px-3 py-1.5 rounded-full bg-red-100 text-red-700">
                            {{ count($unmatched) }} unmatched name(s) — fix before applying
                        </span>
                    </div>
                @endif
            </div>

            @if(count($unmatched) > 0)
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                    <p class="font-semibold mb-1">Unmatched names (no employee found):</p>
                    <p>{{ implode(', ', $unmatched) }}</p>
                    <p class="mt-1 text-red-500">Use the dropdowns below to assign these names to employees, or they will be skipped.</p>
                </div>
            @endif
        </div>

        {{-- Schedule Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Parsed Assignments</h2>
                <p class="text-xs text-gray-400">Review each row. Fix unmatched employees (shown in red).</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-600 w-24">Date</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 w-14">Day</th>
                            <th class="px-4 py-3 font-semibold text-gray-600">Name (from image)</th>
                            <th class="px-4 py-3 font-semibold text-gray-600">Matched Employee</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 w-28">Shift</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 w-20">Day Off</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 w-24">Branch Override</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 w-20">Notes</th>
                            <th class="px-4 py-3 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(row, idx) in assignments" :key="idx">
                            <tr :class="rowClass(row)">
                                <td class="py-2.5 text-gray-700 text-xs"
                                    :class="!row.employee_id && !approved[row.name] ? 'pl-3 border-l-4 border-red-500' : 'px-4'"
                                    x-text="row.date"></td>
                                <td class="px-4 py-2.5 text-gray-500 text-xs font-medium" x-text="row.day"></td>
                                <td class="px-4 py-2.5">
                                    <span class="text-xs font-medium" :class="row.employee_id ? 'text-gray-800' : 'text-red-600'" x-text="row.name"></span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <template x-if="!approved[row.name]">
                                        <select :class="row.employee_id ? 'border-gray-200 text-gray-700' : 'border-red-300 text-red-700 bg-red-50'"
                                                class="w-full border rounded-lg px-2 py-1 text-xs focus:ring-1 focus:ring-indigo-500"
                                                x-model="row.employee_id"
                                                @change="onSelectChange(row)">
                                            <option value="">— skip this entry —</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}">
                                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                                    @if($emp->nickname) ({{ $emp->nickname }}) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </template>
                                    <template x-if="approved[row.name]">
                                        <span class="text-xs font-medium text-green-700" x-text="approvedLabel[row.name]"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-600">
                                    <span x-show="!row.is_day_off" x-text="(row.work_start_time || '—') + ' – ' + (row.work_end_time || '—')"></span>
                                    <span x-show="row.is_day_off" class="text-gray-400">Day Off</span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span x-show="row.is_day_off" class="text-xs font-medium text-orange-600">OFF</span>
                                    <span x-show="!row.is_day_off" class="text-gray-300 text-xs">—</span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span x-show="row.branch_override" x-text="row.branch_override" class="text-indigo-600 font-medium"></span>
                                    <span x-show="!row.branch_override" class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span x-show="row.notes" x-text="row.notes" class="text-amber-600 font-medium"></span>
                                    <span x-show="!row.notes" class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <template x-if="needsApproval(row)">
                                        <button type="button"
                                                @click="approve(row)"
                                                :disabled="approving === row.name"
                                                class="px-2.5 py-1 text-xs font-medium bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-lg transition">
                                            <span x-text="approving === row.name ? 'Saving…' : 'Approve'"></span>
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Apply Form --}}
        <form method="POST" action="{{ route('schedule-uploads.apply', $schedule) }}">
            @csrf
            <input type="hidden" name="assignments" :value="JSON.stringify(assignments)">

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                    Apply Schedule
                </button>
                <p class="text-xs text-gray-400">
                    Entries with no employee selected will be skipped.
                    Existing schedules for the same employee + date will be overwritten.
                </p>
            </div>
        </form>
    </div>

    <script>
    function scheduleReview() {
        return {
            assignments:    [],
            unmatchedNames: [],   // names that had no match from AI
            approved:       {},   // name => true once approved
            approvedLabel:  {},   // name => "First Last" for display
            approving:      null, // name currently being saved

            init(data, unmatched) {
                this.assignments    = data;
                this.unmatchedNames = unmatched;
            },

            needsApproval(row) {
                return this.unmatchedNames.includes(row.name)
                    && !this.approved[row.name]
                    && !!row.employee_id;
            },

            rowClass(row) {
                if (this.approved[row.name]) return 'bg-green-50 hover:bg-green-100';
                if (!row.employee_id)        return 'bg-red-100 hover:bg-red-150';
                return 'hover:bg-gray-50';
            },

            // When a dropdown changes on an unmatched row, sync all rows with same name
            onSelectChange(row) {
                if (!this.unmatchedNames.includes(row.name)) return;
                this.assignments.forEach(a => {
                    if (a.name === row.name) a.employee_id = row.employee_id;
                });
            },

            async approve(row) {
                this.approving = row.name;
                try {
                    const res = await fetch('{{ route('schedule-uploads.assign-name', $schedule) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            name:        row.name,
                            employee_id: row.employee_id,
                        }),
                    });

                    if (!res.ok) throw new Error('Request failed');

                    const json = await res.json();

                    // Mark approved and sync all rows with this name
                    this.approved[row.name]      = true;
                    this.approvedLabel[row.name] = json.employee_name;
                    this.assignments.forEach(a => {
                        if (a.name === row.name) a.employee_id = json.employee_id;
                    });
                } catch (e) {
                    alert('Could not save assignment. Please try again.');
                } finally {
                    this.approving = null;
                }
            },
        };
    }
    </script>
</x-app-layout>
