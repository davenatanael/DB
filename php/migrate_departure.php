<?php

/**
 * migrate_departure.php
 *
 * Migrasi data departure dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.departure
 * Target : db_ybaik_new.departure
 *
 * Perbandingan Schema:
 *   - Seluruh atribut sama (1:1):
 *     `id`, `student_id`, `student_program_id`, `student_program_detail_id`,
 *     `univ_program_id`, `enrollment_scholarship_id`, `package_category`,
 *     `depart`, `created_at`, `updated_at`, `deleted_at`
 *
 * Relasi di Skema Baru:
 *   - student_id                -> FK ke students.id
 *   - student_program_id        -> FK ke enrollments.id
 *   - student_program_detail_id -> FK ke enrollment_programs.id
 *   - univ_program_id           -> FK ke univ_programs.id
 *   - enrollment_scholarship_id -> FK ke enrollment_scholarships.id
 *
 * PRASYARAT: students, enrollments, enrollment_programs, univ_programs,
 *             dan enrollment_scholarships HARUS sudah dimigrasikan duluan.
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
    echo "            MEMULAI MIGRASI DATA DEPARTURE                          \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`departure`");
    echo "-> Tabel `departure` berhasil dikosongkan.\n\n";

    echo "Memigrasi data departure...\n";
    $migrateSql = "
        INSERT INTO `$targetDb`.`departure` (
            `id`, `student_id`, `student_program_id`, `student_program_detail_id`,
            `univ_program_id`, `enrollment_scholarship_id`, `package_category`,
            `depart`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            d.`id`,
            d.`student_id`,
            d.`student_program_id`,
            d.`student_program_detail_id`,
            d.`univ_program_id`,
            d.`enrollment_scholarship_id`,
            d.`package_category`,
            d.`depart`,
            d.`created_at`,
            d.`updated_at`,
            d.`deleted_at`
        FROM `$sourceDb`.`departure` d
    ";
    $affected = $pdo->exec($migrateSql);

    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`departure`")->fetch()['c'];
    $skipped     = $totalSource - $affected;

    echo "   -> Total di sumber    : $totalSource\n";
    echo "   -> Berhasil dimigrasi : $affected\n";
    echo "   -> Di-skip            : $skipped\n";

    if ($skipped > 0) {
        echo "\n   Detail baris departure yang di-skip:\n";
        $skipStmt = $pdo->query("
            SELECT d.id, d.student_id, d.student_program_id
            FROM `$sourceDb`.`departure` d
            LEFT JOIN `$targetDb`.`departure` target ON d.id = target.id
            WHERE target.id IS NULL
        ");
        foreach ($skipStmt->fetchAll() as $row) {
            echo "      - id={$row['id']} student_id={$row['student_id']} student_program_id={$row['student_program_id']}\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "            MIGRASI DEPARTURE SELESAI!                              \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
