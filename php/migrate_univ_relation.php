<?php

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

    // Nonaktifkan Foreign Key Checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // ==========================================
    // 1. MIGRASI DATA univ_has_categories
    // ==========================================
    echo "Memulai migrasi tabel univ_has_categories...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_has_categories`");

    $migrateHasCategoriesSql = "
        INSERT INTO `$targetDb`.`univ_has_categories` (
            `id`,
            `univ_id`,
            `category_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `univ_id`,
            `category_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`univ_has_categories`
    ";
    $affectedHasCategories = $pdo->exec($migrateHasCategoriesSql);
    echo "-> Sukses: $affectedHasCategories data pada tabel univ_has_categories berhasil dimigrasi.\n\n";

    // ==========================================
    // 2. MIGRASI DATA univ_programs
    // ==========================================
    echo "Memulai migrasi tabel univ_programs...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_programs`");

    $migrateProgramsSql = "
        INSERT INTO `$targetDb`.`univ_programs` (
            `id`,
            `univ_id`,
            `course_name`,
            `admission_type`,
            `duration`,
            `c_duration`,
            `starting_date`,
            `application_deadline`,
            `starting_date2`,
            `application_deadline2`,
            `teaching_language`,
            `currency`,
            `c_tuition_fee`,
            `tuition_fee`,
            `c_application_fee`,
            `application_fee`,
            `c_service_fee`,
            `service_fee`,
            `program_description`,
            `entry_requirement`,
            `fee_structure`,
            `status`,
            `is_featured`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `univ_id`,
            `course_name`,
            `admission_type`,
            `duration`,
            `c_duration`,
            `starting_date`,
            `application_deadline`,
            `starting_date2`,
            `application_deadline2`,
            `teaching_language`,
            `currency`,
            `c_tuition_fee`,
            `tuition_fee`,
            `c_application_fee`,
            `application_fee`,
            `c_service_fee`,
            `service_fee`,
            `program_description`,
            `entry_requirement`,
            `fee_structure`,
            `status`,
            `is_featured`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`univ_programs`
    ";
    $affectedPrograms = $pdo->exec($migrateProgramsSql);
    echo "-> Sukses: $affectedPrograms data pada tabel univ_programs berhasil dimigrasi.\n\n";

    // ==========================================
    // 3. MIGRASI DATA univ_fee_structures
    // ==========================================
    echo "Memulai migrasi tabel univ_fee_structures...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_fee_structures`");

    $migrateFeeStructuresSql = "
        INSERT INTO `$targetDb`.`univ_fee_structures` (
            `id`,
            `univ_id`,
            `fee_type`,
            `fee_name`,
            `fee_value`,
            `currency`,
            `nominal`,
            `sequence`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `univ_id`,
            `fee_type`,
            `fee_name`,
            `fee_value`,
            `currency`,
            `nominal`,
            `sequence`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`univ_fee_structures`
    ";
    $affectedFeeStructures = $pdo->exec($migrateFeeStructuresSql);
    echo "-> Sukses: $affectedFeeStructures data pada tabel univ_fee_structures berhasil dimigrasi.\n\n";

    // ==========================================
    // 4. MIGRASI DATA univ_entry_requirements
    // ==========================================
    echo "Memulai migrasi tabel univ_entry_requirements...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_entry_requirements`");

    $migrateEntryRequirementsSql = "
        INSERT INTO `$targetDb`.`univ_entry_requirements` (
            `id`,
            `univ_id`,
            `admission_type`,
            `description`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `univ_id`,
            `admission_type`,
            `description`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`univ_entry_requirements`
    ";
    $affectedEntryRequirements = $pdo->exec($migrateEntryRequirementsSql);
    echo "-> Sukses: $affectedEntryRequirements data pada tabel univ_entry_requirements berhasil dimigrasi.\n\n";

    // ==========================================
    // 5. MIGRASI DATA univ_scholarships
    // ==========================================
    echo "Memulai migrasi tabel univ_scholarships...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_scholarships`");

    $migrateScholarshipsSql = "
        INSERT INTO `$targetDb`.`univ_scholarships` (
            `id`,
            `univ_id`,
            `admission_type`,
            `language`,
            `category`,
            `tuition_fee`,
            `accomodation_fee`,
            `insurance_fee`,
            `stipend_monthly`,
            `stipend_yearly`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `univ_id`,
            `admission_type`,
            `language`,
            `category`,
            `tuition_fee`,
            `accomodation_fee`,
            `insurance_fee`,
            `stipend_monthly`,
            `stipend_yearly`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`univ_scholarships`
    ";
    $affectedScholarships = $pdo->exec($migrateScholarshipsSql);
    echo "-> Sukses: $affectedScholarships data pada tabel univ_scholarships berhasil dimigrasi (kolom univ_program_id diabaikan).\n\n";

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== Migrasi 5 Tabel Relasi Universitas Selesai ===\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
