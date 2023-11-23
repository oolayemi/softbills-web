<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\GeneralHelper;
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
    public function providers(VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchElectricityProviders();

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed("An error occurred, please try again");
        }
        return ApiResponse::success("Provider packages retrieved successfully", $response['content']);
    }

    public function validateMeterNumber(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $validated = $request->validate([
            'billers_code' => 'required|string',
            'service_id' => 'required|string',
            'type' => 'required|string',
        ]);

        $data = [
            'billersCode' => $validated['billers_code'],
            'serviceID' => $validated['service_id'],
            'type' => $validated['type']
        ];

//        $response = $vtPass->validateMeterNumber($data);

        #TODO: TO delete later
        $response = [
            "code" => "000",
            "content" => [
                "Customer_Name" => "TESTMETER1",
                "Meter_Number" => "1111111111111",
                "Business_Unit" => "",
                "Address" => "ABULE - EGBA BU ABULE",
                "Customer_Arrears" => ""
            ]
        ];

        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            Log::info("validate meter number response", $response);
            return ApiResponse::failed('An error occurred with fetching meter number validation details');
        }

        return ApiResponse::success("Meter Number details validated successfully", $response['content']);
    }

    public function purchase(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|string',
            'billers_code' => 'required|string',
            'variation_code' => ['required', 'string', Rule::in(['prepaid', 'postpaid'])],
            'amount' => 'required|integer',
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
            'billersCode' => $request->billers_code,
            'variation_code' => $request->variation_code,
            'amount' => $request->amount,
            'phone' => $user->phone ?? "09061626364"
        ];

        Log::info("payload for purchase electricity", $data);

//        $response = $vtPass->merchantPayment($data);
        $response = [
            "code" => "000",
            "content" => [
                "transactions" => [
                    "amount" => $request->amount,
                    "convinience_fee" => 0,
                    "status" => "delivered",
                    "name" => null,
                    "phone" => "07061933309",
                    "email" => "sandbox@vtpass.com",
                    "type" => "Electricity Bill",
                    "created_at" => "2019-08-17 02:27:26",
                    "discount" => null,
                    "giftcard_id" => null,
                    "total_amount" => 992,
                    "commission" => 8,
                    "channel" => "api",
                    "platform" => "api",
                    "service_verification" => null,
                    "quantity" => 1,
                    "unit_price" => 1000,
                    "unique_element" => "1010101010101",
                    "product_name" => "Eko Electric Payment - EKEDC"
                ]
            ],
            "response_description" => "TRANSACTION SUCCESSFUL",
            "requestId" => "hg3hgh3gdiud4w2wb33",
            "amount" => "1000.00",
            "transaction_date" => [
                "date" => "2019-08-17 02:27:27.000000",
                "timezone_type" => 3,
                "timezone" => "Africa/Lagos"
            ],
            "purchased_code" => "Token : 42167939781206619049 Bonus Token : 62881559799402440206",
            "mainToken" => "42167939781206619049",
            "mainTokenDescription" => "Normal Sale",
            "mainTokenUnits" => 16666.666,
            "mainTokenTax" => 442.11,
            "mainsTokenAmount" => 3157.89,
            "bonusToken" => "62881559799402440206",
            "bonusTokenDescription" => "FBE Token",
            "bonusTokenUnits" => 50,
            "bonusTokenTax" => null,
            "bonusTokenAmount" => null,
            "tariffIndex" => "52",
            "debtDescription" => "1122"
        ];

        $amount = $response['content']['transactions']['amount'];
        $productName = $response['content']['transactions']['product_name'];

        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            Log::info("purchase meter number response", $response);
            return ApiResponse::failed('An error occurred with fetching meter number validation details');
        }

        $user->walletTransactions()->create([
            'wallet_id' => $wallet->id,
            'reference' => $response['requestId'],
            'amount' => $amount,
            'prev_balance' => $wallet->balance,
            'new_balance' => $wallet->balance - $amount,
            'service_type' => ServiceType::ELECTRICITY->value,
            'transaction_type' => TransactionTypeEnum::debit->name,
            'status' => TransactionStatusEnum::SUCCESSFUL->name,
            'narration' => 'You purchased electricity from ' . $productName .' for ₦'.$amount,
        ]);

        $wallet->balance -= $amount;
        $wallet->save();

        return ApiResponse::success("Electricity purchase successfully");
    }
}
