<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers/response.php';

ResponseHelper::sendCorsHeaders();

ResponseHelper::json(200, true, 'NusaChat Native PHP Backend API (Kriteria 1 & 2) aktif.', [
    'php_version' => PHP_VERSION,
    'endpoints'   => [
        'auth'     => '/api/auth.php?action={register|login|me|delete_account}',
        'contacts' => '/api/contacts.php (GET, POST, PUT, DELETE)',
    ],
]);
