<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PasswordController
{
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $user->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();
        $user->invalidateOtherSessions($sessionId);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
