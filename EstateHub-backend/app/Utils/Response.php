<?php
namespace App\Utils;

class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        if (is_array($data) && isset($data['status']) && $data['status'] === 'error') {
            // Error response
            $response = [
                'status' => 'error',
                'error' => $data['error'],
                'timestamp' => date('c')
            ];
        } else {
            // Success response
            $response = [
                'status' => 'success',
                'data' => $data,
                'timestamp' => date('c')
            ];
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    public static function error($message, $code = 'VALIDATION_ERROR', $details = [], $statusCode = 400) {
        self::json([
            'status' => 'error',
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ]
        ], $statusCode);
    }

    public static function success($data = null, $message = null) {
        $response = [];
        if ($data !== null) {
            $response = $data;
        }
        if ($message !== null) {
            $response['message'] = $message;
        }
        self::json($response);
    }
}