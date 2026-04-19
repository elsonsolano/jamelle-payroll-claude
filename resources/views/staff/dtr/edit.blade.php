<x-staff-layout title="Edit DTR" :hide-header="true">

{{-- ── Back-nav header ── --}}
<div class="-mx-4 -mt-4">
    <div class="flex items-center justify-between gap-2 px-4 py-3" style="background:#6ea830;">
        <a href="{{ route('staff.dtr.index') }}"
           class="inline-flex items-center gap-[6px] text-[13px] font-semibold text-white rounded-[10px] px-[10px] py-[6px]"
           style="background:rgba(255,255,255,.15);">
            <svg class="w-[14px] h-[14px]" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="m15 6-6 6 6 6"/>
            </svg>
            DTR
        </a>
        <div class="text-right">
            <div class="text-[15px] font-bold text-white leading-tight">Edit DTR</div>
            <div class="text-[11px] font-medium leading-tight" style="color:rgba(255,255,255,.7);">
                {{ $dtr->date->format('D, M j Y') }}
            </div>
        </div>
    </div>
</div>

<script>
function dtrEditForm() {
    return {
        hasOt: {{ ($dtr->ot_status !== 'none' || old('has_ot')) ? 'true' : 'false' }},
    };
}
</script>

<form id="dtr-form" method="POST" action="{{ route('staff.dtr.update', $dtr) }}" x-data="dtrEditForm()">
@csrf
@method('PUT')

<div class="flex flex-col gap-[14px] pt-4">

    {{-- Timemark hint --}}
    <div class="flex items-start gap-2 rounded-xl p-[10px_12px] border"
         style="background:#f1f8e4; border-color:#cfe7a4;">
        <svg class="w-[14px] h-[14px] shrink-0 mt-[1px]" style="color:#557f24;" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M12 16v-4M12 8h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18"/>
        </svg>
        <p class="m-0 text-[11.5px] font-medium leading-[1.45]" style="color:#3f5e1b;">
            Enter times that <strong>exactly match</strong> your timemark device recording.
        </p>
    </div>

    {{-- Date --}}
    <div>
        <div class="text-[11px] font-bold uppercase tracking-[.06em] mb-[6px]" style="color:#6b7768;">Date</div>
        <input type="date" name="date"
               value="{{ old('date', $dtr->date->format('Y-m-d')) }}"
               max="{{ today()->format('Y-m-d') }}"
               class="w-full rounded-xl bg-white px-[14px] py-[11px] text-[14px] font-semibold border focus:ring-2 focus:ring-green-500 focus:border-green-400"
               style="border-width:1.5px; border-color:#d9ddd6; box-shadow:0 1px 2px rgba(15,20,16,.04); color:#0f1410;">
        @error('date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Punch times --}}
    <div>
        <div class="text-[11px] font-bold uppercase tracking-[.06em] mb-[6px]" style="color:#6b7768;">Punch times</div>
        <div class="grid grid-cols-2 gap-2">

            @foreach([
                ['label' => 'Time In',     'name' => 'time_in',  'val' => substr($dtr->time_in  ?? '', 0, 5)],
                ['label' => 'Start Break', 'name' => 'am_out',   'val' => substr($dtr->am_out   ?? '', 0, 5)],
                ['label' => 'End Break',   'name' => 'pm_in',    'val' => substr($dtr->pm_in    ?? '', 0, 5)],
                ['label' => 'Time Out',    'name' => 'time_out', 'val' => substr($dtr->time_out ?? '', 0, 5)],
            ] as $p)
            <div class="flex flex-col gap-[6px]">
                <label for="{{ $p['name'] }}"
                       class="text-[10px] font-bold uppercase tracking-[.08em]"
                       style="color:#6b7768;">{{ $p['label'] }}</label>
                <input type="time" id="{{ $p['name'] }}" name="{{ $p['name'] }}"
                       value="{{ old($p['name'], $p['val']) }}"
                       class="w-full rounded-xl bg-white px-3 py-[11px] text-[15px] font-semibold border focus:ring-2 focus:ring-green-500 focus:border-green-400"
                       style="border-width:1.5px; border-color:#d9ddd6; box-shadow:0 1px 2px rgba(15,20,16,.04); color:#0f1410;">
                @error($p['name']) <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            @endforeach

        </div>
    </div>

    {{-- Overtime --}}
    <div>
        <div class="text-[11px] font-bold uppercase tracking-[.06em] mb-[6px]" style="color:#6b7768;">Overtime</div>
        <div class="flex items-center justify-between gap-2.5 rounded-xl bg-white p-[12px_14px] border"
             style="border-width:1.5px; border-color:#d9ddd6; box-shadow:0 1px 2px rgba(15,20,16,.04);">
            <div class="flex flex-col gap-[2px]">
                <span class="text-[13px] font-semibold" style="color:#0f1410;">I have overtime</span>
                <span class="text-[11px]" style="color:#6b7768;">Shift exceeded scheduled hours</span>
            </div>
            <button type="button"
                    role="switch"
                    :aria-checked="hasOt.toString()"
                    class="relative w-[42px] h-6 rounded-full border-none cursor-pointer shrink-0 transition-colors duration-200"
                    :style="hasOt ? 'background:#8bc53f' : 'background:#d9ddd6'"
                    @click="hasOt = !hasOt">
                <span class="absolute top-[3px] left-[3px] w-[18px] h-[18px] rounded-full bg-white transition-transform duration-200"
                      style="box-shadow:0 1px 3px rgba(0,0,0,.2);"
                      :style="hasOt ? 'transform:translateX(18px)' : ''"></span>
            </button>
        </div>
        <input type="hidden" name="has_ot" :value="hasOt ? '1' : '0'">

        <div x-show="hasOt" x-cloak class="mt-2 flex flex-col gap-1">
            <input type="number" name="ot_hours"
                   value="{{ old('ot_hours', $dtr->overtime_hours > 0 ? $dtr->overtime_hours : '') }}"
                   min="0.5" max="24" step="0.25"
                   placeholder="e.g. 2, 1.5, or 0.5"
                   class="w-full border rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-green-500 bg-white"
                   style="border-color:#d9ddd6;">

            @if($dtr->ot_status === 'rejected')
                <p class="text-xs" style="color:#dc2626;">
                    Previous rejection: {{ $dtr->ot_rejection_reason ?? 'No reason given' }}.
                    Re-submitting will send for approval again.
                </p>
            @elseif($dtr->ot_status === 'pending')
                <p class="text-xs" style="color:#8a5a12;">OT is currently pending approval.</p>
            @else
                <p class="text-xs" style="color:#6b7768;">Your overtime will be sent for approval.</p>
            @endif

            @error('ot_hours') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Note --}}
    <div>
        <div class="text-[11px] font-bold uppercase tracking-[.06em] mb-[6px]" style="color:#6b7768;">
            Note
            <span style="font-weight:500; text-transform:none; letter-spacing:0; color:#8d9889;">(optional)</span>
        </div>
        <textarea name="notes" rows="3" maxlength="500"
                  placeholder="Note any reason — late, early out, or anything else…"
                  class="w-full rounded-xl px-[14px] py-[11px] text-[13px] leading-[1.5] resize-none focus:ring-2 focus:ring-green-500 bg-white border"
                  style="border-width:1.5px; border-color:#d9ddd6; box-shadow:0 1px 2px rgba(15,20,16,.04); min-height:70px;">{{ old('notes', $dtr->notes) }}</textarea>
        @error('notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

</div>

<div class="h-32"></div>

</form>

{{-- ── Sticky form footer ── --}}
<div class="fixed bottom-16 left-1/2 -translate-x-1/2 w-full max-w-lg z-20 grid gap-2 px-4 pt-[10px] pb-4 border-t"
     style="grid-template-columns:1fr 1.6fr; background:rgba(247,248,245,.9); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); border-color:#d9ddd6;">
    <a href="{{ route('staff.dtr.index') }}"
       class="text-center text-[13px] font-semibold py-[11px] rounded-xl bg-white border"
       style="border-width:1.5px; border-color:#d9ddd6; color:#1b2419;">
        Cancel
    </a>
    <button form="dtr-form" type="submit"
            class="text-[13px] font-bold py-[11px] rounded-xl text-white"
            style="background:#6ea830; box-shadow:0 4px 10px -4px rgba(110,168,48,.5);">
        Update DTR
    </button>
</div>

</x-staff-layout>
