<?php

namespace App\Notifications;

use App\Models\Dtr;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OtRejected extends Notification
{
    use Queueable;

    public function __construct(public Dtr $dtr, public string $approverName) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dtr_id'        => $this->dtr->id,
            'date'          => $this->dtr->date->format('Y-m-d'),
            'reason'        => $this->dtr->ot_rejection_reason,
            'message'       => "Your overtime request for {$this->dtr->date->format('M d, Y')} has been rejected.",
            'approver_name' => $this->approverName,
            'type'          => 'ot_rejected',
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'OT Rejected',
            'body'  => "Your OT for {$this->dtr->date->format('M d, Y')} was rejected.",
            'url'   => '/staff/dtr',
        ];
    }
}
