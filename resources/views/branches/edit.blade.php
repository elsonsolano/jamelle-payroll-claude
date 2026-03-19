<x-app-layout>
    <x-slot name="title">Edit Branch</x-slot>

    <div class="max-w-xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-5">Edit — {{ $branch->name }}</h2>

            <form method="POST" action="{{ route('branches.update', $branch) }}">
                @csrf @method('PUT')
                @include('branches._form')

                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Update Branch
                    </button>
                    <a href="{{ route('branches.index') }}"
                       class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
