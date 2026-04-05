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
            Log::info('[TimeInReminder] Skipped — outside 07:00–07:05 window', ['current_time' => $now->toTimeString()]);
            return;
        }

        $today = $now->toDateString();
        Log::info('[TimeInReminder] Running', ['date' => $today, 'time' => $now->toTimeString()]);

        try {
            $users = User::where('role', 'staff')
                ->with(['employee', 'pushSubscriptions'])
                ->whereHas('pushSubscriptions')
                ->get();

            Log::info('[TimeInReminder] Staff with subscriptions found', ['count' => $users->count()]);

            $webPush = $this->makeWebPush();
            $payload = json_encode([
                'title' => 'Time to Clock In!',
                'body'  => 'Do not forget to clock in for today! Have a great shift!',
                'url'   => '/staff/dashboard',
            ]);

            $recipients = [];
            $skipped    = [];

            foreach ($users as $user) {
                $employee = $user->employee;
                if (! $employee) {
                    continue;
                }

                $cacheKey = "time_in_reminder:{$user->id}:{$today}";
                if (Cache::has($cacheKey)) {
                    $skipped[] = $employee->full_name . ' (already sent)';
                    continue;
                }

                if ($this->isRestDay($employee, $today)) {
                    $skipped[] = $employee->full_name . ' (rest day)';
                    continue;
                }

                $alreadyTimedIn = Dtr::where('employee_id', $employee->id)
                    ->where('date', $today)
                    ->whereNotNull('time_in')
                    ->exists();

                if ($alreadyTimedIn) {
                    $skipped[] = $employee->full_name . ' (already timed in)';
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

            if ($skipped) {
                Log::info('[TimeInReminder] Skipped employees', ['employees' => $skipped]);
            }

            $sent   = 0;
            $failed = 0;
            foreach ($webPush->flush() as $report) {
                $report->isSuccess() ? $sent++ : $failed++;
                if (! $report->isSuccess()) {
                    Log::warning('[TimeInReminder] Push delivery failed', [
                        'reason'   => $report->getReason(),
                        'endpoint' => $report->getRequest()?->getUri()?->__toString(),
                    ]);
                }
            }

            Log::info('[TimeInReminder] Done', [
                'recipients' => $recipients ?: ['none'],
                'pushSent'   => $sent,
                'pushFailed' => $failed,
            ]);

            $this->info('[TimeInReminder] Done — sent: ' . $sent . ', failed: ' . $failed);
        } catch (\Throwable $e) {
            Log::error('[TimeInReminder] Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('[TimeInReminder] Failed: ' . $e->getMessage());
        }
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
