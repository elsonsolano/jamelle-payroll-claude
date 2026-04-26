<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee->load('branch');

        return view('staff.profile', compact('employee'));
    }

    public function updateSignature(Request $request): RedirectResponse
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        Auth::user()->update(['signature' => $request->signature]);

        return back()->with('success', 'Signature updated successfully.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();
        $disk = config('filesystems.profile_photos_disk', 'public');

        try {
            if ($user->profile_photo_path) {
                Storage::disk($disk)->delete($user->profile_photo_path);
            }

            $file = $request->file('photo');
            $path = $file->storeAs(
                "profile-photos/{$user->id}",
                Str::uuid().'.'.$file->extension(),
                $disk
            );
        } catch (Throwable $exception) {
            Log::warning('Profile photo upload failed.', [
                'user_id' => $user->id,
                'disk' => $disk,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Profile photo upload failed. Please check the storage connection and try again.');
        }

        if (! is_string($path) || $path === '') {
            Log::warning('Profile photo upload returned an empty path.', [
                'user_id' => $user->id,
                'disk' => $disk,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Profile photo upload failed. Please check the storage connection and try again.');
        }

        $user->update(['profile_photo_path' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }

    public function destroyPhoto(): RedirectResponse
    {
        $user = Auth::user();
        $disk = config('filesystems.profile_photos_disk', 'public');

        if ($user->profile_photo_path) {
            Storage::disk($disk)->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
