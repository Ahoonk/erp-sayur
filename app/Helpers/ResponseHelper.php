<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param mixed $errorsOrCode  If integer, treated as HTTP status code (errors = null). Otherwise treated as errors payload.
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($message = 'Error', $errorsOrCode = null, $code = 400)
    {
        if (is_int($errorsOrCode)) {
            $httpCode = $errorsOrCode;
            $errors = null;
        } else {
            $httpCode = $code;
            $errors = $errorsOrCode;
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $httpCode);
    }
}
