<?php

/**
 * migrate_employees.php
 *
 * Migrasi data modul karyawan dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new):
 * 1. employees
 *    Rename: updated_at -> update_at
 * 2. employee_kinerjas
 *    Rename: employee_id -> employees_id
 * 3. employee_warnings
 *    Rename: employee_id -> employees_id
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
    echo "       MEMULAI MIGRASI DATA EMPLOYEES                               \n";
    echo "====================================================================\n\n";

    // =========================================================================
    // TAHAP 1: employees
    // =========================================================================
    echo "1. Memigrasi tabel employees...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`employees`");

    $sqlEmp = "
        INSERT INTO `$targetDb`.`employees` (
            `id`, `id_number`, `employee_id_number`, `first_name`, `last_name`,
            `gender`, `place_of_birth`, `date_of_birth`, `main_address`, `alternate_address`,
            `email`, `corporate_email`, `phone_number`, `corporate_phone_number`,
            `marriage_status`, `total_child`, `start_work_date`, `position`, `work_status`,
            `photo`, `id_card_photo`, `resign_at`, `resign_reason`, `division_id`,
            `emergency_contact_name`, `emergency_contact_address`, `emergency_contact_phone`, `emergency_contact_relation`,
            `status`, `created_at`, `update_at`, `deleted_at`
        )
        SELECT 
            `id`, `id_number`, `employee_id_number`, `first_name`, `last_name`,
            `gender`, `place_of_birth`, `date_of_birth`, `main_address`, `alternate_address`,
            `email`, `corporate_email`, `phone_number`, `corporate_phone_number`,
            `marriage_status`, `total_child`, `start_work_date`, `position`, `work_status`,
            `photo`, `id_card_photo`, `resign_at`, `resign_reason`, `division_id`,
            `emergency_contact_name`, `emergency_contact_address`, `emergency_contact_phone`, `emergency_contact_relation`,
            `status`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`employees`
    ";
    $affEmp = $pdo->exec($sqlEmp);
    $totalEmpOld = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`employees`")->fetchColumn();
    echo "   -> Total sumber: $totalEmpOld | Berhasil dimigrasikan: $affEmp\n\n";

    // =========================================================================
    // TAHAP 2: employee_kinerjas
    // =========================================================================
    echo "2. Memigrasi tabel employee_kinerjas...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`employee_kinerjas`");

    $sqlKin = "
        INSERT INTO `$targetDb`.`employee_kinerjas` (
            `id`, `employees_id`, `periode`, `nominal_tabungan`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `employee_id`, `periode`, `nominal_tabungan`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`employee_kinerjas`
    ";
    $affKin = $pdo->exec($sqlKin);
    $totalKinOld = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`employee_kinerjas`")->fetchColumn();
    echo "   -> Total sumber: $totalKinOld | Berhasil dimigrasikan: $affKin\n\n";

    // =========================================================================
    // TAHAP 3: employee_warnings
    // =========================================================================
    echo "3. Memigrasi tabel employee_warnings...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`employee_warnings`");

    $sqlWarn = "
        INSERT INTO `$targetDb`.`employee_warnings` (
            `id`, `employees_id`, `level`, `year`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `employee_id`, `level`, `year`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`employee_warnings`
    ";
    $affWarn = $pdo->exec($sqlWarn);
    $totalWarnOld = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`employee_warnings`")->fetchColumn();
    echo "   -> Total sumber: $totalWarnOld | Berhasil dimigrasikan: $affWarn\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "====================================================================\n";
    echo "               RINGKASAN HASIL MIGRASI EMPLOYEES                    \n";
    echo "====================================================================\n";
    echo " - employees        : $affEmp / $totalEmpOld baris\n";
    echo " - employee_kinerjas: $affKin / $totalKinOld baris\n";
    echo " - employee_warnings: $affWarn / $totalWarnOld baris\n";
    echo "====================================================================\n";
    echo "    MIGRASI DATA EMPLOYEES SELESAI DENGAN SUKSES!                   \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
