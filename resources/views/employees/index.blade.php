<x-app-layout>
    <x-slot name="title">Employees</x-slot>

    <x-slot name="actions">
        <a href="{{ route('employees.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Employee
        </a>
    </x-slot>

    {{-- Filters --}}
    <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name, code, position…"
               class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-64">

        <select name="branch_id" class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Status</option>
            <option value="active"   @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>

        <button type="submit"
                class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg transition">
            Filter
        </button>

        @if(request()->hasAny(['search','branch_id','status']))
            <a href="{{ route('employees.index') }}"
               class="px-4 py-2 text-sm text-gray-500 hover:text-gray-800 transition">
                Clear
            </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Employee</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Code</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Branch</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Position</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Salary</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Status</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($employees as $employee)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $employee->full_name }}</p>
                                    <p class="text-xs text-gray-400">ID: {{ $employee->timemark_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600 font-mono text-xs">{{ $employee->employee_code }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $employee->branch->name }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $employee->position ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <div class="text-gray-800 font-medium">₱{{ number_format($employee->rate, 2) }}</div>
                            <div class="text-xs text-gray-400">{{ ucfirst($employee->salary_type) }} rate</div>
                        </td>
                        <td class="px-5 py-3">
                            @if($employee->active)
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="inline-flex items-center gap-3">
                                <a href="{{ route('employees.show', $employee) }}"
                                   class="text-sm text-gray-500 hover:text-gray-800 font-medium">View</a>
                                <a href="{{ route('employees.edit', $employee) }}"
                                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                                      onsubmit="return confirm('Delete {{ $employee->full_name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-gray-400">
                            No employees found.
                            @if(!request()->hasAny(['search','branch_id','status']))
                                <a href="{{ route('employees.create') }}" class="text-indigo-600 hover:underline">Add one</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($employees->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

</x-app-layout>
