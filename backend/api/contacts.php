<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/common.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo     = getDb();
    $user    = requireUser($pdo);
    $ownerId = (int) $user['id'];

    // GET /api/contacts.php -> Ambil daftar kontak milik akun ini
    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT id, saved_name, contact_phone, created_at FROM contacts WHERE owner_user_id = ? ORDER BY saved_name ASC');
        $stmt->execute([$ownerId]);
        sendJson(200, true, 'Daftar kontak dimuat.', ['contacts' => $stmt->fetchAll()]);
    }

    // POST /api/contacts.php -> Tambah kontak baru
    if ($method === 'POST') {
        $input = getInput();
        $name  = trim((string) ($input['saved_name'] ?? ''));
        $phone = trim((string) ($input['contact_phone'] ?? ''));

        if ($name === '' || $phone === '') {
            sendJson(400, false, 'Nama dan nomor HP kontak wajib diisi.');
        }

        $stmt = $pdo->prepare('INSERT INTO contacts (owner_user_id, saved_name, contact_phone) VALUES (?, ?, ?)');
        $stmt->execute([$ownerId, $name, $phone]);

        sendJson(201, true, 'Kontak ditambahkan.', [
            'contact' => [
                'id'            => (int) $pdo->lastInsertId(),
                'saved_name'    => $name,
                'contact_phone' => $phone,
            ],
        ]);
    }

    // PUT /api/contacts.php -> Ubah kontak
    if ($method === 'PUT') {
        $input = getInput();
        $id    = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $name  = trim((string) ($input['saved_name'] ?? ''));
        $phone = trim((string) ($input['contact_phone'] ?? ''));

        if ($id <= 0 || $name === '' || $phone === '') {
            sendJson(400, false, 'ID, nama, dan nomor HP kontak wajib diisi.');
        }

        $stmt = $pdo->prepare('UPDATE contacts SET saved_name = ?, contact_phone = ? WHERE id = ? AND owner_user_id = ?');
        $stmt->execute([$name, $phone, $id, $ownerId]);

        if ($stmt->rowCount() === 0) {
            sendJson(404, false, 'Kontak tidak ditemukan atau tidak ada perubahan.');
        }

        sendJson(200, true, 'Kontak diperbarui.');
    }

    // DELETE /api/contacts.php?id=... -> Hapus kontak
    if ($method === 'DELETE') {
        $input = getInput();
        $id    = (int) ($_GET['id'] ?? $input['id'] ?? 0);

        if ($id <= 0) {
            sendJson(400, false, 'ID kontak wajib diisi.');
        }

        $stmt = $pdo->prepare('DELETE FROM contacts WHERE id = ? AND owner_user_id = ?');
        $stmt->execute([$id, $ownerId]);

        if ($stmt->rowCount() === 0) {
            sendJson(404, false, 'Kontak tidak ditemukan.');
        }

        sendJson(200, true, 'Kontak dihapus.');
    }

    sendJson(405, false, 'Metode HTTP tidak didukung.');
} catch (Throwable $e) {
    sendJson(500, false, 'Server error: ' . $e->getMessage());
}
