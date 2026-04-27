<x-staff-layout title="My Schedule">

@push('head')
<style>
  .sched-section-hd {
    position: sticky;
    top: 60px;
    z-index: 9;
    background: rgba(247,248,245,.94);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
  }
  .sched-section-hd.today-sec {
    background: rgba(241,248,228,.96);
  }
  .schedule-target-row {
    scroll-margin-top: 112px;
  }
</style>
@endpush

<script>
function scheduleSheet() {
    return {
        open: false,
        date: '',
        dateLabel: '',
        currentStart: '',
        currentEnd: '',
        isCurrentDayOff: false,
        noCurrentSchedule: false,
        existingId: null,
        existingStatus: null,
        requestType: 'time',
        newStart: '',
        newEnd: '',
        reason: '',
        rejectionReason: '',

        openFor(data) {
            this.date              = data.date;
            this.dateLabel         = data.dateLabel;
            this.currentStart      = data.currentStart;
            this.currentEnd        = data.currentEnd;
            this.isCurrentDayOff   = data.isCurrentDayOff;
            this.noCurrentSchedule = data.noCurrentSchedule;
            this.existingId        = data.existingId;
            this.existingStatus    = data.existingStatus;
            this.rejectionReason   = data.rejectionReason || '';

            if (data.existingId && data.existingStatus === 'pending') {
                this.requestType = data.existingIsDayOff ? 'day_off' : 'time';
                this.newStart    = data.existingStart  || '';
                this.newEnd      = data.existingEnd    || '';
                this.reason      = data.existingReason || '';
            } else {
                this.requestType = 'time';
                this.newStart    = '';
                this.newEnd      = '';
                this.reason      = '';
            }
            this.open = true;
        },

        get isEdit() {
            return this.existingId && this.existingStatus === 'pending';
        },

        fmtTime(val) {
            if (!val) return '';
            try {
                return new Date('1970-01-01T' + val).toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit', hour12:true });
            } catch(e) { return val; }
        },

        get currentTimeLabel() {
            if (this.noCurrentSchedule) return 'No schedule assigned';
            if (this.isCurrentDayOff)   return 'Rest Day';
            if (this.currentStart && this.currentEnd)
                return this.fmtTime(this.currentStart) + ' – ' + this.fmtTime(this.currentEnd);
            return 'No schedule';
        },

        get sheetTitle() {
            if (this.isEdit) return 'Edit Schedule Request';
            if (this.existingStatus === 'rejected') return 'Re-submit Schedule Request';
            return 'Request Schedule Change';
        },

        get submitLabel() {
            return this.isEdit ? 'Update Request' : 'Submit Request';
        },
    };
}
</script>

<div x-data="scheduleSheet()">

    {{-- ── Schedule list ── --}}
    <div class="-mx-4 -mt-4">

        @php
            $shownPast     = false;
            $shownToday    = false;
            $shownUpcoming = false;
        @endphp

        @foreach($days as $dateStr => $day)
            @php
                $isToday = $day['date']->isToday();
                $isPast  = $day['date']->isPast() && !$isToday;
                $isOff   = $day['is_day_off'];
                $noSched = $day['source'] === 'none';
                $changeReq = $changeRequests->get($dateStr);

                $hasPending  = $changeReq && $changeReq->status === 'pending';
                $hasRejected = $changeReq && $changeReq->status === 'rejected';
                $isHighlighted = $highlightedDate === $dateStr;

                $changeData = json_encode([
                    'date'              => $dateStr,
                    'dateLabel'         => $day['date']->format('D, M d Y'),
                    'currentStart'      => $day['start'],
                    'currentEnd'        => $day['end'],
                    'isCurrentDayOff'   => (bool) $isOff,
                    'noCurrentSchedule' => $noSched,
                    'existingId'        => $changeReq?->id,
                    'existingStatus'    => $changeReq?->status,
                    'existingIsDayOff'  => (bool) $changeReq?->is_day_off,
                    'existingStart'     => $changeReq?->requested_work_start_time,
                    'existingEnd'       => $changeReq?->requested_work_end_time,
                    'existingReason'    => $changeReq?->reason,
                    'rejectionReason'   => $changeReq?->rejection_reason,
                ]);
            @endphp

            {{-- Section headers --}}
            @if($isPast && !$shownPast)
                @php $shownPast = true; @endphp
                <div class="sched-section-hd flex items-center justify-between px-[18px] py-[10px]"
                     role="heading" aria-level="2">
                    <span class="text-[10px] font-bold tracking-[.12em] uppercase" style="color:#8d9889;">PAST</span>
                </div>
            @endif

            @if($isToday && !$shownToday)
                @php $shownToday = true; @endphp
                <div class="sched-section-hd today-sec flex items-center justify-between px-[18px] py-[10px]"
                     role="heading" aria-level="2">
                    <span class="text-[10px] font-bold tracking-[.12em] uppercase" style="color:#557f24;">TODAY</span>
                </div>
            @endif

            @if(!$isPast && !$isToday && !$shownUpcoming)
                @php $shownUpcoming = true; @endphp
                <div class="sched-section-hd flex items-center justify-between px-[18px] py-[10px]"
                     role="heading" aria-level="2">
                    <span class="text-[10px] font-bold tracking-[.12em] uppercase" style="color:#8d9889;">UPCOMING</span>
                </div>
            @endif

            {{-- Day row --}}
            <div role="listitem"
                 id="schedule-date-{{ $dateStr }}"
                 aria-label="{{ $day['date']->format('l F j') }}{{ !$noSched && !$isOff && $day['start'] ? ', '.(\Carbon\Carbon::parse($day['start'])->format('g:i A')).' to '.(\Carbon\Carbon::parse($day['end'])->format('g:i A')) : '' }}"
                 class="schedule-target-row relative flex items-start gap-3 px-[18px] py-[10px] border-b {{ $isHighlighted ? 'ring-2 ring-offset-0' : '' }}"
                 style="
                     border-color:#eef1ec;
                     {{ $isPast ? 'opacity:.55;' : '' }}
                     {{ $isToday ? 'background:linear-gradient(to right,#f1f8e4 0%,rgba(241,248,228,.4) 100%);' : '' }}
                     {{ $isHighlighted ? 'background:#fff7ed; --tw-ring-color:#fb923c;' : '' }}
                 ">

                {{-- Green left border for today --}}
                @if($isToday)
                    <div class="absolute left-0 top-0 bottom-0 w-[3px] rounded-r-sm" style="background:#8bc53f;"></div>
                @endif

                {{-- Date column --}}
                <div class="flex flex-col items-center shrink-0 pt-[1px]" style="min-width:36px;">
                    <span class="text-[9px] font-bold tracking-[.06em] uppercase" style="color:#8d9889;">
                        {{ strtoupper($day['date']->format('D')) }}
                    </span>
                    <span class="text-[20px] font-bold leading-none tracking-tight"
                          style="color:{{ $isToday ? '#557f24' : ($isPast ? '#8d9889' : '#0f1410') }};">
                        {{ $day['date']->format('j') }}
                    </span>
                    <span class="text-[9px] font-semibold" style="color:#6b7768;">
                        {{ strtoupper($day['date']->format('M')) }}
                    </span>
                </div>

                {{-- Shift info --}}
                <div class="flex-1 min-w-0 flex flex-col gap-[3px] pt-[2px]">

                    {{-- Time / status --}}
                    @if($noSched)
                        <span class="text-[14px] font-medium italic" style="color:#b4bcb0;">No schedule set</span>
                    @elseif($isOff)
                        <span class="text-[14px] font-semibold" style="color:#f4a53c;">Rest Day</span>
                    @else
                        <span class="text-[14px] font-semibold tracking-tight" style="color:#0f1410;">
                            {{ $day['start'] ? \Carbon\Carbon::parse($day['start'])->format('g:i A') : '—' }}
                            –
                            {{ $day['end']   ? \Carbon\Carbon::parse($day['end'])->format('g:i A')   : '—' }}
                        </span>
                    @endif

                    {{-- Department --}}
                    @if(!empty($day['branch']))
                        <span class="text-[11px] font-medium" style="color:#557f24;">{{ $day['branch'] }}</span>
                    @endif

                    {{-- Status chips --}}
                    @php $hasChips = $isToday || $hasPending || $hasRejected; @endphp
                    @if($hasChips)
                        <div class="flex flex-wrap gap-1 mt-[2px]">
                            @if($isToday)
                                <span class="chip text-[10px] font-bold px-2 py-[2px] rounded-full text-white"
                                      style="background:#6ea830;">Today</span>
                            @endif
                            @if($hasPending)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-[2px] rounded-full border"
                                      style="background:#fdf2df; color:#8a5a12; border-color:#f8d99a;">
                                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v5l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18"/></svg>
                                    Pending
                                </span>
                            @endif
                            @if($hasRejected)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-[2px] rounded-full border"
                                      style="background:#fef0ee; color:#7c2e28; border-color:#f9c4be;">
                                    <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m18 6-12 12M6 6l12 12"/></svg>
                                    Change Rejected
                                </span>
                            @endif
                        </div>
                    @endif

                    {{-- Rejection reason card --}}
                    @if($hasRejected && $changeReq->rejection_reason)
                        <div role="alert"
                             class="mt-1 px-[10px] py-[7px] rounded-lg text-[11px] leading-[1.4] border"
                             style="background:#fef0ee; border-color:#f9c4be; color:#7c2e28;">
                            <strong class="font-bold">Reason:</strong> {{ $changeReq->rejection_reason }}
                        </div>
                    @endif

                </div>

                {{-- Action column --}}
                <div class="flex flex-col items-end shrink-0 pt-[2px]">
                    @if(!$isPast)
                        @if($hasPending)
                            <div class="w-[6px] h-[6px] rounded-full mt-[6px]" style="background:#d9ddd6;"></div>
                        @elseif($hasRejected)
                            <button type="button"
                                    @click="openFor({{ $changeData }})"
                                    class="text-[11px] font-bold px-[10px] py-[5px] rounded-lg border-[1.5px] whitespace-nowrap"
                                    style="border-color:#f4a53c; color:#8a5a12; background:#fdf2df;">
                                Re-submit
                            </button>
                        @else
                            <button type="button"
                                    @click="openFor({{ $changeData }})"
                                    class="text-[11px] font-bold px-[10px] py-[5px] rounded-lg border-[1.5px] whitespace-nowrap"
                                    style="border-color:#d9ddd6; color:#1b2419; background:#fff;">
                                Request
                            </button>
                        @endif
                    @endif
                </div>

            </div>
        @endforeach

    </div>

    {{-- ── Bottom Sheet Overlay ── --}}
    <div x-show="open"
         x-cloak
         role="dialog"
         aria-modal="true"
         class="fixed inset-0 z-50 flex flex-col justify-end"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Backdrop --}}
        <div class="absolute inset-0" style="background:rgba(15,20,16,.45);" @click="open = false"></div>

        {{-- Sheet --}}
        <div class="relative rounded-t-[24px] max-h-[90vh] overflow-y-auto flex flex-col"
             style="background:#fff; box-shadow:0 -8px 32px -4px rgba(15,20,16,.14),0 -2px 8px rgba(15,20,16,.06);"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">

            {{-- Drag handle --}}
            <div class="w-9 h-1 rounded-full mx-auto mt-[10px]" style="background:#d9ddd6;"></div>

            {{-- Sheet header --}}
            <div class="px-5 pt-[14px] pb-[10px] border-b" style="border-color:#eef1ec;">
                <p class="text-[11px] font-semibold mb-[3px]" style="color:#6b7768;" x-text="dateLabel"></p>
                <h3 class="text-[17px] font-bold tracking-tight m-0" style="color:#0f1410;" x-text="sheetTitle"></h3>
            </div>

            {{-- Sheet body --}}
            <div class="flex-1 overflow-y-auto px-5 py-[14px] flex flex-col gap-[14px]">

                {{-- Current schedule card --}}
                <div class="rounded-xl px-[14px] py-[10px] border" style="background:#f7f8f5; border-color:#d9ddd6;">
                    <p class="text-[10px] font-bold tracking-[.1em] uppercase mb-1" style="color:#6b7768;">Current Schedule</p>
                    <p class="text-[17px] font-bold tracking-tight" style="color:#0f1410;" x-text="currentTimeLabel"></p>
                </div>

                {{-- Rejection reason when re-submitting --}}
                <template x-if="existingStatus === 'rejected' && rejectionReason">
                    <div class="rounded-xl px-[14px] py-[10px] border" style="background:#fef0ee; border-color:#f9c4be;">
                        <p class="text-[11px] font-bold mb-[2px]" style="color:#7c2e28;">Previous rejection reason</p>
                        <p class="text-[13px]" style="color:#7c2e28;" x-text="rejectionReason"></p>
                    </div>
                </template>

                <form :id="'sheet-form-' + date"
                      :action="isEdit ? '/staff/schedule-change-requests/' + existingId : '/staff/schedule-change-requests'"
                      method="POST"
                      class="flex flex-col gap-[14px]">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="date" :value="date">
                    <input type="hidden" name="is_day_off" :value="requestType === 'day_off' ? '1' : '0'">

                    {{-- Request type toggle --}}
                    <div class="flex flex-col gap-[6px]">
                        <p class="text-[12px] font-semibold" style="color:#4a5748;">I want to request</p>
                        <div class="grid grid-cols-2 gap-[3px] rounded-xl p-[3px]" style="background:#eef1ec;">
                            <button type="button"
                                    @click="requestType = 'time'"
                                    :style="requestType === 'time' ? 'background:#fff; color:#0f1410; box-shadow:0 4px 10px -4px rgba(15,20,16,.08),0 2px 4px rgba(15,20,16,.04);' : 'background:transparent; color:#6b7768;'"
                                    class="text-[13px] font-semibold py-[9px] px-3 rounded-[10px] border-none transition-all duration-150 cursor-pointer">
                                Different Time
                            </button>
                            <button type="button"
                                    @click="requestType = 'day_off'"
                                    :style="requestType === 'day_off' ? 'background:#fff; color:#0f1410; box-shadow:0 4px 10px -4px rgba(15,20,16,.08),0 2px 4px rgba(15,20,16,.04);' : 'background:transparent; color:#6b7768;'"
                                    class="text-[13px] font-semibold py-[9px] px-3 rounded-[10px] border-none transition-all duration-150 cursor-pointer">
                                Day Off
                            </button>
                        </div>
                    </div>

                    {{-- Different time fields --}}
                    <div x-show="requestType === 'time'" x-cloak class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-[5px]">
                            <label for="sheet-new-start" class="text-[11px] font-bold tracking-[.04em]" style="color:#4a5748;">New Start</label>
                            <input type="time" id="sheet-new-start" name="requested_work_start_time" x-model="newStart"
                                   class="w-full rounded-xl border-[1.5px] px-3 py-[10px] text-[15px] font-semibold focus:ring-2 focus:ring-green-500"
                                   style="border-color:#d9ddd6; background:#fff; color:#0f1410; box-shadow:0 1px 2px rgba(15,20,16,.04);">
                        </div>
                        <div class="flex flex-col gap-[5px]">
                            <label for="sheet-new-end" class="text-[11px] font-bold tracking-[.04em]" style="color:#4a5748;">New End</label>
                            <input type="time" id="sheet-new-end" name="requested_work_end_time" x-model="newEnd"
                                   class="w-full rounded-xl border-[1.5px] px-3 py-[10px] text-[15px] font-semibold focus:ring-2 focus:ring-green-500"
                                   style="border-color:#d9ddd6; background:#fff; color:#0f1410; box-shadow:0 1px 2px rgba(15,20,16,.04);">
                        </div>
                    </div>

                    {{-- Day off confirmation --}}
                    <div x-show="requestType === 'day_off'" x-cloak
                         class="rounded-xl border-[1.5px] px-[14px] py-[14px] text-center"
                         style="background:#fdf2df; border-color:#f8d99a;">
                        <div class="text-[28px] mb-[6px]">☀️</div>
                        <p class="text-[14px] font-bold" style="color:#8a5a12;">Requesting a day off</p>
                        <p class="text-[12px] mt-[2px]" style="color:#8a5a12; opacity:.8;"
                           x-text="dateLabel + (currentStart && currentEnd ? ' · Currently ' + currentTimeLabel : '')"></p>
                    </div>

                    {{-- Reason --}}
                    <div class="flex flex-col gap-[5px]">
                        <label class="text-[11px] font-bold tracking-[.04em]" style="color:#4a5748;">
                            Reason <span style="color:#d86a5f; margin-left:2px;">*</span>
                        </label>
                        <textarea name="reason" x-model="reason" rows="3" maxlength="500" required
                                  placeholder="Why do you need this schedule change?"
                                  class="rounded-xl border-[1.5px] px-[14px] py-[11px] text-[13px] leading-[1.5] resize-none"
                                  style="background:#fff; border-color:#d9ddd6; box-shadow:0 1px 2px rgba(15,20,16,.04); min-height:72px; color:#0f1410;"></textarea>
                    </div>

                    @error('requested_work_start_time')
                        <p class="text-xs" style="color:#d86a5f;">{{ $message }}</p>
                    @enderror

                </form>

                {{-- Cancel request (outside form, only for edits) --}}
                <template x-if="isEdit">
                    <form :action="'/staff/schedule-change-requests/' + existingId" method="POST" class="-mt-2">
                        @csrf
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit"
                                onclick="return confirm('Cancel this schedule change request?')"
                                class="w-full text-center text-[13px] font-medium py-2"
                                style="color:#d86a5f;">
                            Cancel Request
                        </button>
                    </form>
                </template>

            </div>

            {{-- Sheet footer --}}
            <div class="px-5 pb-4 pt-[10px] grid gap-2 border-t" style="grid-template-columns:1fr 1.6fr; border-color:#eef1ec; background:rgba(247,248,245,.96); backdrop-filter:blur(10px);">
                <button type="button"
                        @click="open = false"
                        class="text-[13px] font-semibold py-[11px] rounded-xl border-[1.5px] cursor-pointer"
                        style="background:#fff; border-color:#d9ddd6; color:#1b2419;">
                    Close
                </button>
                <button type="submit"
                        :form="'sheet-form-' + date"
                        class="text-[13px] font-bold py-[11px] rounded-xl text-white border-none cursor-pointer"
                        style="background:#6ea830; box-shadow:0 4px 10px -4px rgba(110,168,48,.5);"
                        x-text="submitLabel">
                </button>
            </div>

        </div>
    </div>

</div>

</x-staff-layout>
