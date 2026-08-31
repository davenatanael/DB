<?php

/**
 * migrate_enrollment_scholarships.php
 *
 * Migrasi enrollment_scholarships (lama -> baru).
 *
 * PRASYARAT: enrollments HARUS sudah dimigrasikan duluan (student_program_id
 * di skema baru sekarang FK ke enrollments, bukan lagi ke student_programs
 * yang sudah dihapus).
 *
 * Struktur & nama kolom TIDAK berubah sama sekali -- cuma target FK-nya yang
 * "pindah rumah" dari student_programs ke enrollments. Karena enrollments
 * adalah rename 1:1 dari student_programs (id dipertahankan sama persis),
 * nilai student_program_id tidak perlu ditranslate, cukup divalidasi ulang
 * terhadap enrollments di database BARU.
 *
 * Data sudah dicek: 161 baris total, 2 di antaranya student_program_id NULL
 * (kolomnya nullable, tetap valid masuk), 0 yatim.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi enrollment_scholarships...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`enrollment_scholarships`");

    // LEFT JOIN (bukan INNER) karena student_program_id nullable -- baris
    // dengan student_program_id NULL tetap harus ikut masuk.
    $migrateSql = "
        INSERT INTO `$targetDb`.`enrollment_scholarships` (
            `id`, `student_program_id`, `category`, `nominal_tuition_fee`, `nominal_accomodation`,
            `nominal_stipend`, `nominal_tuition_fee_percentage`, `nominal_accomodation_percentage`,
            `created_at`, `updated_at`
        )
        SELECT
            es.`id`, es.`student_program_id`, es.`category`, es.`nominal_tuition_fee`,
            es.`nominal_accomodation`, es.`nominal_stipend`, es.`nominal_tuition_fee_percentage`,
            es.`nominal_accomodation_percentage`, es.`created_at`, es.`updated_at`
        FROM `$sourceDb`.`enrollment_scholarships` es
        LEFT JOIN `$targetDb`.`enrollments` e ON es.`student_program_id` = e.`id`
        WHERE es.`student_program_id` IS NULL OR e.`id` IS NOT NULL
    ";
    $affected = $pdo->exec($migrateSql);

    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`enrollment_scholarships`")->fetch()['c'];
    $skipped = $totalSource - $affected;

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "-> Total di sumber : $totalSource\n";
    echo "-> Berhasil dimigrasi : $affected\n";
    echo "-> Di-skip (student_program_id tidak ketemu di enrollments) : $skipped\n";

    if ($skipped > 0) {
        echo "\nDetail baris yang di-skip:\n";
        $skipStmt = $pdo->query("
            SELECT es.id, es.student_program_id
            FROM `$sourceDb`.`enrollment_scholarships` es
            LEFT JOIN `$targetDb`.`enrollments` e ON es.student_program_id = e.id
            WHERE es.student_program_id IS NOT NULL AND e.id IS NULL
        ");
        foreach ($skipStmt->fetchAll() as $row) {
            echo "   - id={$row['id']} student_program_id={$row['student_program_id']}\n";
        }
    }

    echo "\nMigrasi enrollment_scholarships selesai.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}