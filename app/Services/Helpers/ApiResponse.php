<?php

namespace App\Services\Helpers;

use App\Services\Enums\ApiResponseEnum;
use App\Services\Traits\BaseServiceTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;

class ApiResponse
{
    use BaseServiceTrait;

    public static function success(string $message = null, array $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => ApiResponseEnum::success->name,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function failed(string $message, int $code = 400, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => ApiResponseEnum::failed->name,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function validationError(MessageBag $errors): JsonResponse
    {
        return self::failed((new ApiResponse)->composeValidationError($errors), 422, $errors->all());
    }
}
