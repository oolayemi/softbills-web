<?php

namespace App\Http\Controllers\Services;

use App\Enums\ApiResponseEnum;
use App\Http\Controllers\Controller;
use App\Services\Constants\Providers;
use App\Services\Helpers\ApiResponse;
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

//        if (! checkWalletBalance($wallet, $data['amount'])) {
//            return response()->json([
//                'status' => ApiResponseEnum::failed(),
//                'message' => 'You don\'t have sufficient balance to continue.',
//            ]);
//        }

        $requestId = now()->format('YmdHi') . \Str::random(10);
        $user = $request->user();

        $data = [
            'request_id' => $requestId,
            'serviceID' => $request->service_id,
            'billersCode' => $request->billers_code,
            'amount' => $request->amount,
            'phone' => $user->phone ?? '09061626364',
            'subscription_type' => 'change'
        ];

        $response = $vtPass->purchaseCableTv($data);

        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            \Log::info("whats wrong - purchase", $response);
            return ApiResponse::failed('An error occurred with fetching validating cable details');
        }

        return ApiResponse::success("SmartCard details validated successfully", ['data' => $response['content']]);
    }
}
