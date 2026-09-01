<?php

/**
 * migrate_enrollment_timeline.php
 *
 * Migrasi data enrollment_timelines dan enrollment_timeline_media dari
 * database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * TAHAP 1: enrollment_timelines
 *   Sumber : outclassco_marketing.enrollment_timelines
 *   Target : db_ybaik_new.enrollment_timelines
 *   Perbandingan Schema:
 *     - Atribut Sama : id, student_program_id, type, is_public, title, content,
 *                      created_by, reminder_sent_at, created_at, updated_at
 *     - Atribut Baru : deleted_at (soft delete, di-set NULL)
 *   Integritas: student_program_id (nullable) divalidasi ke target enrollments.id.
 *
 * TAHAP 2: enrollment_timeline_media
 *   Sumber : outclassco_marketing.enrollment_timeline_media
 *   Target : db_ybaik_new.enrollment_timeline_media
 *   Perbandingan Schema:
 *     - Atribut Sama : id, enrollment_timeline_id, file_path, file_type, caption,
 *                      created_at, updated_at
 *     - Atribut Baru : deleted_at (soft delete, di-set NULL)
 *   Integritas: enrollment_timeline_id divalidasi ke target enrollment_timelines.id
 *               (yang berhasil dimigrasi di TAHAP 1).
 *
 * PRASYARAT: enrollments HARUS sudah dimigrasikan duluan.
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
    echo "    MEMULAI MIGRASI DATA ENROLLMENT TIMELINES DAN MEDIA\n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target (urutan: child dulu baru parent)
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`enrollment_timeline_media`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`enrollment_timelines`");
    echo "-> Tabel `enrollment_timeline_media` dan `enrollment_timelines` berhasil dikosongkan.\n\n";

    // =========================================================================
    // TAHAP 1 : MIGRASI ENROLLMENT_TIMELINES
    // =========================================================================
    echo "1. Memigrasi tabel enrollment_timelines...\n";
    $migrateSql1 = "
        INSERT INTO `$targetDb`.`enrollment_timelines` (
            `id`, `student_program_id`, `type`, `is_public`, `title`, `content`,
            `created_by`, `reminder_sent_at`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            et.`id`,
            et.`student_program_id`,
            et.`type`,
            et.`is_public`,
            et.`title`,
            et.`content`,
            et.`created_by`,
            et.`reminder_sent_at`,
            et.`created_at`,
            et.`updated_at`,
            NULL AS `deleted_at`
        FROM `$sourceDb`.`enrollment_timelines` et
        LEFT JOIN `$targetDb`.`enrollments` e ON et.`student_program_id` = e.`id`
        WHERE et.`student_program_id` IS NULL OR e.`id` IS NOT NULL
    ";
    $affected1 = $pdo->exec($migrateSql1);

    $totalSource1 = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`enrollment_timelines`")->fetch()['c'];
    $skipped1     = $totalSource1 - $affected1;

    echo "   -> Total di sumber : $totalSource1\n";
    echo "   -> Berhasil dimigrasi : $affected1\n";
    echo "   -> Di-skip (student_program_id tidak ketemu di enrollments) : $skipped1\n";

    if ($skipped1 > 0) {
        echo "\n   Detail baris enrollment_timelines yang di-skip:\n";
        $skipStmt1 = $pdo->query("
            SELECT et.id, et.student_program_id
            FROM `$sourceDb`.`enrollment_timelines` et
            LEFT JOIN `$targetDb`.`enrollments` e ON et.student_program_id = e.id
            WHERE et.student_program_id IS NOT NULL AND e.id IS NULL
        ");
        foreach ($skipStmt1->fetchAll() as $row) {
            echo "      - id={$row['id']} student_program_id={$row['student_program_id']}\n";
        }
    }
    echo "\n";

    // =========================================================================
    // TAHAP 2 : MIGRASI ENROLLMENT_TIMELINE_MEDIA
    // =========================================================================
    echo "2. Memigrasi tabel enrollment_timeline_media...\n";
    $migrateSql2 = "
        INSERT INTO `$targetDb`.`enrollment_timeline_media` (
            `id`, `enrollment_timeline_id`, `file_path`, `file_type`, `caption`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            etm.`id`,
            etm.`enrollment_timeline_id`,
            etm.`file_path`,
            etm.`file_type`,
            etm.`caption`,
            etm.`created_at`,
            etm.`updated_at`,
            NULL AS `deleted_at`
        FROM `$sourceDb`.`enrollment_timeline_media` etm
        INNER JOIN `$targetDb`.`enrollment_timelines` et ON etm.`enrollment_timeline_id` = et.`id`
    ";
    $affected2 = $pdo->exec($migrateSql2);

    $totalSource2 = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`enrollment_timeline_media`")->fetch()['c'];
    $skipped2     = $totalSource2 - $affected2;

    echo "   -> Total di sumber : $totalSource2\n";
    echo "   -> Berhasil dimigrasi : $affected2\n";
    echo "   -> Di-skip (enrollment_timeline_id tidak ketemu di enrollment_timelines) : $skipped2\n";

    if ($skipped2 > 0) {
        echo "\n   Detail baris enrollment_timeline_media yang di-skip:\n";
        $skipStmt2 = $pdo->query("
            SELECT etm.id, etm.enrollment_timeline_id
            FROM `$sourceDb`.`enrollment_timeline_media` etm
            LEFT JOIN `$targetDb`.`enrollment_timelines` et ON etm.enrollment_timeline_id = et.id
            WHERE et.id IS NULL
        ");
        foreach ($skipStmt2->fetchAll() as $row) {
            echo "      - id={$row['id']} enrollment_timeline_id={$row['enrollment_timeline_id']}\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI ENROLLMENT TIMELINES DAN MEDIA SELESAI!\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
