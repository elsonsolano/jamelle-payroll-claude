<x-app-layout>
    <x-slot name="title">Timemark</x-slot>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Fetch Form --}}
        <div class="xl:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 p-5 sticky top-6">
                <h2 class="font-semibold text-gray-800 mb-1">Fetch Attendance</h2>
                <p class="text-xs text-gray-400 mb-5">Pull DTR records from the Timemark device.</p>

                <form method="POST" action="{{ route('timemark.fetch') }}" x-data="{ fetchType: 'employee' }">
                    @csrf

                    {{-- Fetch Type Toggle --}}
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-2">Fetch by</label>
                        <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                            <label class="flex-1 text-center cursor-pointer">
                                <input type="radio" name="fetch_type" value="employee" x-model="fetchType" class="sr-only">
                                <span :class="fetchType === 'employee' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                      class="block py-2 transition font-medium">Employee</span>
                            </label>
                            <label class="flex-1 text-center cursor-pointer border-l border-gray-300">
                                <input type="radio" name="fetch_type" value="branch" x-model="fetchType" class="sr-only">
                                <span :class="fetchType === 'branch' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                      class="block py-2 transition font-medium">Branch</span>
                            </label>
                        </div>
                    </div>

                    {{-- Employee Select --}}
                    <div x-show="fetchType === 'employee'" class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Employee</label>
                        <select name="employee_id"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select employee…</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->branch->name }})</option>
                            @endforeach
                        </select>
                        @error('employee_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Branch Select --}}
                    <div x-show="fetchType === 'branch'" class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
                        <select name="branch_id"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select branch…</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Date Range --}}
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                        <input type="date" name="date_from"
                               value="{{ old('date_from', now()->startOfMonth()->format('Y-m-d')) }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('date_from')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                        <input type="date" name="date_to"
                               value="{{ old('date_to', now()->format('Y-m-d')) }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('date_to')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Fetch Attendance
                    </button>
                </form>

                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-xs text-amber-700">
                        <span class="font-semibold">Note:</span> Fetching runs synchronously. Large date ranges may take a moment.
                    </p>
                </div>
            </div>
        </div>

        {{-- Logs --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Log Filters --}}
            <form method="GET" action="{{ route('timemark.logs') }}" class="flex flex-wrap gap-3">
                <select name="branch_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>

                <select name="employee_id"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-48">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->full_name }}</option>
                    @endforeach
                </select>

                <select name="status"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="success" @selected(request('status') === 'success')>Success</option>
                    <option value="failed"  @selected(request('status') === 'failed')>Failed</option>
                </select>

                <button type="submit"
                        class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg transition">
                    Filter
                </button>

                @if(request()->hasAny(['employee_id','branch_id','status']))
                    <a href="{{ route('timemark.logs') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-800">Clear</a>
                @endif
            </form>

            {{-- Logs Table --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Fetch Logs</h2>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <div class="px-5 py-4 flex items-start gap-4">
                            <div class="mt-0.5 flex-shrink-0">
                                @if($log->status === 'success')
                                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-gray-900">{{ $log->employee?->full_name ?? 'Deleted Employee' }}</p>
                                    <span class="text-xs text-gray-400">{{ $log->employee?->branch?->name ?? '—' }}</span>
                                    <span @class([
                                        'text-xs font-medium px-2 py-0.5 rounded-full ml-auto',
                                        'bg-green-100 text-green-700' => $log->status === 'success',
                                        'bg-red-100 text-red-700'     => $log->status === 'failed',
                                    ])>{{ ucfirst($log->status) }}</span>
                                </div>
                                <div class="flex items-center gap-4 mt-1 text-xs text-gray-500">
                                    <span>Device: <span class="font-mono text-gray-700">{{ $log->device_id }}</span></span>
                                    <span>{{ $log->records_fetched }} records</span>
                                    <span>{{ $log->fetched_at->diffForHumans() }}</span>
                                </div>
                                @if($log->notes)
                                    <p class="mt-1 text-xs text-gray-400 truncate">{{ $log->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-gray-400 text-sm">
                            No fetch logs yet. Use the form to fetch attendance data.
                        </div>
                    @endforelse
                </div>

                @if($logs->hasPages())
                    <div class="px-5 py-4 border-t border-gray-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-app-layout>
