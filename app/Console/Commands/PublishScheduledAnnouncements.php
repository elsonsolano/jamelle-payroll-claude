<?php

namespace App\Console\Commands;

use App\Jobs\PublishAnnouncementJob;
use App\Models\Announcement;
use Illuminate\Console\Command;

class PublishScheduledAnnouncements extends Command
{
    protected $signature = 'announcements:publish-scheduled';

    protected $description = 'Publish due scheduled announcements and send their notifications';

    public function handle(): int
    {
        $announcements = Announcement::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        foreach ($announcements as $announcement) {
            PublishAnnouncementJob::dispatchSync($announcement->id);
        }

        $this->info('Processed '.$announcements->count().' scheduled announcement(s).');

        return self::SUCCESS;
    }
}
