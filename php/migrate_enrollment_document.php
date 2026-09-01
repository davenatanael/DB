<?php

/**
 * migrate_enrollment_document.php
 *
 * Migrasi data student_enrollment_documents dan student_enrollment_document_programs
 * dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * TAHAP 1: student_enrollment_documents
 *   Sumber : outclassco_marketing.student_enrollment_documents
 *   Target : db_ybaik_new.student_enrollment_documents
 *   Perbandingan Schema:
 *     - Atribut Sama : id, university_id, filename, path, content, status,
 *                      verified_note, verified_by, verified_at, category,
 *                      created_at, updated_at, deleted_at
 *   Integritas: university_id divalidasi ke target universities.id.
 *
 * TAHAP 2: student_enrollment_document_programs
 *   Sumber : outclassco_marketing.student_enrollment_document_programs
 *   Target : db_ybaik_new.student_enrollment_document_programs
 *   Perbandingan Schema:
 *     - Atribut Sama : id, student_enrollment_document_id, student_program_id,
 *                      created_at, updated_at
 *     - Atribut Baru : deleted_at (soft delete, di-set NULL)
 *   Catatan: Seluruh baris dari database lama dimigrasikan ke database baru (tanpa di-skip).
 *
 * PRASYARAT: universities, enrollments, dan enrollment_programs HARUS sudah dimigrasikan.
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
    echo "    MEMULAI MIGRASI DATA STUDENT ENROLLMENT DOCUMENTS & PROGRAMS   \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target (urutan: child dulu baru parent)
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`student_enrollment_document_programs`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`student_enrollment_documents`");
    echo "-> Tabel `student_enrollment_document_programs` dan `student_enrollment_documents` berhasil dikosongkan.\n\n";

    // =========================================================================
    // TAHAP 1 : MIGRASI STUDENT_ENROLLMENT_DOCUMENTS
    // =========================================================================
    echo "1. Memigrasi tabel student_enrollment_documents...\n";
    $migrateSql1 = "
        INSERT INTO `$targetDb`.`student_enrollment_documents` (
            `id`, `university_id`, `filename`, `path`, `content`, `status`,
            `verified_note`, `verified_by`, `verified_at`, `category`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            sed.`id`,
            sed.`university_id`,
            sed.`filename`,
            sed.`path`,
            sed.`content`,
            sed.`status`,
            sed.`verified_note`,
            sed.`verified_by`,
            sed.`verified_at`,
            sed.`category`,
            sed.`created_at`,
            sed.`updated_at`,
            sed.`deleted_at`
        FROM `$sourceDb`.`student_enrollment_documents` sed
        INNER JOIN `$targetDb`.`universities` u ON sed.`university_id` = u.`id`
    ";
    $affected1 = $pdo->exec($migrateSql1);

    $totalSource1 = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_enrollment_documents`")->fetch()['c'];
    $skipped1     = $totalSource1 - $affected1;

    echo "   -> Total di sumber : $totalSource1\n";
    echo "   -> Berhasil dimigrasi : $affected1\n";
    echo "   -> Di-skip (university_id tidak ketemu di universities) : $skipped1\n";

    if ($skipped1 > 0) {
        echo "\n   Detail baris student_enrollment_documents yang di-skip:\n";
        $skipStmt1 = $pdo->query("
            SELECT sed.id, sed.university_id, sed.filename
            FROM `$sourceDb`.`student_enrollment_documents` sed
            LEFT JOIN `$targetDb`.`universities` u ON sed.university_id = u.id
            WHERE u.id IS NULL
        ");
        foreach ($skipStmt1->fetchAll() as $row) {
            echo "      - id={$row['id']} university_id={$row['university_id']} filename={$row['filename']}\n";
        }
    }
    echo "\n";

    // =========================================================================
    // TAHAP 2 : MIGRASI STUDENT_ENROLLMENT_DOCUMENT_PROGRAMS
    // =========================================================================
    echo "2. Memigrasi tabel student_enrollment_document_programs...\n";
    $migrateSql2 = "
        INSERT INTO `$targetDb`.`student_enrollment_document_programs` (
            `id`, `student_enrollment_document_id`, `student_program_id`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            sedp.`id`,
            sedp.`student_enrollment_document_id`,
            sedp.`student_program_id`,
            sedp.`created_at`,
            sedp.`updated_at`,
            NULL AS `deleted_at`
        FROM `$sourceDb`.`student_enrollment_document_programs` sedp
    ";
    $affected2 = $pdo->exec($migrateSql2);

    $totalSource2 = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_enrollment_document_programs`")->fetch()['c'];
    $skipped2     = $totalSource2 - $affected2;

    echo "   -> Total di sumber : $totalSource2\n";
    echo "   -> Berhasil dimigrasi : $affected2\n";
    echo "   -> Di-skip : $skipped2\n";

    if ($skipped2 > 0) {
        echo "\n   Detail baris student_enrollment_document_programs yang di-skip:\n";
        $skipStmt2 = $pdo->query("
            SELECT sedp.id, sedp.student_enrollment_document_id, sedp.student_program_id
            FROM `$sourceDb`.`student_enrollment_document_programs` sedp
            LEFT JOIN `$targetDb`.`student_enrollment_document_programs` target ON sedp.id = target.id
            WHERE target.id IS NULL
        ");
        foreach ($skipStmt2->fetchAll() as $row) {
            echo "      - id={$row['id']} doc_id={$row['student_enrollment_document_id']} student_program_id={$row['student_program_id']}\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI STUDENT ENROLLMENT DOCUMENTS & PROGRAMS SELESAI!       \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
