<x-app-layout>
    <x-slot name="title">Payroll Cutoffs</x-slot>

    <x-slot name="actions">
        <a href="{{ route('payroll.cutoffs.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Cutoff
        </a>
    </x-slot>

    {{-- Filters --}}
    <form method="GET" action="{{ route('payroll.cutoffs.index') }}" class="flex flex-wrap gap-3 mb-5">
        <select name="branch_id"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                onchange="this.form.submit()">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>

        <select name="status"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="draft"      @selected(request('status') === 'draft')>Draft</option>
            <option value="processing" @selected(request('status') === 'processing')>Processing</option>
            <option value="finalized"  @selected(request('status') === 'finalized')>Finalized</option>
            <option value="voided"     @selected(request('status') === 'voided')>Voided</option>
        </select>

        @if(request()->hasAny(['branch_id','status']))
            <a href="{{ route('payroll.cutoffs.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-800">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Name</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Branch</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Period</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Employees</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Status</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($cutoffs as $cutoff)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3 font-medium text-gray-900">
                            <a href="{{ route('payroll.cutoffs.show', $cutoff) }}" class="hover:text-indigo-600">
                                {{ $cutoff->name }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $cutoff->branch->name }}</td>
                        <td class="px-5 py-3 text-gray-600">
                            {{ $cutoff->start_date->format('M d') }} – {{ $cutoff->end_date->format('M d, Y') }}
                            <p class="text-xs text-gray-400">{{ $cutoff->start_date->diffInDays($cutoff->end_date) + 1 }} days</p>
                        </td>
                        <td class="px-5 py-3 text-center text-gray-700 font-medium">
                            {{ $cutoff->payroll_entries_count }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span @class([
                                'text-xs font-medium px-2.5 py-1 rounded-full',
                                'bg-gray-100 text-gray-600'   => $cutoff->status === 'draft',
                                'bg-amber-100 text-amber-700' => $cutoff->status === 'processing',
                                'bg-green-100 text-green-700' => $cutoff->status === 'finalized',
                                'bg-red-100 text-red-700'     => $cutoff->status === 'voided',
                            ])>{{ ucfirst($cutoff->status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="inline-flex items-center gap-3">
                                <a href="{{ route('payroll.cutoffs.show', $cutoff) }}"
                                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                                @if($cutoff->status === 'draft')
                                    <a href="{{ route('payroll.cutoffs.edit', $cutoff) }}"
                                       class="text-sm text-gray-500 hover:text-gray-800 font-medium">Edit</a>
                                    <form method="POST" action="{{ route('payroll.cutoffs.destroy', $cutoff) }}"
                                          onsubmit="return confirm('Delete {{ $cutoff->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400">
                            No cutoffs found. <a href="{{ route('payroll.cutoffs.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        </div>

        @if($cutoffs->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $cutoffs->links() }}</div>
        @endif
    </div>

</x-app-layout>
