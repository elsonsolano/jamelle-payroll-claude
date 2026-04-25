<?php

namespace App\Http\Controllers;

use App\Jobs\PublishAnnouncementJob;
use App\Models\Announcement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    private function gate(): void
    {
        abort_unless(Auth::user()->hasPermission('announcements'), 403);
    }

    public function index(): View
    {
        $this->gate();

        $announcements = Announcement::with('author')
            ->withCount('reads')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $this->gate();

        return view('announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate();

        $validated = $request->validate([
            'subject'      => 'required|string|max:255',
            'body'         => 'required|string',
            'action'       => 'required|in:draft,publish,schedule',
            'scheduled_at' => 'required_if:action,schedule|nullable|date|after:now',
        ]);

        $status     = match ($validated['action']) {
            'publish'  => 'published',
            'schedule' => 'scheduled',
            default    => 'draft',
        };

        $announcement = Announcement::create([
            'created_by'   => Auth::id(),
            'subject'      => $validated['subject'],
            'body'         => $validated['body'],
            'status'       => $status,
            'scheduled_at' => $status === 'scheduled' ? $validated['scheduled_at'] : null,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        if ($status === 'published') {
            PublishAnnouncementJob::dispatch($announcement->id);
        } elseif ($status === 'scheduled') {
            PublishAnnouncementJob::dispatch($announcement->id)
                ->delay(Carbon::parse($validated['scheduled_at']));
        }

        $message = match ($status) {
            'published' => 'Announcement published and notifications sent.',
            'scheduled' => 'Announcement scheduled.',
            default     => 'Announcement saved as draft.',
        };

        return redirect()->route('announcements.index')->with('success', $message);
    }

    public function show(Announcement $announcement): View
    {
        $this->gate();

        $reads = $announcement->reads()
            ->with('user')
            ->orderByDesc('read_at')
            ->get();

        return view('announcements.show', compact('announcement', 'reads'));
    }

    public function edit(Announcement $announcement): View
    {
        $this->gate();

        return view('announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->gate();

        $isPublished = $announcement->status === 'published';

        $rules = [
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ];

        if (! $isPublished) {
            $rules['action']       = 'required|in:draft,publish,schedule';
            $rules['scheduled_at'] = 'required_if:action,schedule|nullable|date|after:now';
        }

        $validated = $request->validate($rules);

        $announcement->subject = $validated['subject'];
        $announcement->body    = $validated['body'];

        if (! $isPublished) {
            $action = $validated['action'];

            if ($action === 'publish') {
                $announcement->status       = 'published';
                $announcement->published_at = now();
                $announcement->scheduled_at = null;
            } elseif ($action === 'schedule') {
                $announcement->status       = 'scheduled';
                $announcement->scheduled_at = $validated['scheduled_at'];
                $announcement->published_at = null;
            } else {
                $announcement->status       = 'draft';
                $announcement->scheduled_at = null;
            }
        }

        $announcement->save();

        if (! $isPublished) {
            if ($announcement->status === 'published') {
                PublishAnnouncementJob::dispatch($announcement->id);
            } elseif ($announcement->status === 'scheduled') {
                PublishAnnouncementJob::dispatch($announcement->id)
                    ->delay(Carbon::parse($announcement->scheduled_at));
            }
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->gate();

        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }

    public function resendPush(Announcement $announcement): RedirectResponse
    {
        $this->gate();

        abort_unless($announcement->status === 'published', 404);

        PublishAnnouncementJob::dispatch($announcement->id, true);

        return redirect()
            ->route('announcements.show', $announcement)
            ->with('success', 'Push notification resent.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $this->gate();

        $request->validate(['image' => 'required|image|max:4096']);

        $path = $request->file('image')->store('announcement-images', 'public');

        return response()->json(['url' => Storage::url($path)]);
    }
}
