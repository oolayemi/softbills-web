<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\Constants\Providers;
use App\Services\Helpers\ApiResponse;
use App\Services\ThirdPartyAPIs\MegaSubApis;
use Illuminate\Http\JsonResponse;
use App\Rules\AirtimeAmount;
use App\Rules\Phone;

class AirtimeController extends Controller
{
    public function buyAirtime(Request $request, MegaSubApis $megaSub): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'mobile_number' => ['required', new Phone],
            'network_api_id' => ['required','integer',Rule::in([5,6,7,8])],
            'airtime_api_id' => ['required','integer',Rule::in([45,46,47,48])],
            'amount' => ['required', new AirtimeAmount],
            'validatephonenetwork' => ['required', new Phone],
            'duplication_check' => 'required|string'
        ]);

        $response = $megaSub->purchaseAirtime($validated);
        if (empty($response) || (isset($response['status']) && $response['Status'] != "Success")) {
            return ApiResponse::failed("An error occurred, please try again");
        }

        return ApiResponse::success("Airtime purchased successfully", ['message' => $response['Detail']['message']]);
    }
}
