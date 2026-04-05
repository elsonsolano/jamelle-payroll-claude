<?php

namespace App\Console\Commands;

use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendTimeInReminders extends Command
{
    protected $signature   = 'notifications:time-in-reminders';
    protected $description = 'Send time-in push reminders to staff at 7:00 AM';

    public function handle(): void
    {
        $now = now();

        // Only fire in the 07:00–07:05 window
        $windowStart = $now->copy()->setTime(7, 0, 0);
        $windowEnd   = $now->copy()->setTime(7, 5, 0);

        if ($now->lt($windowStart) || $now->gte($windowEnd)) {
            return;
        }

        $today = $now->toDateString();

        $users = User::where('role', 'staff')
            ->with(['employee', 'pushSubscriptions'])
            ->whereHas('pushSubscriptions')
            ->get();

        $webPush = $this->makeWebPush();
        $payload = json_encode([
            'title' => 'Time to Clock In!',
            'body'  => 'Do not forget to clock in for today! Have a great shift!',
            'url'   => '/staff/dashboard',
        ]);

        $recipients = [];

        foreach ($users as $user) {
            $employee = $user->employee;
            if (! $employee) {
                continue;
            }

            $cacheKey = "time_in_reminder:{$user->id}:{$today}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            if ($this->isRestDay($employee, $today)) {
                continue;
            }

            $alreadyTimedIn = Dtr::where('employee_id', $employee->id)
                ->where('date', $today)
                ->whereNotNull('time_in')
                ->exists();

            if ($alreadyTimedIn) {
                continue;
            }

            foreach ($user->pushSubscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys'     => ['p256dh' => $sub->p256dh_key, 'auth' => $sub->auth_token],
                    ]),
                    $payload
                );
            }

            Cache::put($cacheKey, true, now()->endOfDay());
            $recipients[] = $employee->full_name;
        }

        foreach ($webPush->flush() as $report) {
            // fire and forget
        }

        $this->info('Time-in reminders sent to: ' . (count($recipients) ? implode(', ', $recipients) : 'none'));
    }

    private function isRestDay(Employee $employee, string $date): bool
    {
        $dayName = Carbon::parse($date)->format('l');

        $daily = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($daily) {
            return (bool) $daily->is_day_off;
        }

        $schedule = $employee->employeeSchedules()
            ->where('week_start_date', '<=', $date)
            ->orderByDesc('week_start_date')
            ->first();

        if (! $schedule) {
            return false;
        }

        return in_array($dayName, $schedule->rest_days ?? []);
    }

    private function makeWebPush(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);
    }
}
