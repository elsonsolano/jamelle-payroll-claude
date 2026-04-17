<?php

namespace App\Notifications;

use App\Models\ScheduleChangeRequest;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleChangeRequested extends Notification
{
    use Queueable;

    public function __construct(public ScheduleChangeRequest $changeRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $employee = $this->changeRequest->employee;
        $date     = $this->changeRequest->date->format('M d, Y');

        return [
            'change_request_id' => $this->changeRequest->id,
            'employee_name'     => $employee->full_name,
            'date'              => $this->changeRequest->date->format('Y-m-d'),
            'message'           => "{$employee->full_name} requested a schedule change for {$date}.",
            'type'              => 'schedule_change_requested',
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        $employee = $this->changeRequest->employee;
        $date     = $this->changeRequest->date->format('M d, Y');

        return [
            'title' => 'Schedule Change Request',
            'body'  => "{$employee->full_name} requested a schedule change for {$date}.",
            'url'   => '/staff/approvals?tab=schedule',
        ];
    }
}
