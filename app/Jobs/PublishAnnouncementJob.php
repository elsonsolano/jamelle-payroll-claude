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
use Illuminate\Support\Facades\Log;

class PublishAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $announcementId,
        public bool $pushOnly = false,
    ) {}

    public function handle(): void
    {
        $announcement = Announcement::find($this->announcementId);

        if (! $announcement) {
            Log::warning('[AnnouncementPush] Skipped — announcement not found', [
                'announcement_id' => $this->announcementId,
            ]);
            return;
        }

        // Promote from scheduled → published (no-op if already published)
        if ($announcement->status === 'scheduled') {
            if (! $announcement->scheduled_at) {
                Log::warning('[AnnouncementPush] Skipped — scheduled announcement is missing scheduled_at', [
                    'announcement_id' => $announcement->id,
                    'subject' => $announcement->subject,
                ]);
                return;
            }

            if ($announcement->scheduled_at->isFuture()) {
                Log::info('[AnnouncementPush] Skipped — scheduled time has not arrived yet', [
                    'announcement_id' => $announcement->id,
                    'subject' => $announcement->subject,
                    'scheduled_at' => $announcement->scheduled_at->toDateTimeString(),
                    'now' => now()->toDateTimeString(),
                ]);
                return;
            }

            $announcement->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);
        }

        if ($announcement->status !== 'published') {
            Log::info('[AnnouncementPush] Skipped — announcement is not published', [
                'announcement_id' => $announcement->id,
                'status' => $announcement->status,
            ]);
            return;
        }

        $users = User::where('role', 'staff')
            ->with('pushSubscriptions')
            ->get();

        Log::info('[AnnouncementPush] Sending announcement notification', [
            'announcement_id' => $announcement->id,
            'subject' => $announcement->subject,
            'staff_users' => $users->count(),
            'staff_with_subscriptions' => $users->filter(fn (User $user) => $user->pushSubscriptions->isNotEmpty())->count(),
            'push_only' => $this->pushOnly,
        ]);

        $users->each(fn (User $user) => $user->notify(
            new AnnouncementPublished($announcement, storeInDatabase: ! $this->pushOnly)
        ));

        Log::info('[AnnouncementPush] Dispatch complete', [
            'announcement_id' => $announcement->id,
            'subject' => $announcement->subject,
            'staff_users' => $users->count(),
            'push_only' => $this->pushOnly,
        ]);
    }
}
