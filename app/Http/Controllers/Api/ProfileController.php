<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UploadAvatarRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController
{
    private const MANAGED_AVATAR_PATTERN = '/\Aavatars\/[A-Za-z0-9]{40}\.(?:jpe?g|png|webp)\z/';

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $previousAvatar = $user->avatar;
        $avatarPath = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $avatarPath]);

        if (is_string($previousAvatar) && preg_match(self::MANAGED_AVATAR_PATTERN, $previousAvatar) === 1) {
            Storage::disk('public')->delete($previousAvatar);
        }

        return response()->json([
            'message' => 'Avatar uploaded successfully.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            ...$user->toArray(),
            'avatar_url' => $user->avatar === null
                ? null
                : Storage::disk('public')->url($user->avatar),
        ];
    }
}
