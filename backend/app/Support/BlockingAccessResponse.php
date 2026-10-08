<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class BlockingAccessResponse
{
    public static function make(string $code, string $message, string $redirect, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'redirect' => $redirect,
        ], $extra), 423);
    }
}

