<?php

namespace App\Notifications;

use App\Models\PayrollCutoff;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PayslipAvailable extends Notification
{
    use Queueable;

    public function __construct(public PayrollCutoff $cutoff) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'cutoff_id'   => $this->cutoff->id,
            'cutoff_name' => $this->cutoff->name,
            'message'     => "Your payslip for {$this->cutoff->name} is now available.",
            'type'        => 'payslip_available',
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'New Payslip Available',
            'body'  => "Your payslip for {$this->cutoff->name} is ready to view.",
            'url'   => '/staff/payslips',
        ];
    }
}
