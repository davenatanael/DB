<?php

/**
 * migrate_enrollment_examinations.php
 *
 * Migrasi data enrollment_examinations
 * dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.enrollment_examinations
 *          (dengan fallback student_program_id dari outclassco_marketing.enrollment_examination_student_programs)
 * Target : db_ybaik_new.enrollment_examinations
 *
 * Perbandingan Schema:
 *   - Atribut Sama : id, student_program_id, type, status, notes, exam_date,
 *                    created_at, updated_at, deleted_at
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
    echo "    MEMULAI MIGRASI DATA ENROLLMENT_EXAMINATIONS                    \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`enrollment_examinations`");
    echo "-> Tabel `enrollment_examinations` di $targetDb berhasil dikosongkan.\n\n";

    // Ambil data dari database lama dengan LEFT JOIN ke pivot table lama untuk mengisi student_program_id yang kosong di exam 1..3
    $sourceQuery = "
        SELECT 
            ee.`id`,
            COALESCE(ee.`student_program_id`, eesp.`student_program_id`) AS student_program_id,
            ee.`type`,
            ee.`status`,
            ee.`notes`,
            ee.`exam_date`,
            ee.`created_at`,
            ee.`updated_at`,
            ee.`deleted_at`
        FROM `$sourceDb`.`enrollment_examinations` ee
        LEFT JOIN `$sourceDb`.`enrollment_examination_student_programs` eesp 
            ON ee.`id` = eesp.`enrollment_examination_id`
        ORDER BY ee.`id` ASC
    ";
    $sourceRows = $pdo->query($sourceQuery)->fetchAll();
    $totalSource = count($sourceRows);

    echo "-> Mengambil $totalSource baris data dari $sourceDb.enrollment_examinations...\n";

    $insertStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`enrollment_examinations` (
            `id`,
            `student_program_id`,
            `type`,
            `status`,
            `notes`,
            `exam_date`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        ) VALUES (
            :id,
            :student_program_id,
            :type,
            :status,
            :notes,
            :exam_date,
            :created_at,
            :updated_at,
            :deleted_at
        )
    ");

    $inserted = 0;

    foreach ($sourceRows as $row) {
        $studentProgramId = !empty($row['student_program_id']) ? (int)$row['student_program_id'] : null;
        $type = !empty($row['type']) ? trim($row['type']) : 'written_test';
        $status = !empty($row['status']) ? trim($row['status']) : 'published';
        
        $notes = $row['notes'];
        if ($notes === 'null' || $notes === '') {
            $notes = null;
        }

        $examDate = !empty($row['exam_date']) ? $row['exam_date'] : date('Y-m-d');
        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
        $updatedAt = !empty($row['updated_at']) ? $row['updated_at'] : date('Y-m-d H:i:s');
        
        $deletedAt = $row['deleted_at'];
        if ($deletedAt === '0000-00-00 00:00:00' || empty($deletedAt)) {
            $deletedAt = null;
        }

        $insertStmt->execute([
            ':id'                 => $row['id'],
            ':student_program_id' => $studentProgramId,
            ':type'               => $type,
            ':status'             => $status,
            ':notes'              => $notes,
            ':exam_date'          => $examDate,
            ':created_at'         => $createdAt,
            ':updated_at'         => $updatedAt,
            ':deleted_at'         => $deletedAt,
        ]);
        $inserted++;
    }

    $totalInTarget = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`enrollment_examinations`")->fetch()['c'];

    echo "\n=== HASIL MIGRASI ENROLLMENT_EXAMINATIONS ===\n";
    echo "Total data sumber               : $totalSource\n";
    echo "Total data berhasil dimasukkan  : $inserted\n";
    echo "Total data di target DB         : $totalInTarget\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI DATA ENROLLMENT_EXAMINATIONS SELESAI DENGAN SUKSES!     \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
