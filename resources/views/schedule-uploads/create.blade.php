<x-app-layout>
    <x-slot name="title">Import Schedule</x-slot>

    <x-slot name="actions">
        <a href="{{ route('schedule-uploads.index') }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            ← Back
        </a>
    </x-slot>

    <div class="max-w-2xl space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

            <div>
                <h2 class="font-semibold text-gray-900">Import Schedule from JSON</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Upload the schedule image to <strong>claude.ai</strong> with the prompt below, then paste the resulting JSON here.
                </p>
            </div>

            @if($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('schedule-uploads.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch <span class="text-red-500">*</span></label>
                    <select name="branch_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select branch...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule JSON <span class="text-red-500">*</span></label>
                    <textarea name="schedule_json" rows="12" required
                              placeholder="Paste the JSON from claude.ai here..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-indigo-500">{{ old('schedule_json') }}</textarea>
                    @error('schedule_json')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <button type="submit"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        Import Schedule
                    </button>
                </div>
            </form>
        </div>

        {{-- Prompt reference card --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5" x-data="{ copied: false }">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-700">claude.ai Prompt</p>
                <button @click="navigator.clipboard.writeText($refs.prompt.innerText); copied = true; setTimeout(() => copied = false, 2000)"
                        class="text-xs px-3 py-1 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 transition"
                        x-text="copied ? '✓ Copied!' : 'Copy'">
                    Copy
                </button>
            </div>
            <pre x-ref="prompt" class="text-xs text-gray-600 whitespace-pre-wrap leading-relaxed">Parse this work schedule image and return ONLY a JSON object with no explanation, no markdown, no code fences.

Use this exact structure:
{
  "month": "March 2026",
  "rows": [
    {
      "date": "2026-03-23",
      "day": "Mon",
      "assignments": [
        {
          "name": "Eddie",
          "work_start_time": "08:00",
          "work_end_time": "17:00",
          "is_day_off": false,
          "branch_override": null,
          "notes": null
        }
      ]
    }
  ]
}

Rules:
- Shift headers like "8-5" = 08:00-17:00, "12-9" = 12:00-21:00, "1-10" = 13:00-22:00, "2-11" = 14:00-23:00, "8:30-5:30" = 08:30-17:30. All times in HH:MM 24-hour format.
- Multiple names in a cell separated by "/" are separate assignment entries.
- Name with "(ABR)" like "Mona(ABR)" -> name: "Mona", branch_override: "ABR"
- Name with OT like "Mariah(OT1HR)" -> name: "Mariah", notes: "OT1HR"
- DAY-OFF column entry of "-" means nobody is off — skip it.
- Return raw JSON only.</pre>
        </div>
    </div>
</x-app-layout>
