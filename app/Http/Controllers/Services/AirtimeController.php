<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\Enums\ServiceType;
use App\Services\Enums\TransactionStatusEnum;
use App\Services\Enums\TransactionTypeEnum;
use App\Services\Helpers\GeneralHelper;
use App\Services\ThirdPartyAPIs\VtPassApis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\Constants\Providers;
use App\Services\Helpers\ApiResponse;
use App\Services\ThirdPartyAPIs\MegaSubApis;
use Illuminate\Http\JsonResponse;
use App\Rules\AirtimeAmount;
use App\Rules\Phone;

class AirtimeController extends Controller
{

    public function providers(VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchAirtimeProviders();

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed("An error occurred, please try again");
        }

        \Log::info("Airtime providers", $response['content']);

        return ApiResponse::success("Airtime providers packages retrieved successfully", $response['content']);
    }

    public function purchase(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|string',
            'amount' => 'required|numeric|decimal:0,2',
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
            'phone' => $request->phone ?? "09061626364"
        ];

        Log::info("payload for purchase airtime", $data);

//        $response = $vtPass->merchantPayment($data);
        $response = [
            "code" => "000",
            "response_description" => "TRANSACTION SUCCESSFUL",
            "requestId" => "SAND0192837465738253A1HSD",
            "transactionId" => "1563873435424",
            "amount" => $request->amount,
            "transaction_date" => [
                "date" => "2019-07-23 10 => 17 => 16.000000",
                "timezone_type" => 3,
                "timezone" => "Africa/Lagos"
            ],
            "purchased_code" => ""
        ];

        $amount = $response['amount'];
        $productName = $request->service_id;

        if (empty($response) || (isset($response['code']) && $response['code'] != "000")) {
            Log::info("purchase airtime response", $response);
            return ApiResponse::failed('An error occurred with purchasing airtime');
        }

        $user->walletTransactions()->create([
            'wallet_id' => $wallet->id,
            'reference' => $response['requestId'],
            'amount' => $amount,
            'prev_balance' => $wallet->balance,
            'new_balance' => $wallet->balance - $amount,
            'service_type' => ServiceType::AIRTIME->value,
            'transaction_type' => TransactionTypeEnum::debit->name,
            'status' => TransactionStatusEnum::SUCCESSFUL->name,
            'narration' => 'You purchased airtime from ' . $productName . ' for ₦' . $amount,
        ]);

        $wallet->balance -= $amount;
        $wallet->save();

        return ApiResponse::success("Airtime purchase successfully");
    }
}
