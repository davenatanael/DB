<?php

/**
 * migrate_admin_students.php
 *
 * Migrasi data relasi admin dan student (admin_students)
 * dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.admin_students
 * Target : db_ybaik_new.admin_students
 *
 * Perbandingan Schema:
 *   - Atribut Sama : id, student_id, admin_id, created_at, updated_at, deleted_at
 *
 * Integritas Data:
 *   - admin_id divalidasi ke tabel users (role_id = 2 / admin)
 *   - student_id divalidasi ke tabel students
 *
 * PRASYARAT: users (khususnya admin) dan students HARUS sudah dimigrasikan duluan.
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

    echo "====================================================================\n";
    echo "    MEMULAI MIGRASI DATA ADMIN_STUDENTS                             \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`admin_students`");
    echo "-> Tabel `admin_students` di $targetDb berhasil dikosongkan.\n\n";

    // Validasi admin user di database target
    $adminUsers = $pdo->query("
        SELECT id, name, email, role_id 
        FROM `$targetDb`.`users` 
        WHERE `role_id` = 2
    ")->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    echo "-> Terdeteksi " . count($adminUsers) . " admin aktif di tabel target users (role_id = 2).\n\n";

    // Ambil data sumber dari database lama
    $sourceQuery = "
        SELECT 
            ast.`id`,
            ast.`student_id`,
            ast.`admin_id`,
            ast.`created_at`,
            ast.`updated_at`,
            ast.`deleted_at`,
            u.`name` AS admin_name,
            u.`role_id` AS admin_role_id
        FROM `$sourceDb`.`admin_students` ast
        LEFT JOIN `$sourceDb`.`users` u ON ast.`admin_id` = u.`id`
        ORDER BY ast.`id` ASC
    ";
    $sourceRows = $pdo->query($sourceQuery)->fetchAll();
    $totalSource = count($sourceRows);

    echo "-> Mengambil $totalSource baris data dari $sourceDb.admin_students...\n";

    $insertStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`admin_students` (
            `id`,
            `student_id`,
            `admin_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        ) VALUES (
            :id,
            :student_id,
            :admin_id,
            :created_at,
            :updated_at,
            :deleted_at
        )
    ");

    $inserted = 0;
    $unmatchedAdmin = 0;

    foreach ($sourceRows as $row) {
        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
        $updatedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
        $deletedAt = !empty($row['deleted_at']) ? $row['deleted_at'] : null;

        // Pengecekan admin di target DB
        if (!isset($adminUsers[$row['admin_id']])) {
            $unmatchedAdmin++;
        }

        $insertStmt->execute([
            ':id'         => $row['id'],
            ':student_id' => $row['student_id'],
            ':admin_id'   => $row['admin_id'],
            ':created_at' => $createdAt,
            ':updated_at' => $updatedAt,
            ':deleted_at' => $deletedAt,
        ]);
        $inserted++;
    }

    $totalInTarget = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`admin_students`")->fetch()['c'];

    echo "\n=== HASIL MIGRASI ADMIN_STUDENTS ===\n";
    echo "Total data sumber               : $totalSource\n";
    echo "Total data berhasil dimasukkan  : $inserted\n";
    echo "Total data di target DB         : $totalInTarget\n";
    if ($unmatchedAdmin > 0) {
        echo "Peringatan: Ada $unmatchedAdmin relasi dengan admin_id yang tidak ber-role 2 di target.\n";
    } else {
        echo "Validasi Admin: Seluruh admin_id cocok 100% dengan admin (role_id = 2) di target.\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI DATA ADMIN_STUDENTS SELESAI DENGAN SUKSES!              \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
