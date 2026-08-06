<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadAvatarRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    private const MANAGED_AVATAR_PATTERN = '/\Aavatars\/[A-Za-z0-9]{40}\.(?:jpe?g|png|webp)\z/';

    public function __invoke(): View
    {
        return view('account.index', [
            'user' => Auth::user(),
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $previousAvatar = $user->avatar;
        $avatarPath = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $avatarPath]);

        if (is_string($previousAvatar) && preg_match(self::MANAGED_AVATAR_PATTERN, $previousAvatar) === 1) {
            Storage::disk('public')->delete($previousAvatar);
        }

        return redirect()->route('account.index')->with('profile_success', 'Avatar updated successfully.');
    }
}
