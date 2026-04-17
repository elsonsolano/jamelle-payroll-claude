<x-staff-layout title="Log DTR">

    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700">
        Make sure the times you enter match exactly what is recorded in your timemark device.
    </div>

    <form method="POST" action="{{ route('staff.dtr.store') }}" x-data="{ hasOt: false }">
        @csrf

        <div class="space-y-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                       max="{{ date('Y-m-d') }}"
                       class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                @error('date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time In</label>
                    <input type="time" name="time_in" value="{{ old('time_in') }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500">
                    @error('time_in') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Break</label>
                    <input type="time" name="am_out" value="{{ old('am_out') }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500">
                    @error('am_out') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Break</label>
                    <input type="time" name="pm_in" value="{{ old('pm_in') }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500">
                    @error('pm_in') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time Out</label>
                    <input type="time" name="time_out" value="{{ old('time_out') }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500">
                    @error('time_out') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- OT Toggle --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="has_ot" value="1" x-model="hasOt"
                           @if(old('has_ot')) checked @endif
                           class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <span class="text-sm font-medium text-amber-800">I have overtime</span>
                </label>

                <div x-show="hasOt" x-cloak class="mt-3">
                    <label class="block text-sm font-medium text-amber-800 mb-1">Overtime Hours</label>
                    <input type="number" name="ot_hours" value="{{ old('ot_hours') }}"
                           min="0.5" max="24" step="any"
                           placeholder="e.g. 2, 1.5, or 0.5"
                           class="w-full border border-amber-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-amber-500 bg-white">
                    <p class="text-xs text-amber-600 mt-1">Your overtime will be sent for approval.</p>
                    @error('ot_hours') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Note <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea name="notes" rows="3" maxlength="500"
                          placeholder="Note any reason here — late, early out, or anything else…"
                          class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500 resize-none">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="mt-6 flex gap-3">
            <a href="{{ route('staff.dtr.index') }}"
               class="flex-1 text-center border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl text-sm">
                Cancel
            </a>
            <button type="submit"
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl text-sm transition">
                Submit DTR
            </button>
        </div>
    </form>

</x-staff-layout>
