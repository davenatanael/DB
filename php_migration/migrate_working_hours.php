<?php

/**
 * migrate_working_hours.php
 *
 * Migrasi data working_hours dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.working_hours
 * Target : db_ybaik_new.working_hours
 *
 * Atribut:
 *   - `id`, `day_of_week`, `start_time`, `end_time`, `break_start`, `break_end`,
 *     `created_at`, `updated_at`
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
    echo "            MEMULAI MIGRASI DATA WORKING HOURS                      \n";
    echo "====================================================================\n\n";

    // 1. Pastikan tabel target sudah ada di database tujuan, jika belum buat tabelnya
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `$targetDb`.`working_hours` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `day_of_week` tinyint(3) unsigned NOT NULL,
            `start_time` time NOT NULL,
            `end_time` time NOT NULL,
            `break_start` time DEFAULT NULL,
            `break_end` time DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`working_hours`");
    echo "-> Tabel `working_hours` di $targetDb berhasil disiapkan dan dikosongkan.\n\n";

    // 3. Hitung jumlah baris di tabel sumber
    $totalSource = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`working_hours`")->fetchColumn();
    echo "-> Ditemukan $totalSource baris data pada `$sourceDb`.`working_hours`.\n";

    if ($totalSource === 0) {
        echo "Tidak ada data di tabel sumber yang perlu dimigrasikan.\n";
        exit;
    }

    // 4. Migrasi data
    echo "-> Memigrasikan data working_hours ke `$targetDb`...\n";
    $migrateSql = "
        INSERT INTO `$targetDb`.`working_hours` (
            `id`,
            `day_of_week`,
            `start_time`,
            `end_time`,
            `break_start`,
            `break_end`,
            `created_at`,
            `updated_at`
        )
        SELECT 
            `id`,
            `day_of_week`,
            `start_time`,
            `end_time`,
            `break_start`,
            `break_end`,
            `created_at`,
            `updated_at`
        FROM `$sourceDb`.`working_hours`
    ";

    $affected = $pdo->exec($migrateSql);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "-> Berhasil memindahkan $affected baris ke `$targetDb`.`working_hours`.\n\n";

    // 5. Tampilkan sampel hasil migrasi
    echo "--- Sampel Hasil Migrasi (db_ybaik_new.working_hours) ---\n";
    $sampleStmt = $pdo->query("SELECT * FROM `$targetDb`.`working_hours` ORDER BY `day_of_week` ASC");
    $rows = $sampleStmt->fetchAll();

    $dayNames = [
        1 => 'Senin / Monday',
        2 => 'Selasa / Tuesday',
        3 => 'Rabu / Wednesday',
        4 => 'Kamis / Thursday',
        5 => 'Jumat / Friday',
        6 => 'Sabtu / Saturday',
        7 => 'Minggu / Sunday',
    ];

    foreach ($rows as $row) {
        $dayLabel = $dayNames[$row['day_of_week']] ?? "Hari ke-{$row['day_of_week']}";
        $breakInfo = ($row['break_start'] && $row['break_end']) ? " (Break: {$row['break_start']} - {$row['break_end']})" : " (No Break)";
        echo "ID: {$row['id']} | {$dayLabel} | {$row['start_time']} - {$row['end_time']}{$breakInfo}\n";
    }

    echo "\n====================================================================\n";
    echo "               RINGKASAN HASIL MIGRASI WORKING HOURS                \n";
    echo "====================================================================\n";
    echo " - Total data sumber : $totalSource baris\n";
    echo " - Berhasil dimigrasi: $affected baris\n";
    echo "====================================================================\n";
    echo "    MIGRASI DATA WORKING HOURS SELESAI DENGAN SUKSES!               \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi working_hours: " . $e->getMessage() . "\n";
    exit(1);
}
