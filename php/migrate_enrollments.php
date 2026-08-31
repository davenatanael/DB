<?php

/**
 * migrate_enrollments.php
 *
 * Migrasi student_programs (lama) -> enrollments (baru).
 *
 * PRASYARAT: students dan universities HARUS sudah dimigrasikan duluan
 * (enrollments FK ke keduanya).
 *
 * Struktur identik, cuma rename kolom:
 *   student_id -> students_id
 * Sudah divalidasi ke data asli: 1.028 baris, semua student_id & university_id
 * valid (0 yatim), jadi tidak perlu filter WHERE tambahan -- tapi tetap
 * dipasang jaring pengaman (INNER JOIN) untuk jaga-jaga kalau datanya berubah.
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

    echo "Memulai migrasi student_programs -> enrollments...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`enrollments`");

    $migrateSql = "
        INSERT INTO `$targetDb`.`enrollments` (
            `id`, `students_id`, `email`, `password`, `registration_id`, `university_id`,
            `priorities_order`, `status`, `uni_status`, `locked_at`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            sp.`id`, sp.`student_id`, sp.`email`, sp.`password`, sp.`registration_id`, sp.`university_id`,
            sp.`priorities_order`, sp.`status`, sp.`uni_status`, sp.`locked_at`,
            sp.`created_at`, sp.`updated_at`, sp.`deleted_at`
        FROM `$sourceDb`.`student_programs` sp
        INNER JOIN `$targetDb`.`students` st ON sp.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`universities` u ON sp.`university_id` = u.`id`
    ";
    $affected = $pdo->exec($migrateSql);

    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_programs`")->fetch()['c'];
    $skipped = $totalSource - $affected;

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "-> Total di sumber : $totalSource\n";
    echo "-> Berhasil dimigrasi : $affected\n";
    echo "-> Di-skip (student_id/university_id tidak valid) : $skipped\n";

    if ($skipped > 0) {
        echo "\nDetail baris yang di-skip:\n";
        $skipStmt = $pdo->query("
            SELECT sp.id, sp.student_id, sp.university_id
            FROM `$sourceDb`.`student_programs` sp
            LEFT JOIN `$targetDb`.`students` st ON sp.student_id = st.id
            LEFT JOIN `$targetDb`.`universities` u ON sp.university_id = u.id
            WHERE st.id IS NULL OR u.id IS NULL
        ");
        foreach ($skipStmt->fetchAll() as $row) {
            $reason = [];
            if (!$row['student_id']) $reason[] = 'student_id NULL';
            $reason[] = 'lihat student_id/university_id di atas';
            echo "   - id={$row['id']} student_id={$row['student_id']} university_id={$row['university_id']}\n";
        }
    }

    echo "\nMigrasi enrollments selesai.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}