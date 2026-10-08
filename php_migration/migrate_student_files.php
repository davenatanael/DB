<?php

/**
 * migrate_student_files.php
 *
 * Migrasi grup tabel file dokumen student:
 *   1. student_file_types            (prasyarat, FK dari student_files, file_type_tutorial, student_file_type_univ_program)
 *   2. student_files                 (FK -> students, student_file_types)
 *   3. file_type_tutorial            (FK -> student_file_types)
 *   4. student_file_type_univ_program(FK -> student_file_types, universities, univ_programs)
 *
 * Catatan Struktur student_file_type_univ_program:
 * - Kolom bersama: id, student_file_type_id, univ_id, program_id, created_at, updated_at
 * - program_id bersifat NULLABLE (NULL berarti tipe file berlaku umum untuk seluruh prodi di univ tsb).
 * - Kolom baru di DB Baru: deleted_at (diisi NULL).
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

    // ==========================================
    // 1. student_file_types (prasyarat)
    // ==========================================
    echo "1. Migrasi student_file_types...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`student_file_types`");
    $affected1 = $pdo->exec("
        INSERT INTO `$targetDb`.`student_file_types` (
            `id`, `name`, `is_additional`, `order`, `mime_type`,
            `min_file_count`, `max_file_count`, `created_at`, `updated_at`
        )
        SELECT
            `id`, `name`, `is_additional`, `order`, `mime_type`,
            `min_file_count`, `max_file_count`, `created_at`, `updated_at`
        FROM `$sourceDb`.`student_file_types`
    ");
    echo "   -> $affected1 baris.\n\n";

    // ==========================================
    // 2. student_files
    // ==========================================
    echo "2. Migrasi student_files...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`student_files`");
    $affected2 = $pdo->exec("
        INSERT INTO `$targetDb`.`student_files` (
            `id`, `student_id`, `filename`, `type`, `status`,
            `verified_note`, `verified_by`, `verified_at`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            `id`, `student_id`, `filename`, `type`, `status`,
            `verified_note`, `verified_by`, `verified_at`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`student_files`
    ");
    echo "   -> $affected2 baris.\n\n";

    // ==========================================
    // 3. file_type_tutorial
    // ==========================================
    echo "3. Migrasi file_type_tutorial...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`file_type_tutorial`");
    $affected3 = $pdo->exec("
        INSERT INTO `$targetDb`.`file_type_tutorial` (
            `id`, `file_type_id`, `content`, `created_at`, `updated_at`
        )
        SELECT
            `id`, `file_type_id`, `content`, `created_at`, `updated_at`
        FROM `$sourceDb`.`file_type_tutorial`
    ");
    echo "   -> $affected3 baris.\n\n";

    // ==========================================
    // 4. student_file_type_univ_program
    // ==========================================
    echo "4. Migrasi student_file_type_univ_program...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`student_file_type_univ_program`");
    $affected4 = $pdo->exec("
        INSERT INTO `$targetDb`.`student_file_type_univ_program` (
            `id`,
            `student_file_type_id`,
            `univ_id`,
            `program_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            sftup.`id`,
            sftup.`student_file_type_id`,
            sftup.`univ_id`,
            sftup.`program_id`,
            COALESCE(sftup.`created_at`, NOW()),
            COALESCE(sftup.`updated_at`, NOW()),
            NULL AS `deleted_at`
        FROM `$sourceDb`.`student_file_type_univ_program` sftup
        INNER JOIN `$targetDb`.`student_file_types` sft ON sftup.`student_file_type_id` = sft.`id`
        INNER JOIN `$targetDb`.`universities` u ON sftup.`univ_id` = u.`id`
    ");
    $totalSource4 = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`student_file_type_univ_program`")->fetchColumn();
    echo "   -> Total di sumber: $totalSource4 | Berhasil dimigrasi: $affected4 baris.\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== Migrasi File Dokumen Student Selesai ===\n";
    echo "student_file_types=$affected1, student_files=$affected2, file_type_tutorial=$affected3, student_file_type_univ_program=$affected4\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}