<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    public function show(Request $request, User $user, string $filename): StreamedResponse
    {
        $viewer = $request->user();

        abort_unless(
            $viewer && (
                $viewer->isAdmin()
                || $viewer->is($user)
                || ($viewer->isStaff() && $user->isStaff())
            ),
            403
        );

        $path = $user->profile_photo_path;

        abort_unless(
            $path
            && basename($path) === $filename
            && dirname($path) === "profile-photos/{$user->id}",
            404
        );

        $disk = config('filesystems.profile_photos_disk', 'public');
        $storage = Storage::disk($disk);

        abort_unless($storage->exists($path), 404);

        return $storage->response($path, $filename, [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
