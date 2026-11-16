<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt_auth.php';

ResponseHelper::sendCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = Database::getConnection();

    // 1. GET PROFIL AKUN SAAT INI
    if ($method === 'GET' && ($action === 'me' || $action === '')) {
        $currentUser = JwtAuth::requireAuth($pdo);
        ResponseHelper::json(200, true, 'Profil pengguna berhasil dimuat.', [
            'user' => $currentUser,
        ]);
    }

    // 2. REGISTRASI PENGGUNA BARU (Kriteria 2)
    if ($method === 'POST' && $action === 'register') {
        $body = ResponseHelper::getRequestBody();

        $fullName    = trim((string) ($body['full_name'] ?? ''));
        $phoneNumber = trim((string) ($body['phone_number'] ?? ''));
        $password    = (string) ($body['password'] ?? '');

        if ($fullName === '' || $phoneNumber === '' || strlen($password) < 6) {
            ResponseHelper::json(400, false, 'Nama lengkap, nomor HP, dan kata sandi (minimal 6 karakter) wajib diisi.');
        }

        $stmtCheck = $pdo->prepare('SELECT id FROM users WHERE phone_number = :phone LIMIT 1');
        $stmtCheck->execute([':phone' => $phoneNumber]);
        if ($stmtCheck->fetch()) {
            ResponseHelper::json(400, false, 'Nomor HP sudah terdaftar pada akun lain.');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmtInsert = $pdo->prepare('
            INSERT INTO users (full_name, phone_number, password_hash)
            VALUES (:full_name, :phone_number, :password_hash)
        ');
        $stmtInsert->execute([
            ':full_name'     => $fullName,
            ':phone_number'  => $phoneNumber,
            ':password_hash' => $passwordHash,
        ]);

        $userId = (int) $pdo->lastInsertId();
        $newUser = [
            'id'           => $userId,
            'full_name'    => $fullName,
            'phone_number' => $phoneNumber,
        ];

        $token = JwtAuth::generateToken($newUser);

        ResponseHelper::json(201, true, 'Registrasi pengguna berhasil.', [
            'token' => $token,
            'user'  => $newUser,
        ]);
    }

    // 3. LOGIN PENGGUNA (Kriteria 2)
    if ($method === 'POST' && $action === 'login') {
        $body = ResponseHelper::getRequestBody();
        $phoneNumber = trim((string) ($body['phone_number'] ?? ''));
        $password    = (string) ($body['password'] ?? '');

        if ($phoneNumber === '' || $password === '') {
            ResponseHelper::json(400, false, 'Nomor HP dan kata sandi wajib diisi.');
        }

        $stmt = $pdo->prepare('SELECT id, full_name, phone_number, password_hash, created_at FROM users WHERE phone_number = :phone LIMIT 1');
        $stmt->execute([':phone' => $phoneNumber]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            ResponseHelper::json(401, false, 'Nomor HP atau kata sandi salah.');
        }

        unset($user['password_hash']);
        $token = JwtAuth::generateToken($user);

        ResponseHelper::json(200, true, 'Login berhasil.', [
            'token' => $token,
            'user'  => $user,
        ]);
    }

    // 4. PENGHAPUSAN AKUN PENGGUNA (Kriteria 2)
    if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete_account')) {
        $currentUser = JwtAuth::requireAuth($pdo);

        $stmtDelete = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmtDelete->execute([':id' => $currentUser['id']]);

        ResponseHelper::json(200, true, 'Akun pengguna beserta seluruh data kontaknya berhasil dihapus.');
    }

    ResponseHelper::json(404, false, 'Endpoint atau metode autentikasi tidak ditemukan.');
} catch (Throwable $e) {
    ResponseHelper::json(500, false, 'Terjadi kesalahan internal pada server: ' . $e->getMessage());
}
