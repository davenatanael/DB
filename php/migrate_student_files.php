<?php
// migrate_student_files.php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    // Koneksi PDO dengan mendatabase-kan target langsung ke DSN untuk menghindari error 1046
    $pdo = new PDO("mysql:host=$host;dbname=$targetDb;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Nonaktifkan Foreign Key Checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi modul Student Files...\n\n";

    // Kosongkan tabel tujuan dengan urutan dari anak ke induk
    $pdo->exec("TRUNCATE TABLE `student_files`");
    $pdo->exec("TRUNCATE TABLE `file_type_tutorial`");
    $pdo->exec("TRUNCATE TABLE `student_file_types`");

    // ==========================================
    // 1. student_file_types (Master / Induk)
    // ==========================================
    echo "1. Migrasi student_file_types...\n";
    $migrateTypes = "
        INSERT INTO `student_file_types` (
            `id`,
            `name`,
            `is_additional`,
            `order`,
            `mime_type`,
            `min_file_count`,
            `max_file_count`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `name`,
            `is_additional`,
            `order`,
            `mime_type`,
            `min_file_count`,
            `max_file_count`,
            COALESCE(`created_at`, NOW()),
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`student_file_types`
    ";
    $affectedTypes = $pdo->exec($migrateTypes);

    $totalTypesStmt = $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_file_types`");
    $totalTypes = (int) $totalTypesStmt->fetch()['c'];
    $skippedTypes = $totalTypes - $affectedTypes;

    echo "   -> Total di sumber : $totalTypes\n";
    echo "   -> Berhasil dimigrasi : $affectedTypes\n";
    echo "   -> Di-skip : $skippedTypes\n\n";

    // ==========================================
    // 2. file_type_tutorial
    // ==========================================
    echo "2. Migrasi file_type_tutorial...\n";
    $migrateTutorial = "
        INSERT INTO `file_type_tutorial` (
            `id`,
            `file_type_id`,
            `content`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `file_type_id`,
            `content`,
            COALESCE(`created_at`, NOW()),
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`file_type_tutorial`
        WHERE `file_type_id` IS NOT NULL
    ";
    $affectedTutorial = $pdo->exec($migrateTutorial);

    $totalTutorialStmt = $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`file_type_tutorial`");
    $totalTutorial = (int) $totalTutorialStmt->fetch()['c'];
    $skippedTutorial = $totalTutorial - $affectedTutorial;

    echo "   -> Total di sumber : $totalTutorial\n";
    echo "   -> Berhasil dimigrasi : $affectedTutorial\n";
    echo "   -> Di-skip (file_type_id NULL) : $skippedTutorial\n\n";

    // ==========================================
    // 3. student_files
    // ==========================================
    echo "3. Migrasi student_files...\n";
    $migrateFiles = "
        INSERT INTO `student_files` (
            `id`,
            `student_id`,
            `filename`,
            `type`,
            `status`,
            `verified_note`,
            `verified_by`,
            `verified_at`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `student_id`,
            `filename`,
            `type`,
            `status`,
            `verified_note`,
            `verified_by`,
            `verified_at`,
            COALESCE(`created_at`, NOW()),
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`student_files`
        WHERE `student_id` IS NOT NULL
    ";
    $affectedFiles = $pdo->exec($migrateFiles);

    $totalFilesStmt = $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_files`");
    $totalFiles = (int) $totalFilesStmt->fetch()['c'];
    $skippedFiles = $totalFiles - $affectedFiles;

    echo "   -> Total di sumber : $totalFiles\n";
    echo "   -> Berhasil dimigrasi : $affectedFiles\n";
    echo "   -> Di-skip (student_id NULL) : $skippedFiles\n\n";

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== Migrasi modul Student Files selesai sepenuhnya ===\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}