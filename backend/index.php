<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers/common.php';

sendJson(200, true, 'NusaChat API (Kriteria 1 & 2) aktif.', [
    'php_version' => PHP_VERSION,
]);
