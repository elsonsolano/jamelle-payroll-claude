<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'        => 'required|string',
            'keys.p256dh'     => 'required|string',
            'keys.auth'       => 'required|string',
        ]);

        $hash = hash('sha256', $request->input('endpoint'));

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id'       => Auth::id(),
                'endpoint'      => $request->input('endpoint'),
                'p256dh_key'    => $request->input('keys.p256dh'),
                'auth_token'    => $request->input('keys.auth'),
            ]
        );

        return response()->json(['status' => 'ok']);
    }
}
