<?php

namespace App\Notifications;

use App\Models\ScheduleChangeRequest;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduleChangeApproved extends Notification
{
    use Queueable;

    public function __construct(
        public ScheduleChangeRequest $changeRequest,
        public string $approverName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $date = $this->changeRequest->date->format('M d, Y');

        return [
            'change_request_id' => $this->changeRequest->id,
            'date'              => $this->changeRequest->date->format('Y-m-d'),
            'message'           => "Your schedule change request for {$date} has been approved.",
            'approver_name'     => $this->approverName,
            'type'              => 'schedule_change_approved',
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        $date = $this->changeRequest->date->format('M d, Y');

        return [
            'title' => 'Schedule Change Approved',
            'body'  => "Your schedule change for {$date} has been approved.",
            'url'   => '/staff/schedule',
        ];
    }
}
