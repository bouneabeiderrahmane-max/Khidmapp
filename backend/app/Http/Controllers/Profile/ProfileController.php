<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user->fresh());
    }

    /**
     * Désactivation de compte (soft delete) — conforme à la section 7.2.1
     * du cahier des charges. Le token en cours est invalidé.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        Auth::guard('api')->logout();
        $user->delete();

        return response()->json(['message' => 'ok']);
    }
}
