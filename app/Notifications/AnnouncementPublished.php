<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification
{
    use Queueable;

    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'subject'         => $this->announcement->subject,
            'type'            => 'announcement',
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->announcement->subject,
            'body'  => 'Tap to read the announcement.',
            'url'   => '/staff/announcements/' . $this->announcement->id,
        ];
    }
}
