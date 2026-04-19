<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DailySchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user     = Auth::user();
        $employee = $user->employee;

        $todayDtr = $employee->dtrs()
            ->whereDate('date', today())
            ->first();

        // Detect an incomplete overnight shift: yesterday has time_in but no time_out
        $yesterdayDtr = null;
        $candidateYesterday = $employee->dtrs()
            ->whereDate('date', today()->subDay())
            ->first();
        if ($candidateYesterday && $candidateYesterday->time_in && !$candidateYesterday->time_out) {
            $yesterdayDtr = $candidateYesterday;
        }

        $recentDtrs = $employee->dtrs()
            ->whereDate('date', '<', today())
            ->orderByDesc('date')
            ->limit(7)
            ->get();

        // Only needed for approvers
        $pendingApprovalCount = 0;
        if ($user->can_approve_ot || $user->isAdmin()) {
            $pendingApprovalCount = $this->pendingApprovalCount($user, $employee);
        }

        $quote             = $this->dailyQuote();
        $todaySchedule     = $this->resolveSchedule($employee, today());
        $tomorrowSchedule  = $this->resolveSchedule($employee, today()->addDay());

        // Clock state derived from today's DTR
        $clockState = 'none';
        if ($todayDtr) {
            if ($todayDtr->time_out) {
                $clockState = 'out';
            } elseif ($todayDtr->am_out && !$todayDtr->pm_in) {
                $clockState = 'break';
            } elseif ($todayDtr->time_in) {
                $clockState = 'in';
            }
        }

        // Next loggable event for today
        $nextEvent = null;
        if ($clockState === 'none') {
            $nextEvent = 'time_in';
        } elseif ($clockState === 'in') {
            $nextEvent = ($todayDtr->am_out && $todayDtr->pm_in) ? 'time_out' : 'am_out';
        } elseif ($clockState === 'break') {
            $nextEvent = 'pm_in';
        }

        // Next loggable event for yesterday's open shift
        $yesterdayNextEvent = null;
        if ($yesterdayDtr) {
            if (! $yesterdayDtr->am_out) {
                $yesterdayNextEvent = 'am_out';
            } elseif (! $yesterdayDtr->pm_in) {
                $yesterdayNextEvent = 'pm_in';
            } else {
                $yesterdayNextEvent = 'time_out';
            }
        }

        return view('staff.dashboard', compact(
            'employee', 'todayDtr', 'yesterdayDtr', 'recentDtrs', 'pendingApprovalCount',
            'quote', 'todaySchedule', 'tomorrowSchedule',
            'clockState', 'nextEvent', 'yesterdayNextEvent'
        ));
    }

    private function resolveSchedule(\App\Models\Employee $employee, \Carbon\Carbon $date): array
    {
        $daily = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $date->toDateString())
            ->first();

        if ($daily) {
            return [
                'is_day_off' => $daily->is_day_off,
                'start'      => $daily->work_start_time,
                'end'        => $daily->work_end_time,
                'source'     => 'daily',
            ];
        }

        $weekly = $employee->employeeSchedules()
            ->where('week_start_date', '<=', $date->toDateString())
            ->orderByDesc('week_start_date')
            ->first();

        if ($weekly) {
            $restDays  = $weekly->rest_days ?? ['Sunday'];
            $isRestDay = in_array($date->format('l'), $restDays);
            return [
                'is_day_off' => $isRestDay,
                'start'      => $isRestDay ? null : $weekly->work_start_time,
                'end'        => $isRestDay ? null : $weekly->work_end_time,
                'source'     => 'weekly',
            ];
        }

        return ['is_day_off' => null, 'start' => null, 'end' => null, 'source' => 'none'];
    }

    private function dailyQuote(): array
    {
        $quotes = [
            'Ang pag-ibig parang assignment — mas gusto mo gawin kapag deadline na at may kaagaw na.',
            'Mahal kita, pero minsan pag nagsasalita ka, naiisip ko rin bakit hindi ka na lang nag-text.',
            'Hindi ka panget, wala ka lang talagang laban sa mga taong may personality.',
            'Ang puso ko parang jeep — punuan na, pero pinipilit mo pa ring sumabit.',
            'Sa pag-ibig, mahalaga ang trust. Kaya nga nung sinabi mong \'wala lang yun,\' di ako naniwala.',
            'Ang love life mo parang brownout — biglang nawawala, tapos hindi mo alam kailan babalik.',
            'Hindi ka ghoster, busy ka lang talaga… mag-reply sa ibang tao.',
            'Ang pag-ibig parang ulam sa handaan — akala mo para sa\'yo, pero naubusan ka rin pala.',
            'Minsan hindi ka naman talaga iniwan… hindi ka lang talaga pinili. Aray, pero sige lang.',
            'Kung ang love ay blind, mukhang pati standards mo nadamay.',
            'Ang sakit hindi yung iniwan ka… kundi yung pinaramdam niya muna na ikaw na, bago siya nagbago.',
            'Hindi ka naman naging kulang… nagkataon lang na hindi ikaw yung gusto niyang buuin.',
            'Ang hirap kalimutan ng taong hindi ka naman pinili, pero siya pa rin yung pinipili mo araw-araw.',
            'Minsan hindi ka niya sinaktan… pinakita niya lang kung gaano ka ka-disposable.',
            'Pinaglaban mo siya, pero hindi ka naman pala niya ipinaglaban kahit kailan.',
            'Hindi pala sapat na mahal mo siya… kailangan pala mahal ka rin niya sa paraan na naiintindihan mo.',
            'Ang pinaka-masakit? Yung kaya ka niyang mawala nang parang wala lang.',
            'Hindi ka tanga… nagmahal ka lang ng taong hindi marunong magpahalaga.',
            'May mga tao talagang darating para turuan ka kung paano masaktan, hindi kung paano maging masaya.',
            'Iniwan ka niya hindi dahil may mali sa\'yo… kundi dahil may nakita siyang iba na mas gusto niya.',
            'Ang hirap maging option sa taong ginawa mong priority.',
            'Minsan hindi ka niya iniwan… hindi ka lang talaga niya pinili simula pa lang.',
            'Sa llaollao, hindi ka lang gumagawa ng yogurt… gumagawa ka rin ng dahilan para ngumiti ang customer (kahit ikaw hindi na nakangiti).',
            'Ang tunay na laban sa llaollao: hindi ang pila… kundi ang ubos na toppings.',
            'Ang love life ko parang llaollao cup… akala mo puno na, pero may space pa pala sa iba.',
            'Hindi ka tunay na crew kung hindi ka pa nalilito sa \'dalawang sauce po, mix or separate?\'',
            'Sa llaollao, ang bilis ng oras… lalo na kapag sunod-sunod ang order na Sanum, tub, at takeout.',
            'Ang tunay na teamwork sa llaollao: isang kuha ng toppings, gets na agad ng kasama mo.',
            'Hindi mo kailangan ng gym… mag-llaollao ka lang, full body workout na.',
            'Pag peak hours, hindi ka na tao… isa ka nang yogurt machine.',
            'Sa llaollao, kahit pagod ka na, tuloy pa rin — kasi may susunod pang \'isa pa po\'.',
            'Hindi kailangan maging perfect—basta consistent ka, panalo ka na sa llaollao.',
            'Hindi natin kontrolado yung dami ng customer… pero kontrolado natin kung paano tayo magtrabaho.',
            'Pag sabay-sabay kayong pagod pero tuloy pa rin—yan ang tunay na teamwork.',
            'Hindi napapansin ng customer yung pagod natin… pero ramdam nila kapag maayos ang trabaho natin.',
            'Minsan magulo, minsan stressful—pero tandaan, kaya natin \'to kasi magkakasama tayo.',
            'Hindi kailangang mabilis lagi—kailangan tama, tuloy-tuloy, at may malasakit.',
            'Ang galing ng team hindi nasusukat sa tahimik na araw… kundi sa kung paano kayo kumilos pag peak hours.',
            'Kahit maliit na bagay—tamang serving, maayos na galaw—yan yung nagdadala ng malaking difference.',
            'Hindi araw-araw mataas energy… pero araw-araw may choice ka pa rin na gawin ng maayos yung trabaho.',
            'Sa dulo ng araw, hindi lang tayo nagbenta ng yogurt—nagbigay tayo ng magandang experience.',
            'Kapag napagod ka, okay lang—pero huwag mong kakalimutan na kaya mo pa.',
            'Walang solo dito—kapag may nahuhuli, tutulungan; kapag may nalulunod, sasalo.',
            'Hindi natin kailangan maging pinaka-mabilis na branch—basta solid ang galaw, automatic panalo.',
            'Yung teamwork na tahimik pero efficient—yan yung hindi napapansin, pero pinaka-malakas.',
            'Hindi man laging perfect ang araw—pero pwedeng maging proud ka sa effort mo.',
        ];

        return ['text' => $quotes[array_rand($quotes)]];
    }

    private function pendingApprovalCount(\App\Models\User $user, \App\Models\Employee $employee): int
    {
        $branch       = $employee->branch;
        $isHeadOffice = strtolower(trim($branch->name)) === 'head office';

        if ($user->isAdmin()) {
            $headOffice = \App\Models\Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();

            $otCount = \App\Models\Dtr::where('ot_status', 'pending')
                ->whereHas('employee', function ($q) use ($headOffice) {
                    if ($headOffice) {
                        $q->where('branch_id', $headOffice->id)
                          ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
                    }
                })->count();

            $scheduleCount = \App\Models\ScheduleChangeRequest::where('status', 'pending')
                ->whereHas('employee', function ($q) use ($headOffice) {
                    if ($headOffice) {
                        $q->where('branch_id', $headOffice->id)
                          ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
                    }
                })->count();

            return $otCount + $scheduleCount;
        }

        if ($user->can_approve_ot && $isHeadOffice) {
            $otCount       = \App\Models\Dtr::where('ot_status', 'pending')->count();
            $scheduleCount = \App\Models\ScheduleChangeRequest::where('status', 'pending')->count();
            return $otCount + $scheduleCount;
        }

        if ($user->can_approve_ot && !$isHeadOffice) {
            $otCount = \App\Models\Dtr::where('ot_status', 'pending')
                ->whereHas('employee', function ($q) use ($branch, $employee) {
                    $q->where('branch_id', $branch->id)
                      ->where('id', '!=', $employee->id);
                })->count();

            $scheduleCount = \App\Models\ScheduleChangeRequest::where('status', 'pending')
                ->whereHas('employee', function ($q) use ($branch, $employee) {
                    $q->where('branch_id', $branch->id)
                      ->where('id', '!=', $employee->id);
                })->count();

            return $otCount + $scheduleCount;
        }

        return 0;
    }
}
