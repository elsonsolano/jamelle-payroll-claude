<x-app-layout>
    <x-slot name="title">Edit Cutoff</x-slot>

    <div class="max-w-xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-5">Edit — {{ $cutoff->name }}</h2>
            <form method="POST" action="{{ route('payroll.cutoffs.update', $cutoff) }}">
                @csrf @method('PUT')
                @include('payroll.cutoffs._form')
                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Update Cutoff
                    </button>
                    <a href="{{ route('payroll.cutoffs.show', $cutoff) }}"
                       class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
