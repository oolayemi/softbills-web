<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\GeneralHelper;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use App\Services\ThirdPartyAPIs\VtPassApis;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Helpers\ApiResponse;
use App\Rules\Phone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ElectricityController extends Controller
{
    public function providers(SageCloudServices $sageCloud): JsonResponse
    {
        $response = $sageCloud->fetchElectricityBillers();

        if (!$response['success']) {
            return ApiResponse::failed("An error occurred while fetching electricity billers");
        }

        sort($response['billers']);

        return ApiResponse::success("Electricity billers fetched successfully", $response['billers']);
    }

    public function validateMeterNumber(Request $request, SageCloudServices $sageCloud): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required',
            'account_number' => 'required|string',
        ]);

        $response = $sageCloud->validateMeter($validated);

        if (!$response['success']) {
            return ApiResponse::failed("An error occurred while validating meter number");
        }

        return ApiResponse::success("Meter number fetched successfully", $response['customer']);
    }

    public function purchase(Request $request, SageCloudServices $sageCloud): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required',
            'account_number' => 'required|string',
            'amount' => 'required|decimal:0,2',
        ]);

        $validatedMeter = $sageCloud->validateMeter($data);

        if (!$validatedMeter['success']) {
            return ApiResponse::failed("An error occurred while validating meter");
        }

        $user = $request->user();
        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)) {
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $payload = [
            'reference' => GeneralHelper::generateReference(ServiceType::ELECTRICITY->value),
            'type' => $data['type'],
            'disco' => $validatedMeter['customer']['disco'] ?? $validatedMeter['customer']['billerName'],
            'account_number' => $data['account_number'],
            'phone' => $validatedMeter['customer']['phoneNumber'] ?? $user->phone_number ?? '09061628409',
            'amount' => $data['amount'],
        ];

        $response = $sageCloud->purchasePower($payload);
        Log::info("response from purchase power", $response);
        $amount = $data['amount'];

        if (isset($response['status']) && $response['status'] != 'failed') {

            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'reference' => $payload['reference'],
                'amount' => $amount,
                'prev_balance' => $wallet->balance,
                'new_balance' => $wallet->balance - $amount,
                'service_type' => ServiceType::ELECTRICITY->value,
                'transaction_type' => TransactionTypeEnum::debit->name,
                'status' => $response['status'] == 'pending' ? TransactionStatusEnum::PENDING->name : TransactionStatusEnum::SUCCESSFUL->name,
                'narration' => 'You purchased electricity from ' . $payload['disco'] . ' for ₦' . $payload['amount'],
            ]);

            $wallet->balance -= $amount;
            $wallet->save();

            return ApiResponse::success("Electricity purchase request submitted");
        }
        return ApiResponse::failed("Electricity purchase request failed");
    }
}
