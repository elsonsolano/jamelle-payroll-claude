<?php

namespace App\Notifications;

use App\Models\Dtr;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OtSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Dtr $dtr) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $employee = $this->dtr->employee;
        return [
            'dtr_id'        => $this->dtr->id,
            'employee_name' => $employee->full_name,
            'date'          => $this->dtr->date->format('Y-m-d'),
            'ot_end_time'   => $this->dtr->ot_end_time,
            'message'       => "{$employee->full_name} submitted an overtime request for {$this->dtr->date->format('M d, Y')}.",
            'type'          => 'ot_submitted',
        ];
    }
}
