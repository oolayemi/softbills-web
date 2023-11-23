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
use App\Services\ThirdPartyAPIs\VtPassApis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CableTvController extends Controller
{
    public function providers(VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchTvProviders();

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed("An error occurred, please try again");
        }

        \Log::info("CableTV providers", $response['content']);

        return ApiResponse::success("Provider packages retrieved successfully", $response['content']);
    }

    public function fetchPackages($type, VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchTvBillersForProvider($type);

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed('An error occurred with fetching provider packages');
        }

        return ApiResponse::success($type.' packages retrieved successfully', $response['content']['varations']);
    }

    public function validateSmartCard(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $request->validate([
            'billers_code' => 'required|string',
            'service_id' => 'required|string',
        ]);

//        $data = [
//            'billersCode' => $request->billers_code,
//            'serviceID' => $request->service_id
//        ];

//        $response = $vtPass->validateSmartCard($data);

        $response = [
            "code" => "000",
            "content" => [
                "Customer_Name" => "Mr  DsTEST",
                "Status" => "Open",
                "DUE_DATE" => "2019-07-23T00:00:00",
                "Customer_Number" => 48209000,
                "Customer_Type" => "DSTV",
                "Current_Bouquet" => "DStv Premium-Asia N17630 + DStv French only N6050 + DStv Premium-French N20780",
                "Current_Bouquet_Code" => "dstv10, dstv5, dstv9",
                "Renewal_Amount" => 2500
            ]
        ];


        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            \Log::info("whats wrong", $response);
            return ApiResponse::failed('An error occurred with fetching validating cable details');
        }

        return ApiResponse::success("SmartCard details validated successfully", $response['content']);
    }

    public function purchase(Request $request, VtPassApis $vtPass)
    {
        $request->validate([
            'service_id' => 'required',
            'billers_code' => 'required',
            'variation_code' => 'required',
            'amount' => 'required',
        ]);

        $requestId = now()->format('YmdHi') . \Str::random(10);
        $user = $request->user();

        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)){
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $data = [
            'request_id' => $requestId,
            'serviceID' => $request->service_id,
            'billersCode' => $request->billers_code,
            'amount' => $request->amount,
            'phone' => $user->phone ?? '09061626364',
            'subscription_type' => 'change'
        ];

//        $response = $vtPass->purchaseCableTv($data);
        $response = [
            "code" => "000",
            "content" => [
                "transactions" => [
                    "status" => "initiated",
                    "channel" => "api",
                    "transactionId" => "1563857332996",
                    "method" => "api",
                    "platform" => "api",
                    "is_api" => 1,
                    "discount" => null,
                    "customer_id" => 100649,
                    "email" => "sandbox@vtpass.com",
                    "phone" => "07061933309",
                    "type" => "TV Subscription",
                    "convinience_fee" => "0.00",
                    "commission" => 0.75,
                    "amount" => $request->amount,
                    "total_amount" => 49.25,
                    "quantity" => 1,
                    "unit_price" => "50",
                    "updated_at" => "2019-07-23 05:48:52",
                    "created_at" => "2019-07-23 05:48:52",
                    "id" => 7349787
                ]
            ],
            "response_description" => "TRANSACTION SUCCESSFUL",
            "requestId" => "SAND000001112A9320223291",
            "amount" => "50.00",
            "transaction_date" => [
                "date" => "2019-07-23 05:48:52.000000",
                "timezone_type" => 3,
                "timezone" => "Africa/Lagos"
            ],
            "purchased_code" => ""
        ];

        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            \Log::info("whats wrong - purchase", $response);
            return ApiResponse::failed('An error occurred with fetching validating cable details');
        }

        $amount = $response['content']['transactions']['amount'];

        $user->walletTransactions()->create([
            'wallet_id' => $wallet->id,
            'reference' => $response['requestId'],
            'amount' => $amount,
            'prev_balance' => $wallet->balance,
            'new_balance' => $wallet->balance - $amount,
            'service_type' => ServiceType::CABLE_TV->value,
            'transaction_type' => TransactionTypeEnum::debit->name,
            'status' => TransactionStatusEnum::SUCCESSFUL->name,
            'narration' => 'You purchased TV subscription from ' . $request->service_id .' for ₦'.$amount,
        ]);

        $wallet->balance -= $amount;
        $wallet->save();

        return ApiResponse::success("TV subscription purchased successfully");
    }
}
