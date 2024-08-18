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
    private $service_code = [
        'mtn' => 'MTNVTU',
        'airtel' => 'AIRTELVTU',
        '9mobile' => '9MOBILEVTU',
        'glo' => 'GLOVTU',
    ];

    public function providers(VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchAirtimeProviders();

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed("An error occurred, please try again");
        }

        \Log::info("Airtime providers", $response['content']);

        return ApiResponse::success("Airtime providers packages retrieved successfully", $response['content']);
    }

    public function purchase(Request $request, SageCloudServices $sageCloud): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'mobile' => ['required', new Phone],
            'amount' => ['required', 'numeric', 'decimal:0,2'],
            'operator' => ['required'],
        ]);

        $data = $request->all();
        $wallet = $user->wallet;

        if (!GeneralHelper::hasEnoughBalance($wallet, $request->amount)) {
            return ApiResponse::failed("You don't have sufficient balance to continue");
        }

        $payload = [
            'reference' => GeneralHelper::generateReference(ServiceType::AIRTIME->value),
            'network' => strtoupper($data['operator']),
            'service' => $this->service_code[$data['operator']],
            'phone' => $data['mobile'],
            'amount' => $data['amount'],
        ];

        $amount = floatval($request->amount);

        $response = $sageCloud->purchaseAirtime($payload);

        if (isset($response['status']) && $response['status'] != 'failed') {

            $user->walletTransactions()->create([
                'wallet_id' => $wallet->id,
                'reference' => $payload['reference'],
                'amount' => $amount,
                'prev_balance' => $wallet->balance,
                'new_balance' => $wallet->balance - $amount,
                'service_type' => ServiceType::AIRTIME->value,
                'transaction_type' => TransactionTypeEnum::debit->name,
                'status' => TransactionStatusEnum::SUCCESSFUL->name,
                'narration' => $payload['network'] . ' airtime purchased of ₦' . $payload['amount'] . ' to ' . $payload['phone']
            ]);

            $wallet->balance -= $amount;
            $wallet->save();

            return ApiResponse::success("Airtime request submitted");
        }

        return ApiResponse::failed("Your airtime request failed");
    }
}
