<?php

/**
 * migrate_student_files_group.php
 *
 * Migrasi grup tabel file dokumen student:
 *   1. student_file_types  (prasyarat, FK dari student_files & file_type_tutorial)
 *   2. student_files       (FK -> students, student_file_types)
 *   3. file_type_tutorial  (FK -> student_file_types)
 *
 * Struktur lama vs baru identik (cuma nambah deleted_at + FK resmi),
 * jadi migrasinya lurus, tidak ada transformasi kolom.
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
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`student_file_types`");
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
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`student_files`");
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
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`file_type_tutorial`");
    $affected3 = $pdo->exec("
        INSERT INTO `$targetDb`.`file_type_tutorial` (
            `id`, `file_type_id`, `content`, `created_at`, `updated_at`
        )
        SELECT
            `id`, `file_type_id`, `content`, `created_at`, `updated_at`
        FROM `$sourceDb`.`file_type_tutorial`
    ");
    echo "   -> $affected3 baris.\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== Migrasi student_file_types, student_files, file_type_tutorial Selesai ===\n";
    echo "student_file_types=$affected1, student_files=$affected2, file_type_tutorial=$affected3\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}