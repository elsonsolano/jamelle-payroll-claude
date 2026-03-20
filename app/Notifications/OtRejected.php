<?php

namespace App\Notifications;

use App\Models\Dtr;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OtRejected extends Notification
{
    use Queueable;

    public function __construct(public Dtr $dtr) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dtr_id'  => $this->dtr->id,
            'date'    => $this->dtr->date->format('Y-m-d'),
            'reason'  => $this->dtr->ot_rejection_reason,
            'message' => "Your overtime request for {$this->dtr->date->format('M d, Y')} has been rejected.",
            'type'    => 'ot_rejected',
        ];
    }
}
