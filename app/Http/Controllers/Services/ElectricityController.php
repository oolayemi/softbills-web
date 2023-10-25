<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
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

        if (empty($response) || !isset($response['content']) || (isset($response['response_description']) && $response['response_description'] != "000"))
        {
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
            'variation_code' => ['required','string',Rule::in(['prepaid', 'postpaid'])],
            'amount' => 'required|integer',
        ]);

        $requestId = now()->format('YmdHi') . Str::random(10);
        $user = $request->user();

        $data = [
            'request_id' => $requestId,
            'serviceID' => $request->service_id,
            'billersCode' => $request->billers_code,
            'variation_code' => $request->variation_code,
            'amount' => $request->amount,
            'phone' => $user->phone ?? "09061626364"
        ];

        Log::info("payload for purchase electricity", $data);

        $response = $vtPass->merchantPayment($data);

        if (empty($response) || !isset($response['content']) || (isset($response['code']) && $response['code'] != "000")) {
            Log::info("purchase meter number response", $response);
            return ApiResponse::failed('An error occurred with fetching meter number validation details');
        }

        return ApiResponse::success("Electricity purchase successfully", $response['content']);
    }
}
