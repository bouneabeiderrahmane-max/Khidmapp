<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginEmailRequest;
use App\Http\Requests\Auth\RegisterEmailRequest;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $this->otp->generateAndSend($request->string('phone')->toString());

        return response()->json(['message' => __('khidmapp.otp_sent')]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        if (! $this->otp->verify($phone, $request->string('code')->toString())) {
            return response()->json(['message' => __('khidmapp.otp_invalid')], 422);
        }

        $user = User::query()->where('phone', $phone)->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $request->string('name')->toString() ?: 'Client',
                'phone' => $phone,
                'phone_verified_at' => now(),
                'locale' => app()->getLocale(),
            ]);
            $user->assignRole(Roles::CLIENT);
        } elseif ($user->phone_verified_at === null) {
            $user->update(['phone_verified_at' => now()]);
        }

        if ($user->isBlocked()) {
            return response()->json(['message' => __('khidmapp.account_blocked')], 403);
        }

        return $this->respondWithToken(Auth::guard('api')->login($user), $user);
    }

    public function registerEmail(RegisterEmailRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'locale' => $request->input('locale', app()->getLocale()),
        ]);
        $user->assignRole(Roles::CLIENT);

        return $this->respondWithToken(Auth::guard('api')->login($user), $user, 201);
    }

    public function loginEmail(LoginEmailRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['message' => __('khidmapp.login_failed')], 401);
        }

        $user = Auth::guard('api')->user();

        if ($user->isBlocked()) {
            Auth::guard('api')->logout();

            return response()->json(['message' => __('khidmapp.account_blocked')], 403);
        }

        return $this->respondWithToken($token, $user);
    }

    public function refresh(): JsonResponse
    {
        $token = Auth::guard('api')->refresh();

        return $this->respondWithToken($token, Auth::guard('api')->user());
    }

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'ok']);
    }

    private function respondWithToken(string $token, User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], $status);
    }
}
