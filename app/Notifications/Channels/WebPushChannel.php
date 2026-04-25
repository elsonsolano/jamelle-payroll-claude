<?php

namespace App\Notifications\Channels;

use App\Notifications\AnnouncementPublished;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $subscriptions = $notifiable->pushSubscriptions ?? collect();
        if ($subscriptions->isEmpty()) {
            $this->logAnnouncementPush('info', 'Skipped — no push subscriptions', $notifiable, $notification);
            return;
        }

        $payload = json_encode($notification->toWebPush($notifiable));

        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys'     => ['p256dh' => $sub->p256dh_key, 'auth' => $sub->auth_token],
                ]),
                $payload
            );
        }

        $sent = 0;
        $failed = 0;

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
                continue;
            }

            $failed++;
            $this->logAnnouncementPush('warning', 'Push failed', $notifiable, $notification, [
                'reason' => $report->getReason(),
            ]);
        }

        $this->logAnnouncementPush('info', 'Push flush complete', $notifiable, $notification, [
            'subscriptions' => $subscriptions->count(),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    private function logAnnouncementPush(string $level, string $message, object $notifiable, Notification $notification, array $extra = []): void
    {
        if (! $notification instanceof AnnouncementPublished) {
            return;
        }

        $context = array_merge([
            'announcement_id' => $notification->announcement->id,
            'subject' => $notification->announcement->subject,
            'user_id' => $notifiable->id ?? null,
            'user_name' => $notifiable->name ?? null,
        ], $extra);

        Log::{$level}('[AnnouncementPush] ' . $message, $context);
    }
}
