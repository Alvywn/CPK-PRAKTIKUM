<?php
declare(strict_types=1);

/**
 * Helper Response API JSON Standar (Kriteria 2)
 */
class ResponseHelper
{
    public static function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    public static function json(int $statusCode, bool $success, string $message, mixed $data = null): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'status'    => $statusCode,
            'success'   => $success,
            'message'   => $message,
            'timestamp' => date('c'),
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Membaca input request baik dari application/json maupun multipart/form-data
     */
    public static function getRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if (!$raw) {
                return [];
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            parse_str($raw, $parsed);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return [];
    }
}
