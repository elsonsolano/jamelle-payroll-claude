<x-app-layout>
    <x-slot name="title">Branches</x-slot>

    <x-slot name="actions">
        <a href="{{ route('branches.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Branch
        </a>
    </x-slot>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left">
                    <th class="px-5 py-3 font-semibold text-gray-600">Branch Name</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Address</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Work Hours</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Employees</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($branches as $branch)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $branch->name }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $branch->address ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-700">
                            {{ \Carbon\Carbon::parse($branch->work_start_time)->format('g:i A') }}
                            –
                            {{ \Carbon\Carbon::parse($branch->work_end_time)->format('g:i A') }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1 text-indigo-700 font-medium">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $branch->employees_count }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('branches.edit', $branch) }}"
                                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>

                                <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                                      onsubmit="return confirm('Delete {{ $branch->name }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="text-sm text-red-500 hover:text-red-700 font-medium">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-gray-400">
                            No branches found. <a href="{{ route('branches.create') }}" class="text-indigo-600 hover:underline">Add one</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-app-layout>
