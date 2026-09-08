<?php

/**
 * db.php
 *
 * Konfigurasi koneksi database dan fungsi helper bersama untuk seluruh modul migrasi.
 * Digunakan oleh semua skrip di folder php_consolidated.
 */

class MigrationDB
{
    private static ?PDO $pdo = null;

    public const SOURCE_DB = 'outclassco_marketing';
    public const TARGET_DB = 'db_ybaik_new';
    public const HOST      = '127.0.0.1';
    public const USER      = 'root';
    public const PASS      = '';

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                "mysql:host=" . self::HOST . ";dbname=" . self::TARGET_DB . ";charset=utf8mb4",
                self::USER,
                self::PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            self::$pdo->exec("SET sql_mode = ''");
        }
        return self::$pdo;
    }

    public static function disableForeignKeyChecks(PDO $pdo): void
    {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    }

    public static function enableForeignKeyChecks(PDO $pdo): void
    {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    public static function truncate(PDO $pdo, string $table): void
    {
        $pdo->exec("TRUNCATE TABLE `" . self::TARGET_DB . "`.`$table`");
    }

    public static function logHeader(string $title): void
    {
        echo "\n" . str_repeat("=", 75) . "\n";
        echo "  " . strtoupper($title) . "\n";
        echo str_repeat("=", 75) . "\n\n";
    }

    public static function logSection(string $step, string $name): void
    {
        echo "[$step] $name...\n";
    }

    public static function logResult(string $table, int $affected, ?int $sourceTotal = null): void
    {
        if ($sourceTotal !== null) {
            echo "   -> Sukses: $affected / $sourceTotal baris pada tabel `$table`.\n\n";
        } else {
            echo "   -> Sukses: $affected baris dimasukkan ke tabel `$table`.\n\n";
        }
    }
}
