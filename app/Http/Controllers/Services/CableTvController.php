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
        $validated = $request->validate([
            'billersCode' => 'required|string',
            'serviceID' => 'required|string',
        ]);

        $response = $vtPass->validateSmartCard($validated);

        if (! $response['success']) {
            return response()->json([
                'status' => ApiResponseEnum::failed(),
                'message' => $response['message'] ?? 'Could not validate smart card details',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => ApiResponseEnum::success(),
            'message' => 'SmartCard details validated successfully',
            'data' => $response['customer'],
        ]);
    }
}
