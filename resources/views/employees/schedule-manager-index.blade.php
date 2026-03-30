<x-app-layout>
    <x-slot name="title">Employees</x-slot>

    @php
        $employeeData = $employees->map(fn($e) => [
            'id'       => $e->id,
            'name'     => $e->full_name,
            'nickname' => $e->nickname,
            'branch'   => $e->branch->name,
            'branch_id'=> $e->branch_id,
            'position' => $e->position ?? '',
            'url'      => route('employees.schedules.index', $e),
        ])->values();
    @endphp

    <div
        x-data="{
            search: '',
            branchId: '',
            employees: {{ $employeeData->toJson() }},
            get filtered() {
                return this.employees.filter(e => {
                    const matchesBranch = !this.branchId || e.branch_id == this.branchId;
                    const q = this.search.toLowerCase();
                    const matchesSearch = !q || e.name.toLowerCase().includes(q) || (e.nickname && e.nickname.toLowerCase().includes(q));
                    return matchesBranch && matchesSearch;
                });
            }
        }"
        class="space-y-4"
    >

        {{-- Filters --}}
        <div class="flex items-center gap-3">
            <div class="relative w-64">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text" x-model="search" placeholder="Search by name…"
                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>

            <select x-model="branchId"
                    class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>

            <p class="text-xs text-gray-400 ml-auto" x-text="filtered.length + ' employee' + (filtered.length === 1 ? '' : 's')"></p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left">
                        <th class="px-5 py-3 font-semibold text-gray-600">Employee</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Branch</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Position</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="emp in filtered" :key="emp.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900" x-text="emp.name"></p>
                                <p x-show="emp.nickname" class="text-xs text-gray-400 mt-0.5" x-text="emp.nickname"></p>
                            </td>
                            <td class="px-5 py-3 text-gray-600" x-text="emp.branch"></td>
                            <td class="px-5 py-3 text-gray-500" x-text="emp.position || '—'"></td>
                            <td class="px-5 py-3 text-right">
                                <a :href="emp.url"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800 border border-indigo-200 hover:border-indigo-400 rounded-lg transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Manage Schedule
                                </a>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filtered.length === 0">
                        <td colspan="4" class="px-5 py-12 text-center text-gray-400 text-sm">No employees found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>
