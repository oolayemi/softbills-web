<?php

namespace App\Http\Controllers\Services;

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

        return ApiResponse::success("Provider packages retrieved successfully", ['billers' => $response['content']]);
    }

    public function fetchPackages($type, VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchTvBillersForProvider($type);

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000")) {
            return ApiResponse::failed('An error occurred with fetching provider packages');
        }

        return ApiResponse::success(
            $type.' packages retrieved successfully',
            ['billers' => $response['content']]);
    }

    public function validateSmartCard(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $request->validate([
            'billers_code' => 'required|string',
            'service_id' => 'required|string',
        ]);

        $data = [
            'billersCode' => $request->billers_code,
            'serviceID' => $request->service_id
        ];

        $response = $vtPass->validateSmartCard($data);

        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            return ApiResponse::failed('An error occurred with fetching validating card details');
        }

        return ApiResponse::success("SmartCard details validated successfully", ['data' => $response['content']]);
    }

    public function purchase(Request $request, VtPassApis $vtPass)
    {
        $data = $request->validate([
            'service_id' => 'required',
            'billers_code' => 'required',
            'variation_code' => 'required',
            'amount' => 'required',
            'phone' => 'required',
            'subscription_type' => 'required',
        ]);

        $user = $request->user();
    }
}
