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

class SendClockOutReminders extends Command
{
    protected $signature   = 'notifications:clock-out-reminders';
    protected $description = 'Send clock-out push reminders 15 minutes before shift end (fallback: 9:00 PM)';

    public function handle(): void
    {
        $now       = now();
        $today     = $now->toDateString();
        $windowEnd = $now->copy()->addMinutes(5);

        $users = User::where('role', 'staff')
            ->with(['employee', 'pushSubscriptions'])
            ->whereHas('pushSubscriptions')
            ->get();

        $webPush = $this->makeWebPush();
        $payload = json_encode([
            'title' => 'Time to Clock Out!',
            'body'  => "Don't forget to clock out! Appreciate your hard work as always!",
            'url'   => '/staff/dashboard',
        ]);

        $recipients = [];

        foreach ($users as $user) {
            $employee = $user->employee;
            if (! $employee) {
                continue;
            }

            $cacheKey = "clock_out_reminder:{$user->id}:{$today}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            if ($this->isRestDay($employee, $today)) {
                continue;
            }

            $alreadyClockedOut = Dtr::where('employee_id', $employee->id)
                ->where('date', $today)
                ->whereNotNull('time_out')
                ->exists();

            if ($alreadyClockedOut) {
                continue;
            }

            $target = $this->resolveNotificationTime($employee, $today);

            // Only send if the target falls within the current 5-minute window
            if ($target->lt($now) || $target->gte($windowEnd)) {
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

        $this->info('Clock-out reminders sent to: ' . (count($recipients) ? implode(', ', $recipients) : 'none'));
    }

    private function resolveNotificationTime(Employee $employee, string $today): Carbon
    {
        $daily = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($daily && $daily->work_end_time) {
            return Carbon::parse($today . ' ' . $daily->work_end_time)->subMinutes(15);
        }

        $schedule = $employee->employeeSchedules()
            ->where('week_start_date', '<=', $today)
            ->orderByDesc('week_start_date')
            ->first();

        if ($schedule && $schedule->work_end_time) {
            return Carbon::parse($today . ' ' . $schedule->work_end_time)->subMinutes(15);
        }

        // No schedule set — fallback to 9:00 PM
        return Carbon::parse($today . ' 21:00:00');
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
