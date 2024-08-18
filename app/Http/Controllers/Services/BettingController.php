<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\ApiResponse;
use App\Services\Helpers\GeneralHelper;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BettingController extends Controller
{
    public function providers(SageCloudServices $sageCloud): JsonResponse
    {
        $response = $sageCloud->fetchBettingBillers();

        if (! $response['success']) {
            return ApiResponse::failed("An error occurred while fetching providers");
        }

        return ApiResponse::success("Betting providers retrieved successfully", $response['data']);
    }

    public function validateBetting(Request $request, SageCloudServices $sageCloudApiService): JsonResponse
    {
        $validated = $request->validate([
            'customerId' => 'required',
            'type' => 'required|string',
        ]);

        $validatedData = $sageCloudApiService->validateBetting($validated);

        Log::info('Betting providers', $validatedData);

        if (! $validatedData['success']) {
            return ApiResponse::failed("An error occurred while validating betting details");
        }

        return ApiResponse::success("Betting providers validated successfully", $validatedData['data']);
    }

    public function purchase(Request $request, SageCloudServices $sageCloud): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'customerId' => ['required'],
            'type' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
        ]);

        $data = $request->all();

        $payload = [
            'reference' => GeneralHelper::generateReference(ServiceType::BETTING->value),
            'type' => $data['type'],
            'customerId' => $data['customerId'],
            'name' => $data['name'],
            'amount' => $data['amount'],
        ];

        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)){
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $response = $sageCloud->fundBetting($payload);
        Log::info('funding betting response', [$response]);

        if (isset($response['status']) && $response['status'] != 'failed') {
            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'reference' => $response['requestId'],
                'amount' => $data['amount'],
                'prev_balance' => $wallet->balance,
                'new_balance' => $wallet->balance - $data['amount'],
                'service_type' => ServiceType::BETTING->value,
                'transaction_type' => TransactionTypeEnum::debit->name,
                'status' => TransactionStatusEnum::SUCCESSFUL->name,
                'narration' => $payload['type'].' betting funding of ₦'.$payload['amount'],
            ]);

            $wallet->balance -= $data['amount'];
            $wallet->save();

            return ApiResponse::success("Betting request submitted successfully");
        }

        return ApiResponse::failed('Your betting request failed');
    }
}
