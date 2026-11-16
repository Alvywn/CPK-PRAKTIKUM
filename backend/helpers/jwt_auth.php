<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/JWTExceptionWithPayloadInterface.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/BeforeValidException.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/ExpiredException.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/SignatureInvalidException.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/Key.php';
require_once __DIR__ . '/../lib/php-jwt-7.2.1/src/JWT.php';
require_once __DIR__ . '/response.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Helper Autentikasi berbasis JWT (firebase/php-jwt v7.2.1)
 */
class JwtAuth
{
    private const SECRET_KEY = 'PBP_CPK2_NATIVE_PHP_JWT_SECRET_KEY_2026';
    private const ALGO = 'HS256';
    private const TTL_SECONDS = 86400 * 7;

    public static function generateToken(array $user): string
    {
        $issuedAt = time();
        $payload = [
            'iss'   => 'pbp-messaging-api',
            'iat'   => $issuedAt,
            'exp'   => $issuedAt + self::TTL_SECONDS,
            'sub'   => (int) $user['id'],
            'name'  => $user['full_name'],
            'phone' => $user['phone_number'],
        ];

        $secret = getenv('JWT_SECRET') ?: self::SECRET_KEY;
        return JWT::encode($payload, $secret, self::ALGO);
    }

    public static function requireAuth(PDO $pdo): array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if ($authHeader === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $k => $v) {
                if (strtolower((string) $k) === 'authorization') {
                    $authHeader = (string) $v;
                    break;
                }
            }
        }

        if (!preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            ResponseHelper::json(401, false, 'Akses ditolak. Token autentikasi (Bearer JWT) tidak ditemukan.');
        }

        $jwt = $matches[1];
        $secret = getenv('JWT_SECRET') ?: self::SECRET_KEY;

        try {
            $decoded = JWT::decode($jwt, new Key($secret, self::ALGO));
            $userId = (int) ($decoded->sub ?? 0);

            if ($userId <= 0) {
                ResponseHelper::json(401, false, 'Token JWT tidak memiliki identitas pengguna yang valid.');
            }

            $stmt = $pdo->prepare('SELECT id, full_name, phone_number, created_at FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                ResponseHelper::json(401, false, 'Akun pengguna tidak ditemukan atau telah dihapus.');
            }

            return $user;
        } catch (Throwable $e) {
            ResponseHelper::json(401, false, 'Sesi tidak valid atau telah kedaluwarsa: ' . $e->getMessage());
        }
    }
}
