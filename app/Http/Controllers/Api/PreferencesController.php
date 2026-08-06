<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdatePreferencesRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferencesController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'preferences' => $this->preferences($request->user()),
        ]);
    }

    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Preferences updated successfully.',
            'preferences' => $this->preferences($user->fresh()),
        ]);
    }

    /**
     * @return array{theme: string, notifications_enabled: bool}
     */
    private function preferences(User $user): array
    {
        return [
            'theme' => $user->theme,
            'notifications_enabled' => (bool) $user->notifications_enabled,
        ];
    }
}
