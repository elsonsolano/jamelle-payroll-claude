<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $announcementId) {}

    public function handle(): void
    {
        $announcement = Announcement::find($this->announcementId);

        if (! $announcement) {
            return;
        }

        // Promote from scheduled → published (no-op if already published)
        if ($announcement->status === 'scheduled') {
            $announcement->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);
        }

        if ($announcement->status !== 'published') {
            return;
        }

        User::where('role', 'staff')
            ->with('pushSubscriptions')
            ->each(fn (User $user) => $user->notify(new AnnouncementPublished($announcement)));
    }
}
