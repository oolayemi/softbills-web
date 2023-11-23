<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Rules\Phone;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\ApiResponse;
use App\Services\Helpers\GeneralHelper;
use App\Services\ThirdPartyAPIs\VtPassApis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataController extends Controller
{
    public function providers(VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchDataProviders();

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed("An error occurred, please try again");
        }

        \Log::info("Data providers", $response['content']);

        return ApiResponse::success("Data providers packages retrieved successfully", $response['content']);
    }

    public function dataBundle(VtPassApis $vtPass, string $serviceId)
    {
        $response = $vtPass->fetchDataBundles($serviceId);

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed("An error occurred, please try again");
        }

        return ApiResponse::success($serviceId.' packages retrieved successfully', $response['content']['varations']);
    }

    public function purchase(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|string',
            'amount' => 'required|numeric|decimal:0,2',
            'variation_code' => 'required|string',
            'phone' => ['required', new Phone]
        ]);

        $requestId = now()->format('YmdHi') . Str::random(10);

        $user = $request->user();
        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)){
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $data = [
            'request_id' => $requestId,
            'serviceID' => $request->service_id,
            'amount' => $request->amount,
            'variation_code' => $request->variation_code,
            'billersCode' => $request->phone,
            'phone' => $request->phone,
        ];

        Log::info("payload for purchase data", $data);

//        $response = $vtPass->merchantPayment($data);
        $response = [
            "code" => "000",
            "content" => [
                "transactions" => [
                    "status" => "delivered",
                    "product_name" => "Airtel Data",
                    "unique_element" => "08011111111",
                    "unit_price" => 100,
                    "quantity" => 1,
                    "service_verification" => null,
                    "channel" => "api",
                    "commission" => 4,
                    "total_amount" => 96,
                    "discount" => null,
                    "type" => "Data Services",
                    "email" => "sandbox@vtpass.com",
                    "phone" => "07061933309",
                    "name" => null,
                    "convinience_fee" => 0,
                    "amount" => 100,
                    "platform" => "api",
                    "method" => "api",
                    "transactionId" => "1582290782154"
                ]
            ],
            "response_description" => "TRANSACTION SUCCESSFUL",
            "requestId" => "3476we129909djd",
            "amount" => $request->amount,
            "transaction_date" => [
                "date" => "2020-02-21 14:13:02.000000",
                "timezone_type" => 3,
                "timezone" => "Africa/Lagos"
            ],
            "purchased_code" => ""
        ];

        $amount = $response['amount'];
        $productName = $request->service_id;

        if (empty($response) || (isset($response['code']) && $response['code'] != "000")) {
            Log::info("purchase data response", $response);
            return ApiResponse::failed('An error occurred with purchasing data');
        }

        $user->walletTransactions()->create([
            'wallet_id' => $wallet->id,
            'reference' => $response['requestId'],
            'amount' => $amount,
            'prev_balance' => $wallet->balance,
            'new_balance' => $wallet->balance - $amount,
            'service_type' => ServiceType::DATA->value,
            'transaction_type' => TransactionTypeEnum::debit->name,
            'status' => TransactionStatusEnum::SUCCESSFUL->name,
            'narration' => 'You purchased data from ' . $productName . ' for ₦' . $amount,
        ]);

        $wallet->balance -= $amount;
        $wallet->save();

        return ApiResponse::success("Airtime purchase successfully");
    }
}
