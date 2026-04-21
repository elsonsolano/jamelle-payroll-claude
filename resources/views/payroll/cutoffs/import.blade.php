<x-app-layout>
    <x-slot name="title">Import Payroll — {{ $cutoff->name }}</x-slot>

    <x-slot name="actions">
        <a href="{{ route('payroll.cutoffs.show', $cutoff) }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back to Cutoff
        </a>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold text-gray-900 mb-1">Import from Excel</h2>
            <p class="text-sm text-gray-500 mb-6">
                Upload the payroll Excel file for <strong>{{ $cutoff->branch->name }}</strong>.
                The file must contain a sheet named <code class="bg-gray-100 px-1 rounded">PAYROLL</code>
                with employee rows starting at row 8.
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

            <form method="POST"
                  action="{{ route('payroll.cutoffs.import-excel.store', $cutoff) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Excel File (.xlsx)</label>
                    <input type="file"
                           name="excel_file"
                           accept=".xlsx,.xls"
                           required
                           class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-300 rounded-lg p-2">
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-amber-800">
                    <p class="font-semibold mb-1">Before uploading</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Employee names in the Excel must match names in the system exactly (Last, First format).</li>
                        <li>If any name doesn't match, the entire import will be blocked — correct and re-upload.</li>
                        <li>Importing will <strong>finalize</strong> the cutoff immediately.</li>
                        <li>Any existing entries for this cutoff will be replaced.</li>
                    </ul>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Import & Finalize
                    </button>
                    <a href="{{ route('payroll.cutoffs.show', $cutoff) }}"
                       class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Column reference --}}
        <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Expected Excel Column Layout (PAYROLL sheet)</h3>
            <div class="overflow-x-auto">
                <table class="text-xs text-gray-600 w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700">
                            <th class="border border-gray-200 px-2 py-1 text-left">Col</th>
                            <th class="border border-gray-200 px-2 py-1 text-left">Field</th>
                            <th class="border border-gray-200 px-2 py-1 text-left">Col</th>
                            <th class="border border-gray-200 px-2 py-1 text-left">Field</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['B','Name (Last, First)','N','SSS Deduction'],
                            ['D','Basic Wage/Day','O','PHIC Deduction'],
                            ['E','Regular Days','P','Pag-ibig Deduction'],
                            ['F','Overtime Hours','Q','Pag-ibig Loan'],
                            ['G','Basic Pay','R','S/L'],
                            ['H','Retirement Pay','S','Savings'],
                            ['I','13th Month Allocation','T','SSS Refund'],
                            ['J','Overtime Pay','U','Refund'],
                            ['L','Skill Allowance','V','13th Month (deduction)'],
                            ['M','Gross Amount','X','Net Amount'],
                        ] as [$c1,$f1,$c2,$f2])
                        <tr>
                            <td class="border border-gray-200 px-2 py-1 font-mono font-bold">{{ $c1 }}</td>
                            <td class="border border-gray-200 px-2 py-1">{{ $f1 }}</td>
                            <td class="border border-gray-200 px-2 py-1 font-mono font-bold">{{ $c2 }}</td>
                            <td class="border border-gray-200 px-2 py-1">{{ $f2 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
