<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/JWTExceptionWithPayloadInterface.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/BeforeValidException.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/ExpiredException.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/SignatureInvalidException.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/Key.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/JWT.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

const JWT_SECRET = 'PBP_CPK2_SECRET_KEY_2026';

// Header CORS & JSON untuk semua endpoint API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function sendJson(int $code, bool $success, string $message, mixed $data = null): never
{
    http_response_code($code);
    $res = ['status' => $code, 'success' => $success, 'message' => $message];
    if ($data !== null) {
        $res['data'] = $data;
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

function getInput(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $decoded = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($decoded) ? $decoded : [];
}

function createJwt(array $user): string
{
    return JWT::encode([
        'iat' => time(),
        'exp' => time() + (86400 * 7),
        'sub' => (int) $user['id'],
    ], JWT_SECRET, 'HS256');
}

function requireUser(PDO $pdo): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        sendJson(401, false, 'Token JWT tidak ditemukan.');
    }

    try {
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $stmt = $pdo->prepare('SELECT id, full_name, phone_number FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $decoded->sub]);
        $user = $stmt->fetch();
        if (!$user) {
            sendJson(401, false, 'Akun tidak ditemukan.');
        }
        return $user;
    } catch (Throwable $e) {
        sendJson(401, false, 'Token tidak valid atau kedaluwarsa.');
    }
}
