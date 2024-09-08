<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use App\Services\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ForgotPasswordController extends Controller
{
    public function requestReset(Request $request)
    {
        $request->validate([
            'value' => ['required'],
        ]);

        $user = User::query()
            ->where('email', $request->value)
            ->orWhere('phone', $request->value)
            ->first();

        if (! $user) {
            return ApiResponse::failed('The provided user does not exist');
        }

        $otp = str_pad(123456, 6, '0', STR_PAD_LEFT);

        Otp::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['code' => $otp, 'is_used' => false, 'expires_at' => Carbon::now()->addMinutes(5)]);

//        if ($user->email == $request->value) {
//            $user->notify(new ForgotPasswordNotification());
//        } else {
//            //send sms
//        }

        return ApiResponse::success('Verification code has been sent successfully');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'value' => ['required'],
            'otp' => ['required'],
        ]);

        $user = User::query()
            ->where('email', $request->value)
            ->orWhere('phone', $request->value)
            ->first();

        if (! $user) {
            return ApiResponse::failed('The provided user does not exist');
        }

        $otp = $request->otp;

        $checkOtp = $user->otp;
        if (!$checkOtp || $checkOtp->is_used || $checkOtp->expires_at < now() || $checkOtp->code != $otp) {
            return ApiResponse::failed("The provided otp is not valid.");
        }

        $checkOtp->update(['is_used' => true]);

        return ApiResponse::success('Code has been verified successfully');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'value' => ['required'],
            'new_password' => ['required', 'confirmed'],
        ]);

        $user = User::query()
            ->where('email', $request->value)
            ->orWhere('phone', $request->value)
            ->first();

        if (! $user) {
            return ApiResponse::failed('The provided user does not exist');
        }

        $data = $request->all();
        $user->update(['password' => $data['new_password']]);

        return ApiResponse::success('Password reset successfully');
    }
}
