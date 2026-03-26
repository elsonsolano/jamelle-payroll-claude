<?php

namespace App\Notifications;

use App\Models\Dtr;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OtApproved extends Notification
{
    use Queueable;

    public function __construct(public Dtr $dtr, public string $approverName) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dtr_id'        => $this->dtr->id,
            'date'          => $this->dtr->date->format('Y-m-d'),
            'message'       => "Your overtime request for {$this->dtr->date->format('M d, Y')} has been approved.",
            'approver_name' => $this->approverName,
            'type'          => 'ot_approved',
        ];
    }
}
