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

        $quote         = $this->dailyQuote();
        $todaySchedule = $this->todaySchedule($employee);

        return view('staff.dashboard', compact(
            'employee', 'todayDtr', 'yesterdayDtr', 'recentDtrs', 'pendingApprovalCount', 'quote', 'todaySchedule'
        ));
    }

    private function todaySchedule(\App\Models\Employee $employee): array
    {
        $today = today();

        $daily = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $today->toDateString())
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
            ->where('week_start_date', '<=', $today->toDateString())
            ->orderByDesc('week_start_date')
            ->first();

        if ($weekly) {
            $restDays  = $weekly->rest_days ?? ['Sunday'];
            $isRestDay = in_array($today->format('l'), $restDays);
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
            ['text' => 'The secret of getting ahead is getting started.', 'author' => 'Mark Twain'],
            ['text' => 'Hard work beats talent when talent doesn\'t work hard.', 'author' => 'Tim Notke'],
            ['text' => 'Your work is going to fill a large part of your life, and the only way to be truly satisfied is to do what you believe is great work.', 'author' => 'Steve Jobs'],
            ['text' => 'Success is not the key to happiness. Happiness is the key to success. If you love what you are doing, you will be successful.', 'author' => 'Albert Schweitzer'],
            ['text' => 'It always seems impossible until it\'s done.', 'author' => 'Nelson Mandela'],
            ['text' => 'Don\'t watch the clock; do what it does. Keep going.', 'author' => 'Sam Levenson'],
            ['text' => 'Opportunities are usually disguised as hard work, so most people don\'t recognize them.', 'author' => 'Ann Landers'],
            ['text' => 'The only way to do great work is to love what you do.', 'author' => 'Steve Jobs'],
            ['text' => 'Believe you can and you\'re halfway there.', 'author' => 'Theodore Roosevelt'],
            ['text' => 'Start where you are. Use what you have. Do what you can.', 'author' => 'Arthur Ashe'],
            ['text' => 'What you do today can improve all your tomorrows.', 'author' => 'Ralph Marston'],
            ['text' => 'The difference between ordinary and extraordinary is that little extra.', 'author' => 'Jimmy Johnson'],
            ['text' => 'You don\'t have to be great to start, but you have to start to be great.', 'author' => 'Zig Ziglar'],
            ['text' => 'Ang hindi marunong lumingon sa pinanggalingan ay hindi makararating sa paroroonan.', 'author' => 'Filipino Proverb'],
            ['text' => 'Little by little, one travels far.', 'author' => 'J.R.R. Tolkien'],
            ['text' => 'Excellence is not a destination but a continuous journey that never ends.', 'author' => 'Brian Tracy'],
            ['text' => 'Do what you can, with what you have, where you are.', 'author' => 'Theodore Roosevelt'],
            ['text' => 'Your attitude, not your aptitude, will determine your altitude.', 'author' => 'Zig Ziglar'],
            ['text' => 'Take care of your body. It\'s the only place you have to live.', 'author' => 'Jim Rohn'],
            ['text' => 'Rest when you\'re weary. Refresh and renew yourself, your body, your mind, your spirit.', 'author' => 'Ralph Marston'],
            ['text' => 'Kahit mabagal, huwag lamang titigil.', 'author' => 'Filipino Proverb'],
            ['text' => 'Success usually comes to those who are too busy to be looking for it.', 'author' => 'Henry David Thoreau'],
            ['text' => 'Hustle in silence and let your success make the noise.', 'author' => 'Unknown'],
            ['text' => 'Every expert was once a beginner. Every pro was once an amateur.', 'author' => 'Robin Sharma'],
            ['text' => 'Be so good they can\'t ignore you.', 'author' => 'Steve Martin'],
            ['text' => 'A year from now you may wish you had started today.', 'author' => 'Karen Lamb'],
            ['text' => 'Push yourself, because no one else is going to do it for you.', 'author' => 'Unknown'],
            ['text' => 'Great things never come from comfort zones.', 'author' => 'Unknown'],
            ['text' => 'Dream it. Wish it. Do it.', 'author' => 'Unknown'],
            ['text' => 'Walang matamis na tagumpay na hindi dumaan sa mapait na paghihirap.', 'author' => 'Filipino Proverb'],
        ];

        $index = today()->dayOfYear % count($quotes);
        return $quotes[$index];
    }

    private function pendingApprovalCount(\App\Models\User $user, \App\Models\Employee $employee): int
    {
        $branch       = $employee->branch;
        $isHeadOffice = strtolower(trim($branch->name)) === 'head office';

        if ($user->isAdmin()) {
            // Admin approves Head Office can_approve_ot employees
            return \App\Models\Dtr::where('ot_status', 'pending')
                ->whereHas('employee', function ($q) {
                    $headOffice = \App\Models\Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
                    if ($headOffice) {
                        $q->where('branch_id', $headOffice->id)
                          ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
                    }
                })->count();
        }

        if ($user->can_approve_ot && $isHeadOffice) {
            // HO approver → approves all pending OT across all branches
            return \App\Models\Dtr::where('ot_status', 'pending')->count();
        }

        if ($user->can_approve_ot && !$isHeadOffice) {
            // Branch approver → approves all staff in same branch except themselves
            return \App\Models\Dtr::where('ot_status', 'pending')
                ->whereHas('employee', function ($q) use ($branch, $employee) {
                    $q->where('branch_id', $branch->id)
                      ->where('id', '!=', $employee->id);
                })->count();
        }

        return 0;
    }
}
