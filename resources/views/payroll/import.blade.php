<x-app-layout>
    <x-slot name="title">Import Legacy Payroll Data</x-slot>

    <x-slot name="actions">
        <a href="{{ route('payroll.cutoffs.index') }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to Cutoffs
        </a>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold text-gray-900 mb-1">Import Legacy Payroll Data</h2>
            <p class="text-sm text-gray-500 mb-6">
                Import historical payroll records from the previous system. A new finalized cutoff will be created automatically — no branch required.
            </p>

            @if($errors->any())
                <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    <p class="font-semibold mb-1">Import failed</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('payroll.import.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cutoff Name</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="e.g. Dec 30 – Jan 13"
                           required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date"
                               name="start_date"
                               value="{{ old('start_date') }}"
                               required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date"
                               name="end_date"
                               value="{{ old('end_date') }}"
                               required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Excel File (.xlsx)</label>
                    <input type="file"
                           name="excel_file"
                           accept=".xlsx,.xls"
                           required
                           class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-300 rounded-lg p-2">
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-amber-800">
                    <p class="font-semibold mb-1">Before uploading</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Employee names must be in <strong>LASTNAME, FIRSTNAME</strong> format and match names in the system.</li>
                        <li>Employees can belong to any branch — the import searches system-wide.</li>
                        <li>If any name doesn't match, the entire import will be blocked — correct and re-upload.</li>
                        <li>The cutoff will be <strong>finalized immediately</strong> on import.</li>
                    </ul>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Import & Finalize
                    </button>
                    <a href="{{ route('payroll.cutoffs.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Column reference --}}
        <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Expected Excel Column Layout</h3>
            <div class="overflow-x-auto">
                <table class="text-xs text-gray-600 w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700">
                            <th class="border border-gray-200 px-3 py-2 text-left">Column</th>
                            <th class="border border-gray-200 px-3 py-2 text-left">Field</th>
                            <th class="border border-gray-200 px-3 py-2 text-left">Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['A', 'Name (LASTNAME, FIRSTNAME)', 'DELA CRUZ, JUAN'],
                            ['B', 'Basic Wage (daily rate)', '650'],
                            ['C', 'Basic Pay', '8,450.00'],
                            ['D', 'Net Amount', '7,900.00'],
                        ] as [$col, $field, $example])
                        <tr>
                            <td class="border border-gray-200 px-3 py-2 font-mono font-bold">{{ $col }}</td>
                            <td class="border border-gray-200 px-3 py-2">{{ $field }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-400">{{ $example }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400 mt-3">Row 1 must be the header row. Data starts from row 2. The sheet name does not matter.</p>
        </div>
    </div>
</x-app-layout>
