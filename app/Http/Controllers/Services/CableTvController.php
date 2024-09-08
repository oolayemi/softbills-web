<?php

namespace App\Http\Controllers\Services;

use App\Enums\ApiResponseEnum;
use App\Http\Controllers\Controller;
use App\Services\Constants\Providers;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\ApiResponse;
use App\Services\Helpers\GeneralHelper;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use App\Services\ThirdPartyAPIs\VtPassApis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CableTvController extends Controller
{
    public function providers(SageCloudServices $sageCloud): JsonResponse
    {
        $response = $sageCloud->fetchCableTvProviders();

        if (! $response['success']) {
            return ApiResponse::failed("An error occurred with fetching provider");
        }

        return ApiResponse::success("Cable-Tv providers retrieved successfully", $response['billers']);
    }

    public function fetchPackages($type, SageCloudServices $sageCloud): JsonResponse
    {
        $response = $sageCloud->fetchCableTvBillersForProvider($type);

        if (! $response['success']) {
            return ApiResponse::failed("An error occurred with fetching provider packages");
        }

        return ApiResponse::success($type.' packages retrieved successfully', $response['plans']);
    }

    public function validateSmartCard(Request $request, SageCloudServices $sageCloud): JsonResponse
    {
        $validated = $request->validate([
            'smartCardNo' => 'required|string',
            'biller_id' => 'required|string',
        ]);

        $response = $sageCloud->validateSmartcard($validated);

        if (! $response['success']) {
            return ApiResponse::failed($response['message'] ?? 'Could not validate smart card details');
        }

        return ApiResponse::success("SmartCard details validated successfully", $response['customer']);
    }

    public function purchase(Request $request, SageCloudServices $sageCloud)
    {
        $user = $request->user();

        $data = $request->validate([
            'smartCardNo' => 'required',
            'code' => 'required',
            'type' => 'required',
            'amount' => 'required|decimal:0,2',
            'image_url' => ['nullable', 'string'],
        ]);

        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)){
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $payload = [
            'reference' => GeneralHelper::generateReference(ServiceType::CABLE_TV->value),
            'smartCardNo' => $request->smartCardNo,
            'code' => $request->code,
            'type' => $request->type,
        ];

        $response = $sageCloud->purchaseCableTv($payload);

        $amount = $request->amount;

        if (isset($response['status']) && $response['status'] != 'failed') {

            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'reference' => $response['requestId'],
                'amount' => $amount,
                'prev_balance' => $wallet->balance,
                'new_balance' => $wallet->balance - $amount,
                'service_type' => ServiceType::CABLE_TV->value,
                'transaction_type' => TransactionTypeEnum::debit->name,
                'status' => TransactionStatusEnum::SUCCESSFUL->name,
                'narration' => 'You purchased TV subscription from ' . $request->service_id . ' for ₦' . $amount,
                'image_url' => $request->image_url
            ]);

            $wallet->balance -= $amount;
            $wallet->save();

            return ApiResponse::success("Cable TV purchase request submitted.");
        }

        return ApiResponse::failed("Your CableTV purchase request failed.");
    }
}
