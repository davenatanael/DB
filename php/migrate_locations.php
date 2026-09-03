<?php

/**
 * migrate_locations.php
 *
 * Migrasi data locations dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.locations
 * Target : db_ybaik_new.locations
 *
 * Perbandingan Schema:
 *   - Atribut (1:1):
 *     `id`, `name`, `status`, `created_at`, `updated_at`, `deleted_at`
 *
 * Catatan Data:
 *   - Kolom `created_at` di DB lama dapat bernilai '0000-00-00 00:00:00',
 *     sehingga dinormalisasi menggunakan updated_at / NOW() agar valid di MySQL strict mode.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("SET sql_mode = ''");

    echo "====================================================================\n";
    echo "            MEMULAI MIGRASI DATA LOCATIONS                          \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`locations`");
    echo "-> Tabel `locations` di $targetDb berhasil dikosongkan.\n\n";

    echo "Memigrasi data locations...\n";
    $migrateSql = "
        INSERT INTO `$targetDb`.`locations` (
            `id`,
            `name`,
            `status`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            l.`id`,
            l.`name`,
            l.`status`,
            CASE 
                WHEN l.`created_at` IS NULL OR l.`created_at` = '0000-00-00 00:00:00' 
                THEN COALESCE(NULLIF(l.`updated_at`, '0000-00-00 00:00:00'), NOW())
                ELSE l.`created_at`
            END AS `created_at`,
            CASE 
                WHEN l.`updated_at` IS NULL OR l.`updated_at` = '0000-00-00 00:00:00' 
                THEN NOW()
                ELSE l.`updated_at`
            END AS `updated_at`,
            NULLIF(l.`deleted_at`, '0000-00-00 00:00:00') AS `deleted_at`
        FROM `$sourceDb`.`locations` l
        ORDER BY l.`id` ASC
    ";

    $affected = $pdo->exec($migrateSql);

    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`locations`")->fetch()['c'];
    $totalTarget = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`locations`")->fetch()['c'];

    echo "   -> Total di sumber    : $totalSource\n";
    echo "   -> Berhasil dimigrasi : $affected\n";
    echo "   -> Total di target DB : $totalTarget\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "            MIGRASI LOCATIONS SELESAI DENGAN SUKSES!                \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi locations: " . $e->getMessage() . "\n";
    exit(1);
}
