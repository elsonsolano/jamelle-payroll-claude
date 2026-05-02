<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceScore;
use App\Models\DailySchedule;
use App\Models\EmployeeAttendanceBadge;
use App\Models\PayrollCutoff;
use App\Services\AttendanceScoringService;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AttendanceScoringService $attendanceScoringService,
        protected GamificationService $gamification,
    ) {}

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

        $quote              = $this->dailyQuote();
        $todaySchedule      = $this->resolveSchedule($employee, today());
        $tomorrowSchedule   = $this->resolveSchedule($employee, today()->addDay());
        $attendanceProgress = $this->attendanceProgress($employee, $todayDtr);
        $launchAt = Carbon::parse('2026-05-01 06:00:00', 'Asia/Manila');
        $comingSoon = now('Asia/Manila')->lt($launchAt);

        $achievementSummary = null;
        $rank = null;
        if (! $comingSoon) {
            $currentCutoff = $this->gamification->currentCutoffFor($employee);
            $achievementSummary = $this->gamification->achievementsData($employee, $currentCutoff);
            $rank = $this->gamification->rankFor($achievementSummary['total_points']);
        }

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
            'clockState', 'nextEvent', 'yesterdayNextEvent', 'attendanceProgress',
            'achievementSummary', 'rank', 'comingSoon'
        ));
    }

    private function attendanceProgress(\App\Models\Employee $employee, ?\App\Models\Dtr $todayDtr): array
    {
        $currentCutoff = PayrollCutoff::where('branch_id', $employee->branch_id)
            ->where('status', '!=', 'voided')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        $estimatePeriod = $currentCutoff ?? $this->virtualAttendanceCutoff($employee);
        $estimate = $this->attendanceScoringService->estimateEmployeeForCutoff($estimatePeriod, $employee);

        $latestOfficialScore = AttendanceScore::with('payrollCutoff')
            ->where('employee_id', $employee->id)
            ->whereHas('payrollCutoff', fn ($query) => $query->where('status', 'finalized'))
            ->latest('finalized_at')
            ->latest('id')
            ->first();

        $latestBadgeCutoffId = $latestOfficialScore?->payroll_cutoff_id;
        $recentBadges = EmployeeAttendanceBadge::with('badge', 'payrollCutoff')
            ->where('employee_id', $employee->id)
            ->whereHas('badge', fn ($query) => $query->where('active', true))
            ->when($latestBadgeCutoffId, fn ($query) => $query->where('payroll_cutoff_id', $latestBadgeCutoffId))
            ->latest('awarded_at')
            ->latest('id')
            ->limit(4)
            ->get();

        $totalBadgeCount = EmployeeAttendanceBadge::where('employee_id', $employee->id)
            ->whereHas('badge', fn ($query) => $query->where('active', true))
            ->count();

        $streak = $this->attendanceScoringService->completeDtrStreak(
            $employee,
            today(),
            $estimatePeriod->start_date,
        );

        $todayMessage = match (true) {
            ! $todayDtr || ! $todayDtr->time_in => 'Time in today to earn no-absence and no-late points.',
            ! ($todayDtr->time_in && $todayDtr->am_out && $todayDtr->pm_in && $todayDtr->time_out) => 'Finish today\'s DTR today to earn same-day points.',
            $this->attendanceScoringService->wasCompletedPromptly($todayDtr) => 'Nice. Today\'s DTR earned same-day points.',
            default => 'DTR complete, but same-day points are missed.',
        };

        return [
            'current_cutoff'         => $currentCutoff,
            'estimate_period'        => $estimatePeriod,
            'is_virtual_period'      => ! $currentCutoff,
            'estimate'               => $estimate,
            'latest_official_score'  => $latestOfficialScore,
            'complete_dtr_streak'    => $streak,
            'today_message'          => $todayMessage,
            'recent_badges'          => $recentBadges,
            'total_badge_count'      => $totalBadgeCount,
        ];
    }

    private function virtualAttendanceCutoff(\App\Models\Employee $employee): PayrollCutoff
    {
        [$startDate, $endDate] = $this->currentAttendancePeriod(today());

        return new PayrollCutoff([
            'branch_id'   => $employee->branch_id,
            'name'        => 'Current attendance period',
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'status'      => 'draft',
        ]);
    }

    private function currentAttendancePeriod(Carbon $date): array
    {
        $day = (int) $date->format('j');

        if ($day >= 14 && $day <= 29) {
            return [
                $date->copy()->day(14)->startOfDay(),
                $date->copy()->day(29)->startOfDay(),
            ];
        }

        if ($day >= 30) {
            return [
                $date->copy()->day(30)->startOfDay(),
                $date->copy()->addMonthNoOverflow()->day(13)->startOfDay(),
            ];
        }

        $previousMonth = $date->copy()->subMonthNoOverflow();

        return [
            $previousMonth->copy()->day(min(30, $previousMonth->daysInMonth))->startOfDay(),
            $date->copy()->day(13)->startOfDay(),
        ];
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
            // Love life / hugot
            'Hi mahal, eat ka na? Wag ka pagutom ha... Sorry, wrong send!',
            'Ang love life mo parang brownout — biglang nawawala, tapos hindi mo alam kailan babalik.',
            'Hindi ka ghoster, busy ka lang talaga… mag-reply sa ibang tao.',
            'Ang pag-ibig parang ulam sa handaan — akala mo para sa\'yo, pero naubusan ka rin pala.',
            'May mga tao talagang darating para turuan ka kung paano masaktan, hindi kung paano maging masaya.',
            'Iniwan ka niya hindi dahil may mali sa\'yo… kundi dahil may nakita siyang iba na mas gusto niya.',
            'Minsan hindi ka niya iniwan… hindi ka lang talaga niya pinili simula pa lang.',
            'Sorry ha, pero hindi ka nya mahal, mag move on ka na. Bye ka!',
            'Ang relasyon namin parang pila… akala ko ako na next, pero may sumingit pala.',
            'Ang relasyon namin parang order slip… akala mo sure na, pero biglang cancelled.',
            'Ang effort ko parang sauce… todo bigay, pero hindi rin pala sapat para tumamis.',

            // llaollao crew jokes
            'Sa llaollao, hindi ka lang gumagawa ng yogurt… gumagawa ka rin ng dahilan para ngumiti ang customer (kahit ikaw hindi na nakangiti).',
            'Ang tunay na laban sa llaollao: hindi ang pila… kundi ang ubos na toppings.',
            'Ang love life ko parang llaollao cup… akala mo puno na, pero may space pa pala sa iba.',
            'Hindi ka tunay na crew kung hindi ka pa nalilito sa \'dalawang sauce po, mix or separate?\'',
            'Sa llaollao, ang bilis ng oras… lalo na kapag sunod-sunod ang order na Sanum, tub, at takeout.',
            'Ang tunay na teamwork sa llaollao: isang kuha ng toppings, gets na agad ng kasama mo.',
            'Hindi mo kailangan ng gym… mag-llaollao ka lang, full body workout na.',
            'Pag peak hours, hindi ka na tao… isa ka nang yogurt machine.',
            'Sa llaollao, kahit pagod ka na, tuloy pa rin — kasi may susunod pang \'isa pa po\'.',

            // Work motivation
            'Hindi kailangan maging perfect — basta consistent ka, panalo ka na sa llaollao.',
            'Hindi natin kontrolado yung dami ng customer… pero kontrolado natin kung paano tayo magtrabaho.',
            'Pag sabay-sabay kayong pagod pero tuloy pa rin — yan ang tunay na teamwork.',
            'Hindi napapansin ng customer yung pagod natin… pero ramdam nila kapag maayos ang trabaho natin.',
            'Minsan magulo, minsan stressful — pero tandaan, kaya natin \'to kasi magkakasama tayo.',
            'Hindi kailangang mabilis lagi — kailangan tama, tuloy-tuloy, at may malasakit.',
            'Ang galing ng team hindi nasusukat sa tahimik na araw… kundi sa kung paano kayo kumilos pag peak hours.',
            'Kahit maliit na bagay — tamang serving, maayos na galaw — yan yung nagdadala ng malaking difference.',
            'Hindi araw-araw mataas energy… pero araw-araw may choice ka pa rin na gawin ng maayos yung trabaho.',
            'Sa dulo ng araw, hindi lang tayo nagbenta ng yogurt — nagbigay tayo ng magandang experience.',
            'Kapag napagod ka, okay lang — pero huwag mong kakalimutan na kaya mo pa.',
            'Walang solo dito — kapag may nahuhuli, tutulungan; kapag may nalulunod, sasalo.',
            'Hindi natin kailangan maging pinaka-mabilis na branch — basta solid ang galaw, automatic panalo.',
            'Yung teamwork na tahimik pero efficient — yan yung hindi napapansin, pero pinaka-malakas.',
            'Hindi man laging perfect ang araw — pero pwedeng maging proud ka sa effort mo.',
            'Ang saya sa trabaho parang llaollao cup… hindi kailangang puno, basta sapat para ngumiti ka.',
            'Ang teamwork parang toppings… iba-iba man, pero mas masarap kapag magkakasama.',
            'Ang hard work parang yogurt… sa simula mabagal, pero pag tuloy-tuloy, mapupuno rin.',
            'Ang effort parang sauce… konti lang minsan, pero kayang magpabago ng buong result.',
            'Ang teamwork parang pila… kapag maayos ang flow, lahat makakarating sa dulo.',
            'Ang saya parang toppings… mas dumadami kapag sinishare.',
            'Ang trabaho parang swirl… kailangan steady lang para maging maayos ang kalabasan.',
            'Ang teamwork parang order… malinaw dapat para walang sablay.',
            'Ang pagod parang yogurt machine… normal lang, pero tuloy pa rin ang labas.',
            'Ang success parang cup… hindi biglaan napupuno, dahan-dahan pero sigurado.',
            'Ang team natin parang Sanum… iba-iba ang laman, pero solid kapag pinagsama.',
            'Ang effort parang serving… hindi kailangang perpekto, basta consistent.',
            'Ang saya sa shift parang toppings… minsan konti, pero sapat para gumaan ang araw.',
            'Ang teamwork parang sauce… hindi laging napapansin, pero ramdam sa dulo.',
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
