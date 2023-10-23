<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\ThirdPartyAPIs\VtPassApis;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Helpers\ApiResponse;
use App\Rules\Phone;
use Illuminate\Validation\Rule;

class ElectricityController extends Controller
{
    public function providers(VtPassApis $vtPass): JsonResponse
    {
        $response = $vtPass->fetchElectricityProviders();

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000"))
        {
            return ApiResponse::failed("An error occurred, please try again");
        }
        return ApiResponse::success("Provider packages retrieved successfully", $response['content']);
    }

    public function validateMeterNumber(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $validated = $request->validate([
            'billersCode' => 'required|string',
            'serviceID' => 'required|string',
            'type' => 'required|string',
        ]);

        $data = [
            'billersCode' => $validated['billers_code'],
            'serviceID' => $validated['service_id'],
            'type' => $validated['type']
        ];

        $response = $vtPass->validateMeterNumber($validated);

        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            return ApiResponse::failed('An error occurred with fetching meter number validation details');
        }

        return ApiResponse::success("Meter Number details validated successfully", ['data' => $response['content']]);
    }

    public function purchase(Request $request, VtPassApis $vtPass): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'request_id' => 'required|string',
            'billersCode' => 'required|string',
            'variation_code' => ['required','string',Rule::in(['prepaid', 'postpaid'])],
            'amount' => 'required|integer',
            'phone' => ['required', new Phone],
            'serviceID' => 'required|string',
            'type' => 'required|string',
        ]);

        $response = $vtPass->merchantPayment($validated);

    }
}
