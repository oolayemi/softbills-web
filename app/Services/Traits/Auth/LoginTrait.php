<?php

namespace App\Services\Traits\Auth;

use App\Models\User;
use App\Services\Actions\OtpAction;
use App\Services\Enums\ProviderEnum;
use App\Services\Enums\UserRoleEnum;
use App\Services\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

trait LoginTrait
{
    public function login(array $data, UserRoleEnum $role = UserRoleEnum::customer): JsonResponse
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->first();


        if (!$user || !Hash::check($data['password'], $user->password) || !in_array($role->name, $user->getRoleNames()->toArray())) {
            return ApiResponse::failed('The provided credentials are incorrect.');
        }


        $user->update(['fcm_token' => $data['fcm_token'] ?? null]);
        $token = $user->createToken($data['email'])->plainTextToken;

        $tokenData = ['token' => $token];

        if (!$user->hasVerifiedEmail()) {
//            $otpAction = resolve(OtpAction::class);

//            if ($otpAction->generateOtp($user->id)) {
//                return ApiResponse::failed('Verify your email to continue', 403, $tokenData);
//            }
        }

        return ApiResponse::success('User logged in successfully', $tokenData);
    }

    public function logout(): JsonResponse
    {
        $user = \request()->user();

        $user->update(['fcm_token' => null]);
        $user->currentAccessToken()->delete();

        return ApiResponse::success('User logged out successfully');
    }
}
