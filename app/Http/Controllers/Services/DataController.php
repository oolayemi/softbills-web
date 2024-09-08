<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Rules\Phone;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\ApiResponse;
use App\Services\Helpers\GeneralHelper;
use App\Services\ThirdPartyAPIs\SageCloudServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DataController extends Controller
{
    public function providers(SageCloudServices $sageCloud): JsonResponse
    {
        return ApiResponse::success(
            "Data providers packages retrieved successfully",
            $sageCloud->fetchDataProviders()['billers'] ?? []
        );
    }

    public function dataBundle($provider, SageCloudServices $sageCloud)
    {
        return ApiResponse::success(
            'Data bundles retrieved successfully',
            $sageCloud->fetchDataBundles(['provider' => strtoupper($provider)])['data']);
    }

    public function purchase(Request $request, SageCloudServices $sageCloud): JsonResponse
    {
        $request->validate([
            'mobile' => ['required', 'size:11', new Phone],
            'operator' => 'required',
            'amount' => 'required',
            'bundle' => ['required'],
            'image_url' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)){
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $payload = [
            'reference' => GeneralHelper::generateReference(ServiceType::DATA->value),
            'type' => strtoupper($request->operator.'data'),
            'network' => strtoupper($request->operator),
            'phone' => $request->mobile,
            'provider' => strtoupper($request->operator),
            'code' => $request->bundle,
        ];

        $data = $request->all();

        $response = $sageCloud->purchaseData($payload);
        $amount = $data['amount'];

        if (isset($response['status']) && $response['status'] != 'failed') {

            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'reference' => $payload['reference'],
                'amount' => $amount,
                'prev_balance' => $wallet->balance,
                'new_balance' => $wallet->balance - $amount,
                'service_type' => ServiceType::DATA->value,
                'transaction_type' => TransactionTypeEnum::debit->name,
                'status' => TransactionStatusEnum::SUCCESSFUL->name,
                'narration' => $payload['network'] . ' data purchased of ₦' . $request->amount . ' to ' . $payload['phone'],
                'image_url' => $request->image_url
            ]);

            $wallet->balance -= $amount;
            $wallet->save();

            return ApiResponse::success("Data bundle purchase request submitted");
        }

        return ApiResponse::failed('Data bundle purchase request failed.');
    }
}
