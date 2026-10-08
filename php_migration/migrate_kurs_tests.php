<?php
/**
 * migrate_kurs_tests.php
 *
 * Migrasi data dari outclassco_marketing ke db_ybaik_new:
 * 1. kurs -> kurs
 * 2. tests -> tests
 * 3. student_tests -> student_tests
 *
 * PRASYARAT: tabel students sudah dimigrasikan.
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
    echo "       MEMULAI MIGRASI DATA KURS, TESTS & STUDENT_TESTS             \n";
    echo "====================================================================\n\n";

    // -------------------------------------------------------------
    // 1. Migrasi Tabel kurs
    // -------------------------------------------------------------
    echo "1. Memigrasi tabel kurs...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`kurs`");

    $sqlKurs = "
        INSERT INTO `$targetDb`.`kurs` (
            `id`,
            `nama_kurs_awal`,
            `nama_kurs_akhir`,
            `nominal`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `nama_kurs_awal`,
            `nama_kurs_akhir`,
            `nominal`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`kurs`
    ";
    $affectedKurs = $pdo->exec($sqlKurs);
    $totalSourceKurs = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`kurs`")->fetchColumn();
    echo "   -> Total sumber : $totalSourceKurs | Berhasil dimigrasi: $affectedKurs baris\n\n";

    // -------------------------------------------------------------
    // 2. Migrasi Tabel tests
    // -------------------------------------------------------------
    echo "2. Memigrasi tabel tests...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`tests`");

    $sqlTests = "
        INSERT INTO `$targetDb`.`tests` (
            `id`,
            `name`,
            `score`,
            `link`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `name`,
            `score`,
            `link`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`tests`
    ";
    $affectedTests = $pdo->exec($sqlTests);
    $totalSourceTests = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`tests`")->fetchColumn();
    echo "   -> Total sumber : $totalSourceTests | Berhasil dimigrasi: $affectedTests baris\n\n";

    // -------------------------------------------------------------
    // 3. Migrasi Tabel student_tests
    // -------------------------------------------------------------
    echo "3. Memigrasi tabel student_tests...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`student_tests`");

    $sqlStudentTests = "
        INSERT INTO `$targetDb`.`student_tests` (
            `id`,
            `student_id`,
            `test_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            st.`id`,
            st.`student_id`,
            st.`test_id`,
            st.`created_at`,
            st.`updated_at`,
            st.`deleted_at`
        FROM `$sourceDb`.`student_tests` st
        INNER JOIN `$targetDb`.`students` s ON s.`id` = st.`student_id`
        INNER JOIN `$targetDb`.`tests` t ON t.`id` = st.`test_id`
    ";
    $affectedStudentTests = $pdo->exec($sqlStudentTests);
    $totalSourceStudentTests = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`student_tests`")->fetchColumn();
    echo "   -> Total sumber : $totalSourceStudentTests | Berhasil dimigrasi: $affectedStudentTests baris\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "====================================================================\n";
    echo "Migrasi kurs, tests, dan student_tests SELESAI DENGAN SUKSES!\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
