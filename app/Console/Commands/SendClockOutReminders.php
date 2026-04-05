<?php

namespace App\Console\Commands;

use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

        Log::info('[ClockOutReminder] Running', [
            'time'      => $now->toTimeString(),
            'window'    => $now->toTimeString() . ' – ' . $windowEnd->toTimeString(),
        ]);

        try {
            $users = User::where('role', 'staff')
                ->with(['employee', 'pushSubscriptions'])
                ->whereHas('pushSubscriptions')
                ->get();

            Log::info('[ClockOutReminder] Staff with subscriptions found', ['count' => $users->count()]);

            $webPush = $this->makeWebPush();
            $payload = json_encode([
                'title' => 'Time to Clock Out!',
                'body'  => "Don't forget to clock out! Appreciate your hard work as always!",
                'url'   => '/staff/dashboard',
            ]);

            $recipients = [];
            $skipped    = [];

            foreach ($users as $user) {
                $employee = $user->employee;
                if (! $employee) {
                    continue;
                }

                $cacheKey = "clock_out_reminder:{$user->id}:{$today}";
                if (Cache::has($cacheKey)) {
                    $skipped[] = $employee->full_name . ' (already sent)';
                    continue;
                }

                if ($this->isRestDay($employee, $today)) {
                    $skipped[] = $employee->full_name . ' (rest day)';
                    continue;
                }

                $alreadyClockedOut = Dtr::where('employee_id', $employee->id)
                    ->where('date', $today)
                    ->whereNotNull('time_out')
                    ->exists();

                if ($alreadyClockedOut) {
                    $skipped[] = $employee->full_name . ' (already clocked out)';
                    continue;
                }

                $target = $this->resolveNotificationTime($employee, $today);

                // Only send if the target falls within the current 5-minute window
                if ($target->lt($now) || $target->gte($windowEnd)) {
                    $skipped[] = $employee->full_name . ' (target ' . $target->toTimeString() . ' not in window)';
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
                $recipients[] = $employee->full_name . ' (target ' . $target->toTimeString() . ')';
            }

            if ($skipped) {
                Log::info('[ClockOutReminder] Skipped employees', ['employees' => $skipped]);
            }

            $sent   = 0;
            $failed = 0;
            foreach ($webPush->flush() as $report) {
                $report->isSuccess() ? $sent++ : $failed++;
                if (! $report->isSuccess()) {
                    Log::warning('[ClockOutReminder] Push delivery failed', [
                        'reason'   => $report->getReason(),
                        'endpoint' => $report->getRequest()?->getUri()?->__toString(),
                    ]);
                }
            }

            Log::info('[ClockOutReminder] Done', [
                'recipients' => $recipients ?: ['none'],
                'pushSent'   => $sent,
                'pushFailed' => $failed,
            ]);

            $this->info('[ClockOutReminder] Done — sent: ' . $sent . ', failed: ' . $failed);
        } catch (\Throwable $e) {
            Log::error('[ClockOutReminder] Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('[ClockOutReminder] Failed: ' . $e->getMessage());
        }
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
