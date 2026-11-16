<?php
declare(strict_types=1);

/**
 * Konfigurasi Koneksi Database (PDO MariaDB/MySQL) untuk Kriteria 1 & 2
 */
class Database
{
    private static ?PDO $instance = null;

    private const DB_HOST = '127.0.0.1';
    private const DB_PORT = '3306';
    private const DB_NAME = 'db_messaging_k2';
    private const DB_USER = 'root';
    private const DB_PASS = '';

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = getenv('DB_HOST') ?: self::DB_HOST;
        $port = getenv('DB_PORT') ?: self::DB_PORT;
        $dbName = getenv('DB_NAME') ?: self::DB_NAME;
        $user = getenv('DB_USER') ?: self::DB_USER;
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : self::DB_PASS;

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), '1049') || str_contains(strtolower($e->getMessage()), 'unknown database')) {
                self::initializeDatabase($host, $port, $dbName, $user, $pass);
            } else {
                throw $e;
            }
        }

        return self::$instance;
    }

    private static function initializeDatabase(string $host, string $port, string $dbName, string $user, string $pass): void
    {
        $rootDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($rootDsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        } else {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
