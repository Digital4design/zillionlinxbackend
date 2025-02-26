<?php

use App\Models\NotificationLanguage;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;


if (!function_exists('success')) {
    function success($message, $data = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}

if (!function_exists('error')) {
    function error($message, $data = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}

if (!function_exists('sessionError')) {
    function sessionError($message, $data = null, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}