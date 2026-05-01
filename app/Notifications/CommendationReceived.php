<?php

namespace App\Notifications;

use App\Models\Commendation;
use App\Notifications\Channels\WebPushChannel;
use App\Services\CommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CommendationReceived extends Notification
{
    use Queueable;

    public function __construct(public Commendation $commendation) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $labels = app(CommendationService::class)->labelsFor($this->commendation->trait_ids ?? []);
        $points = (int) $this->commendation->points;

        return [
            'commendation_id' => $this->commendation->id,
            'trait_ids' => $this->commendation->trait_ids,
            'trait_labels' => $labels,
            'points' => $points,
            'message' => 'Someone commended you for '.$this->formatLabels($labels).". +{$points} ".Str::plural('point', $points),
            'type' => 'commendation_received',
            'url' => '/staff/achievements',
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        $labels = app(CommendationService::class)->labelsFor($this->commendation->trait_ids ?? []);
        $points = (int) $this->commendation->points;

        return [
            'title' => 'New Commendation',
            'body' => 'Someone commended you for '.$this->formatLabels($labels).". +{$points} ".Str::plural('point', $points),
            'url' => '/staff/achievements',
        ];
    }

    private function formatLabels(array $labels): string
    {
        if (count($labels) <= 1) {
            return $labels[0] ?? 'your work';
        }

        if (count($labels) === 2) {
            return $labels[0].' and '.$labels[1];
        }

        $last = array_pop($labels);

        return implode(', ', $labels).', and '.$last;
    }
}
