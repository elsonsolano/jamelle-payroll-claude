<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        $announcements = Announcement::published()
            ->orderByDesc('published_at')
            ->withExists(['reads as is_read' => fn ($q) => $q->where('user_id', $userId)])
            ->get();

        return view('staff.announcements.index', compact('announcements'));
    }

    public function show(Announcement $announcement): View
    {
        abort_if($announcement->status !== 'published', 404);

        AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => Auth::id()],
            ['read_at' => now()],
        );

        return view('staff.announcements.show', compact('announcement'));
    }
}
