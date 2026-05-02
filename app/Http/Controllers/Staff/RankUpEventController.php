<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\RankUpEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RankUpEventController extends Controller
{
    public function seen(RankUpEvent $rankUpEvent): JsonResponse
    {
        $this->authorizeEvent($rankUpEvent);

        if (! $rankUpEvent->seen_at) {
            $rankUpEvent->update(['seen_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function shared(RankUpEvent $rankUpEvent): JsonResponse
    {
        $this->authorizeEvent($rankUpEvent);

        $rankUpEvent->update([
            'shared_at' => $rankUpEvent->shared_at ?? now(),
            'seen_at' => $rankUpEvent->seen_at ?? now(),
        ]);

        return response()->json(['success' => true]);
    }

    private function authorizeEvent(RankUpEvent $rankUpEvent): void
    {
        abort_if($rankUpEvent->user_id !== Auth::id(), 403);
    }
}
