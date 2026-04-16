<x-app-layout>
    <x-slot name="title">Edit DTR — {{ $dtr->employee->full_name }}</x-slot>

    <div class="max-w-xl">

        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700">
            Editing DTR for <strong>{{ $dtr->employee->full_name }}</strong>
            on <strong>{{ $dtr->date->format('D, M d Y') }}</strong>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('dtr.update', $dtr) }}"
              x-data="{ hasOt: {{ $dtr->overtime_hours > 0 ? 'true' : 'false' }} }">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time In</label>
                        <input type="time" name="time_in"
                               value="{{ old('time_in', substr($dtr->time_in ?? '', 0, 5)) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('time_in') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Break</label>
                        <input type="time" name="am_out"
                               value="{{ old('am_out', substr($dtr->am_out ?? '', 0, 5)) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('am_out') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Break</label>
                        <input type="time" name="pm_in"
                               value="{{ old('pm_in', substr($dtr->pm_in ?? '', 0, 5)) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('pm_in') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time Out</label>
                        <input type="time" name="time_out"
                               value="{{ old('time_out', substr($dtr->time_out ?? '', 0, 5)) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('time_out') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- OT --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="hasOt"
                               class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        <span class="text-sm font-medium text-amber-800">Has overtime</span>
                    </label>
                    <div x-show="hasOt" x-cloak class="mt-2">
                        <input type="number" name="ot_hours"
                               value="{{ old('ot_hours', $dtr->overtime_hours > 0 ? $dtr->overtime_hours : '') }}"
                               min="0.25" max="24" step="0.25"
                               placeholder="Hours (e.g. 1, 1.5, 2.25)"
                               class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-amber-500 focus:border-amber-500">
                        @error('ot_hours') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        @if($dtr->ot_status !== 'none')
                            <p class="text-xs text-amber-600 mt-1">
                                Current OT status: <strong>{{ $dtr->ot_status }}</strong>.
                                Changing the hours will reset it to pending.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Pending" {{ old('status', $dtr->status) === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Approved" {{ old('status', $dtr->status) === 'Approved' ? 'selected' : '' }}>Approved</option>
                    </select>
                    @error('status') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Note <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea name="notes" rows="3" maxlength="500"
                              placeholder="Late, early out, or anything to note…"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none">{{ old('notes', $dtr->notes) }}</textarea>
                    @error('notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="mt-4 flex gap-3">
                <a href="{{ route('dtr.show', $dtr) }}"
                   class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                    Save Changes
                </button>
            </div>

        </form>
    </div>

</x-app-layout>
