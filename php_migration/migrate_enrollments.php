<?php

/**
 * migrate_enrollments.php
 *
 * Migrasi seluruh data enrollment dan relasi-relasinya:
 *
 * TAHAP 1: enrollments
 *   Sumber : outclassco_marketing.student_programs
 *   Target : db_ybaik_new.enrollments
 *   Rename : student_id -> students_id
 *
 * TAHAP 2: enrollment_programs
 *   Sumber : outclassco_marketing.student_program_details
 *   Target : db_ybaik_new.enrollment_programs
 *   Rename : student_program_id -> student_program_id (sama, FK ke enrollments)
 *            program_id          -> program_id (FK ke univ_programs, ID sama)
 *   Catatan: 53 baris di sumber memiliki student_program_id NULL -> di-skip.
 *
 * PRASYARAT: students, universities, dan univ_programs HARUS sudah dimigrasikan.
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
    echo "        MEMULAI MIGRASI DATA ENROLLMENTS DAN RELASINYA              \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target (urutan penting: child dulu sebelum parent)
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`enrollment_programs`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`enrollments`");
    echo "-> Tabel `enrollments` dan `enrollment_programs` berhasil dikosongkan.\n\n";

    // =========================================================================
    // TAHAP 1 : MIGRASI ENROLLMENTS (student_programs -> enrollments)
    // =========================================================================
    echo "1. Memigrasi tabel enrollments (sumber: student_programs)...\n";
    $affected1 = $pdo->exec("
        INSERT INTO `$targetDb`.`enrollments` (
            `id`, `students_id`, `email`, `password`, `registration_id`, `university_id`,
            `priorities_order`, `status`, `uni_status`, `locked_at`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            sp.`id`,
            sp.`student_id`,
            sp.`email`,
            sp.`password`,
            sp.`registration_id`,
            sp.`university_id`,
            sp.`priorities_order`,
            sp.`status`,
            sp.`uni_status`,
            sp.`locked_at`,
            sp.`created_at`,
            sp.`updated_at`,
            sp.`deleted_at`
        FROM `$sourceDb`.`student_programs` sp
        INNER JOIN `$targetDb`.`students` st ON sp.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`universities` u ON sp.`university_id` = u.`id`
    ");

    $total1 = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_programs`")->fetch()['c'];
    $skip1  = $total1 - $affected1;

    echo "   -> Total sumber        : $total1\n";
    echo "   -> Berhasil dimigrasi  : $affected1\n";
    echo "   -> Di-skip (student/univ_id tidak valid) : $skip1\n";

    if ($skip1 > 0) {
        $rows = $pdo->query("
            SELECT sp.id, sp.student_id, sp.university_id
            FROM `$sourceDb`.`student_programs` sp
            LEFT JOIN `$targetDb`.`students` st ON sp.student_id = st.id
            LEFT JOIN `$targetDb`.`universities` u ON sp.university_id = u.id
            WHERE st.id IS NULL OR u.id IS NULL
        ")->fetchAll();
        foreach ($rows as $r) {
            echo "   - id={$r['id']} student_id={$r['student_id']} university_id={$r['university_id']}\n";
        }
    }
    echo "\n";

    // =========================================================================
    // TAHAP 2 : MIGRASI ENROLLMENT_PROGRAMS (student_program_details -> enrollment_programs)
    // =========================================================================
    echo "2. Memigrasi tabel enrollment_programs (sumber: student_program_details)...\n";
    
    // Pastikan kolom student_program_id dapat bernilai NULL
    $pdo->exec("ALTER TABLE `$targetDb`.`enrollment_programs` MODIFY COLUMN `student_program_id` BIGINT(20) UNSIGNED NULL");

    $affected2 = $pdo->exec("
        INSERT INTO `$targetDb`.`enrollment_programs` (
            `id`,
            `student_program_id`,
            `program_id`,
            `interest`,
            `status`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            spd.`id`,
            spd.`student_program_id`,
            spd.`program_id`,
            spd.`interest`,
            spd.`status`,
            spd.`created_at`,
            spd.`updated_at`,
            spd.`deleted_at`
        FROM `$sourceDb`.`student_program_details` spd
    ");

    $total2 = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_program_details`")->fetch()['c'];
    $nullSP = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`enrollment_programs` WHERE student_program_id IS NULL")->fetch()['c'];

    echo "   -> Total sumber                : $total2\n";
    echo "   -> Berhasil dimigrasi          : $affected2\n";
    echo "   -> Data dengan student_program_id NULL: $nullSP\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // =========================================================================
    // SAMPEL HASIL MIGRASI
    // =========================================================================
    echo "3. Sampel Hasil Migrasi enrollments (5 Data Teratas):\n";
    printf("%-5s | %-10s | %-8s | %-22s | %-30s | %-12s\n",
        "ID", "STUDENT_ID", "UNIV_ID", "STATUS", "REGISTRATION_ID", "LOCKED_AT");
    echo str_repeat("-", 95) . "\n";
    $samples = $pdo->query("
        SELECT id, students_id, university_id, status, registration_id, locked_at
        FROM `$targetDb`.`enrollments` LIMIT 5
    ")->fetchAll();
    foreach ($samples as $s) {
        printf("%-5d | %-10d | %-8d | %-22s | %-30s | %-12s\n",
            $s['id'], $s['students_id'], $s['university_id'],
            mb_strimwidth($s['status'] ?? '-', 0, 22, '..'),
            mb_strimwidth($s['registration_id'] ?? '-', 0, 30, '..'),
            $s['locked_at'] ?? '-'
        );
    }

    echo "\n4. Sampel Hasil Migrasi enrollment_programs (5 Data Teratas):\n";
    printf("%-5s | %-18s | %-10s | %-10s | %-15s\n",
        "ID", "STUDENT_PROGRAM_ID", "PROGRAM_ID", "INTEREST", "STATUS");
    echo str_repeat("-", 65) . "\n";
    $samples2 = $pdo->query("
        SELECT id, student_program_id, program_id, interest, status
        FROM `$targetDb`.`enrollment_programs` LIMIT 5
    ")->fetchAll();
    foreach ($samples2 as $s) {
        printf("%-5d | %-18d | %-10d | %-10s | %-15s\n",
            $s['id'], $s['student_program_id'], $s['program_id'],
            $s['interest'] ?? '-', mb_strimwidth($s['status'] ?? '-', 0, 15, '..')
        );
    }
    echo str_repeat("=", 95) . "\n\n";

    echo "====================================================================\n";
    echo "               MIGRASI ENROLLMENTS SELESAI                          \n";
    echo "====================================================================\n";
    echo " - Total enrollments dimigrasikan       : $affected1 / $total1\n";
    echo " - Total enrollment_programs dimigrasikan: $affected2 / $total2\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
}