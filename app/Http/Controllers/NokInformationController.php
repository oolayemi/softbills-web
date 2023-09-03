<?php

namespace App\Http\Controllers;

use App\Rules\Phone;
use App\Services\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NokInformationController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'firstname' => ['required', 'string'],
            'lastname' => ['required', 'string'],
            'email' => ['required', 'email'],
            'address' => ['required', 'string'],
            'phone' => ['required', new Phone],
            'relationship' => ['required', 'string'],
        ]);

        $user->nok()->create($validated);

        return ApiResponse::success("Next of Kin information saved successfully");
    }

    public function index(): JsonResponse
    {
        $user = \request()->user();
        $nok = $user->nok?->toArray();

        return ApiResponse::success('Next of Kin information fetched successfully', $nok);
    }
}
