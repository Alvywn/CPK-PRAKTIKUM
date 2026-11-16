<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/common.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDb();

    // GET /api/auth.php?action=me -> Ambil profil akun aktif
    if ($method === 'GET' && ($action === 'me' || $action === '')) {
        $user = requireUser($pdo);
        sendJson(200, true, 'Profil dimuat.', ['user' => $user]);
    }

    // POST /api/auth.php?action=register -> Registrasi pengguna baru
    if ($method === 'POST' && $action === 'register') {
        $input = getInput();
        $name  = trim((string) ($input['full_name'] ?? ''));
        $phone = trim((string) ($input['phone_number'] ?? ''));
        $pass  = (string) ($input['password'] ?? '');

        if ($name === '' || $phone === '' || strlen($pass) < 6) {
            sendJson(400, false, 'Nama, nomor HP, dan kata sandi (min. 6 karakter) wajib diisi.');
        }

        $cek = $pdo->prepare('SELECT id FROM users WHERE phone_number = ? LIMIT 1');
        $cek->execute([$phone]);
        if ($cek->fetch()) {
            sendJson(400, false, 'Nomor HP sudah terdaftar.');
        }

        $stmt = $pdo->prepare('INSERT INTO users (full_name, phone_number, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $phone, password_hash($pass, PASSWORD_BCRYPT)]);

        $user = [
            'id'           => (int) $pdo->lastInsertId(),
            'full_name'    => $name,
            'phone_number' => $phone,
        ];

        sendJson(201, true, 'Registrasi berhasil.', [
            'token' => createJwt($user),
            'user'  => $user,
        ]);
    }

    // POST /api/auth.php?action=login -> Login pengguna
    if ($method === 'POST' && $action === 'login') {
        $input = getInput();
        $phone = trim((string) ($input['phone_number'] ?? ''));
        $pass  = (string) ($input['password'] ?? '');

        $stmt = $pdo->prepare('SELECT id, full_name, phone_number, password_hash FROM users WHERE phone_number = ? LIMIT 1');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            sendJson(401, false, 'Nomor HP atau kata sandi salah.');
        }

        unset($user['password_hash']);
        sendJson(200, true, 'Login berhasil.', [
            'token' => createJwt($user),
            'user'  => $user,
        ]);
    }

    // DELETE /api/auth.php?action=delete_account -> Hapus akun permanen
    if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete_account')) {
        $user = requireUser($pdo);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
        sendJson(200, true, 'Akun berhasil dihapus.');
    }

    sendJson(404, false, 'Endpoint tidak ditemukan.');
} catch (Throwable $e) {
    sendJson(500, false, 'Server error: ' . $e->getMessage());
}
