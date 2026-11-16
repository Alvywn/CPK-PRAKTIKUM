<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt_auth.php';

ResponseHelper::sendCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Database::getConnection();
    $currentUser = JwtAuth::requireAuth($pdo);
    $ownerId = (int) $currentUser['id'];

    // 1. READ CONTACTS (Kriteria 2)
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT id, owner_user_id, saved_name, contact_phone, created_at, updated_at
            FROM contacts
            WHERE owner_user_id = :owner_id
            ORDER BY saved_name ASC
        ');
        $stmt->execute([':owner_id' => $ownerId]);
        $contacts = $stmt->fetchAll();

        ResponseHelper::json(200, true, 'Daftar kontak berhasil dimuat.', [
            'contacts' => $contacts,
        ]);
    }

    // 2. CREATE CONTACT (Kriteria 2)
    if ($method === 'POST') {
        $body = ResponseHelper::getRequestBody();
        $savedName    = trim((string) ($body['saved_name'] ?? ''));
        $contactPhone = trim((string) ($body['contact_phone'] ?? ''));

        if ($savedName === '' || $contactPhone === '') {
            ResponseHelper::json(400, false, 'Nama kontak dan nomor HP kontak wajib diisi.');
        }

        $stmtInsert = $pdo->prepare('
            INSERT INTO contacts (owner_user_id, saved_name, contact_phone)
            VALUES (:owner_id, :saved_name, :contact_phone)
        ');
        $stmtInsert->execute([
            ':owner_id'      => $ownerId,
            ':saved_name'    => $savedName,
            ':contact_phone' => $contactPhone,
        ]);

        $contactId = (int) $pdo->lastInsertId();

        ResponseHelper::json(201, true, 'Kontak baru berhasil ditambahkan.', [
            'contact' => [
                'id'            => $contactId,
                'owner_user_id' => $ownerId,
                'saved_name'    => $savedName,
                'contact_phone' => $contactPhone,
            ],
        ]);
    }

    // 3. UPDATE CONTACT (Kriteria 2)
    if ($method === 'PUT') {
        $body = ResponseHelper::getRequestBody();
        $contactId    = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        $savedName    = trim((string) ($body['saved_name'] ?? ''));
        $contactPhone = trim((string) ($body['contact_phone'] ?? ''));

        if ($contactId <= 0 || $savedName === '' || $contactPhone === '') {
            ResponseHelper::json(400, false, 'ID kontak, nama kontak, dan nomor HP wajib diisi untuk pembaruan.');
        }

        $stmtCheck = $pdo->prepare('SELECT id FROM contacts WHERE id = :id AND owner_user_id = :owner LIMIT 1');
        $stmtCheck->execute([':id' => $contactId, ':owner' => $ownerId]);
        if (!$stmtCheck->fetch()) {
            ResponseHelper::json(404, false, 'Data kontak tidak ditemukan.');
        }

        $stmtUpdate = $pdo->prepare('
            UPDATE contacts
            SET saved_name = :saved_name, contact_phone = :phone
            WHERE id = :id AND owner_user_id = :owner_id
        ');
        $stmtUpdate->execute([
            ':saved_name' => $savedName,
            ':phone'      => $contactPhone,
            ':id'         => $contactId,
            ':owner_id'   => $ownerId,
        ]);

        ResponseHelper::json(200, true, 'Data kontak berhasil diperbarui.', [
            'contact' => [
                'id'            => $contactId,
                'owner_user_id' => $ownerId,
                'saved_name'    => $savedName,
                'contact_phone' => $contactPhone,
            ],
        ]);
    }

    // 4. DELETE CONTACT (Kriteria 2)
    if ($method === 'DELETE') {
        $body = ResponseHelper::getRequestBody();
        $contactId = (int) ($_GET['id'] ?? $body['id'] ?? 0);

        if ($contactId <= 0) {
            ResponseHelper::json(400, false, 'ID kontak wajib disertakan untuk penghapusan.');
        }

        $stmtDel = $pdo->prepare('DELETE FROM contacts WHERE id = :id AND owner_user_id = :owner');
        $stmtDel->execute([
            ':id'    => $contactId,
            ':owner' => $ownerId,
        ]);

        if ($stmtDel->rowCount() === 0) {
            ResponseHelper::json(404, false, 'Kontak tidak ditemukan atau sudah dihapus.');
        }

        ResponseHelper::json(200, true, 'Kontak berhasil dihapus dari buku kontak.');
    }

    ResponseHelper::json(405, false, 'Metode HTTP tidak didukung pada endpoint kontak.');
} catch (Throwable $e) {
    ResponseHelper::json(500, false, 'Terjadi kesalahan internal pada server: ' . $e->getMessage());
}
