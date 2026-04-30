<x-app-layout>
    <x-slot name="title">Employees</x-slot>

    <x-slot name="actions">
        {{-- Import --}}
        <div x-data="{ open: false }">
            <button @click="open = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M8 12l4 4m0 0l4-4m-4 4V4"/>
                </svg>
                Import Excel
            </button>

            {{-- Modal --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Import Employees</h2>

                    <p class="text-sm text-gray-600">
                        Upload an Excel file (.xlsx) to bulk-import or update employees.
                        Existing employees (matched by EE #) will be updated.
                    </p>

                    <a href="{{ route('employees.import.template') }}"
                       class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M8 8l4-4m0 0l4 4m-4-4v12"/>
                        </svg>
                        Download Template
                    </a>

                    <form method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Excel File</label>
                            <input type="file" name="file" accept=".xlsx,.xls" required
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-gray-300 file:text-sm file:font-medium file:bg-gray-50 hover:file:bg-gray-100">
                            @error('file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="open = false"
                                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
               class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64">

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
            <a href="{{ route('employees.index', ['clear' => 1]) }}"
               class="px-4 py-2 text-sm text-gray-500 hover:text-gray-800 transition">
                Clear
            </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Employee</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Branch</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Position</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">SSS No.</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">PhilHealth No.</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Pag-IBIG No.</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">TIN No.</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Status</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($employees as $employee)
                    <tr class="hover:bg-indigo-50 cursor-pointer transition"
                        onclick="window.location='{{ route('employees.show', $employee) }}'">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @if($employee->user?->profile_photo_url)
                                    <img src="{{ $employee->user->profile_photo_url }}"
                                         alt="{{ $employee->full_name }}"
                                         class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-medium text-gray-900">{{ $employee->full_name }}</p>
                                        @if($employee->user)
                                            <span title="Has portal account" class="text-green-500">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        @else
                                            <span title="No portal account" class="text-gray-300">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400">{{ $employee->employee_code }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $employee->branch->name }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $employee->position ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $employee->sss_no ?: '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $employee->phic_no ?: '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $employee->pagibig_no ?: '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $employee->tin_no ?: '—' }}</td>
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
                        <td class="px-5 py-3 text-right" onclick="event.stopPropagation()">
                            <div class="inline-flex items-center gap-3">
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
                        <td colspan="10" class="px-5 py-10 text-center text-gray-400">
                            No employees found.
                            @if(!request()->hasAny(['search','branch_id','status']))
                                <a href="{{ route('employees.create') }}" class="text-indigo-600 hover:underline">Add one</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($employees->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

</x-app-layout>
