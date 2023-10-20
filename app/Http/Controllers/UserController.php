<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Rules\Phone;
use App\Services\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function userProfile(): JsonResponse
    {
        $user = \request()->user();
        return ApiResponse::success("User profile fetched successfully", $user->toArray());
    }

    public function editProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'min:3', 'max:20'],
            'lastname' => ['required', 'string', 'min:3', 'max:20'],
            'phone' => ['required', new Phone],
            'image' => ['nullable', 'image', 'max:10000'],
        ]);

        $user = \request()->user();
        $file = $request->file('image');

        if ($request->file('image')) {
            if ($user->image_url) {
                if (Storage::disk('public')->exists($user->image_url)) {
                    Storage::disk('public')->delete($user->image_url);
                }
            }
            $image = $file->store('uploads/users', 'public');
            $validated['image_url'] = $image;
            unset($validated['image']);
        }

        $user->update($validated);

        return ApiResponse::success("User profile updated successfully");
    }

    public function fetchWalletDetails(): JsonResponse
    {
        $user = \request()->user();
        $walletDetails = Wallet::first();
        \Log::info("HErrrrr", [$walletDetails]);

        return ApiResponse::success('Wallets fetched successfully', $walletDetails->toArray());
    }

    public function userWalletTransactions(): JsonResponse
    {
        $user = \request()->user();
        $walletTransactions = $user->walletTransactions()
            ->orderByDesc('id')
            ->paginate(50)
            ->toArray();

        return ApiResponse::success('Wallet transactions fetched successfully', $walletTransactions);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required',
                'min:8',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x]).*$/',
                'confirmed'],
        ], $messages = [
            'regex' => 'The :attribute must contain at least an uppercase, lowercase and a number',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return ApiResponse::failed("The provided password is incorrect");
        }
        $user->update(['password' => Hash::make($validated['new_password'])]);

        return ApiResponse::success( "Password updated successfully");
    }

    public function changePin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_pin' => 'required|string',
            'new_pin' => 'required|string|confirmed|digits:4',
        ]);

        $user = $request->user();

        if (sha1($validated['current_pin']) != $user->transaction_pin) {
            return ApiResponse::failed("The provided old transaction PIN is incorrect");
        }
        $user->update(['transaction_pin' => sha1($validated['new_pin'])]);

        return ApiResponse::success( "Transaction PIN updated successfully");
    }

//    public function tier2Upgrade(Request $request, SageCloudServices $sageCloud): JsonResponse
//    {
//        $user = $request->user();
//
//        $validated = $request->validate([
//            'bvn' => ['required', 'digits:11', 'numeric'],
//            'phone' => ['required', new Phone],
//        ]);
//
//        $wallet = $user->nairaWallet;
//
//        if (! checkWalletBalance($wallet, 25)) {
//            return response()->json([
//                'status' => ApiResponseEnum::failed(),
//                'message' => 'You don\'t have sufficient balance to continue.',
//            ]);
//        }
//
//        $response = $sageCloud->verifyBvn($validated);
//        Log::info('response from verify bvn', [$response]);
//
//        $user->walletTransactions()->create([
//            'naira_wallet_id' => $wallet->id,
//            'reference' => tx_ref(),
//            'amount' => 25,
//            'charges' => 0,
//            'wallet_source' => WalletSourceEnum::naira(),
//            'prev_balance' => $wallet->balance,
//            'new_balance' => $wallet->balance - 25,
//            'type' => 'BVN Verification',
//            'transaction_type' => TransactionTypeEnum::debit(),
//            'status' => 'success',
//            'narration' => 'BVN verification payment',
//        ]);
//
//        if ($response['status'] != 'failed') {
//            if ($response['data']['verification_status'] == 'VERIFIED') {
//                $user->update(['tier' => 2]);
//            }
//        }
//
//        return response()->json([
//            'status' => ApiResponseEnum::success(),
//            'message' => 'Account upgraded successfully',
//        ]);
//    }
}
