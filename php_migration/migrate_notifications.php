<?php

/**
 * migrate_notifications.php
 *
 * Migrasi data notifications dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.notifications
 * Target : db_ybaik_new.notifications
 *
 * Perbandingan Schema:
 *   - Atribut (1:1):
 *     `id`, `admin_student_id`, `message`, `status`, `to`,
 *     `created_at`, `updated_at`, `deleted_at`
 *
 * Relasi di Skema Baru:
 *   - `admin_student_id` -> FK ke `admin_students` (`id`)
 *
 * PRASYARAT:
 *   - `migrate_admin_students.php` HARUS sudah dijalankan terlebih dahulu
 *     agar relasi admin_student_id valid.
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
    echo "            MEMULAI MIGRASI DATA NOTIFICATIONS                      \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`notifications`");
    echo "-> Tabel `notifications` di $targetDb berhasil dikosongkan.\n\n";

    // Pengecekan foreign key referensi ke admin_students di target DB
    $totalAdminStudents = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`admin_students`")->fetch()['c'];
    echo "-> Terdeteksi $totalAdminStudents baris di `$targetDb`.`admin_students`.\n";

    if ($totalAdminStudents === 0) {
        echo "PERINGATAN: Tabel `admin_students` di $targetDb masih kosong!\n";
        echo "Pastikan `migrate_admin_students.php` sudah dijalankan sebelumnya.\n\n";
    }

    echo "Memigrasi data notifications...\n";
    $migrateSql = "
        INSERT INTO `$targetDb`.`notifications` (
            `id`,
            `admin_student_id`,
            `message`,
            `status`,
            `to`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            n.`id`,
            n.`admin_student_id`,
            n.`message`,
            COALESCE(n.`status`, 0) AS `status`,
            n.`to`,
            CASE 
                WHEN n.`created_at` IS NULL OR n.`created_at` = '0000-00-00 00:00:00' 
                THEN COALESCE(NULLIF(n.`updated_at`, '0000-00-00 00:00:00'), NOW())
                ELSE n.`created_at`
            END AS `created_at`,
            CASE 
                WHEN n.`updated_at` IS NULL OR n.`updated_at` = '0000-00-00 00:00:00' 
                THEN NOW()
                ELSE n.`updated_at`
            END AS `updated_at`,
            NULLIF(n.`deleted_at`, '0000-00-00 00:00:00') AS `deleted_at`
        FROM `$sourceDb`.`notifications` n
        INNER JOIN `$targetDb`.`admin_students` ast ON n.`admin_student_id` = ast.`id`
        ORDER BY n.`id` ASC
    ";

    $affected = $pdo->exec($migrateSql);

    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`notifications`")->fetch()['c'];
    $totalTarget = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`notifications`")->fetch()['c'];
    $skipped     = $totalSource - $affected;

    echo "   -> Total di sumber              : $totalSource\n";
    echo "   -> Berhasil dimigrasi           : $affected\n";
    echo "   -> Di-skip (orphan / tanpa FK)  : $skipped\n";
    echo "   -> Total di target DB           : $totalTarget\n";

    if ($skipped > 0) {
        echo "\n   Detail data notifications yang di-skip karena admin_student_id tidak ditemukan:\n";
        $skipStmt = $pdo->query("
            SELECT n.id, n.admin_student_id, n.message
            FROM `$sourceDb`.`notifications` n
            LEFT JOIN `$targetDb`.`admin_students` ast ON n.admin_student_id = ast.id
            WHERE ast.id IS NULL
        ");
        foreach ($skipStmt->fetchAll() as $row) {
            echo "      - id={$row['id']} admin_student_id={$row['admin_student_id']} message='{$row['message']}'\n";
        }
    } else {
        echo "\nValidasi: Semua admin_student_id pada notifications berhasil diverifikasi dan valid!\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "            MIGRASI NOTIFICATIONS SELESAI DENGAN SUKSES!            \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi notifications: " . $e->getMessage() . "\n";
    exit(1);
}
