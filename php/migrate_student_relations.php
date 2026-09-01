<?php

/**
 * migrate_student_relations.php
 *
 * Migrasi seluruh tabel relasi siswa:
 * 1. student_favorites (student_id -> students, program_id -> univ_programs)
 * 2. student_file_type_univ_program (student_file_type_id -> student_file_types, univ_id -> universities, program_id -> univ_programs)
 * 3. student_payment_discounts (student_payment_id -> payments)
 * 4. student_student_payment (student_id -> students, student_payment_id -> payments)
 *
 * Validasi ketat: Seluruh relasi student_id divalidasi ke target db_ybaik_new.students.
 * PRASYARAT: students, univ_programs, universities, student_file_types, dan payments HARUS sudah dimigrasikan.
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
    echo "       MEMULAI MIGRASI DATA RELASI STUDENTS                         \n";
    echo "====================================================================\n\n";

    // =========================================================================
    // TAHAP 1: student_favorites
    // =========================================================================
    echo "1. Memigrasi tabel student_favorites...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`student_favorites`");
    
    $sqlFav = "
        INSERT INTO `$targetDb`.`student_favorites` (
            `id`,
            `student_id`,
            `program_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            sf.`id`,
            sf.`student_id`,
            sf.`program_id`,
            sf.`created_at`,
            sf.`updated_at`,
            NULL
        FROM `$sourceDb`.`student_favorites` sf
        INNER JOIN `$targetDb`.`students` st ON sf.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`univ_programs` up ON sf.`program_id` = up.`id`
    ";
    $affFav = $pdo->exec($sqlFav);
    $totalFavOld = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`student_favorites`")->fetchColumn();
    echo "   -> Total sumber: $totalFavOld | Berhasil dimigrasikan: $affFav\n\n";

    // =========================================================================
    // TAHAP 2: student_file_type_univ_program
    // =========================================================================
    echo "2. Memigrasi tabel student_file_type_univ_program...\n";
    
    // Pastikan constraint dan kolom program_id disesuaikan
    try {
        $pdo->exec("ALTER TABLE `$targetDb`.`student_file_type_univ_program` DROP INDEX `unique_filetype_univ_program`");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `$targetDb`.`student_file_type_univ_program` MODIFY COLUMN `program_id` BIGINT(20) UNSIGNED NULL");
    } catch (Exception $e) {}

    $pdo->exec("DELETE FROM `$targetDb`.`student_file_type_univ_program`");

    $sqlSft = "
        INSERT INTO `$targetDb`.`student_file_type_univ_program` (
            `id`,
            `student_file_type_id`,
            `univ_id`,
            `program_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            sftup.`id`,
            sftup.`student_file_type_id`,
            sftup.`univ_id`,
            sftup.`program_id`,
            sftup.`created_at`,
            sftup.`updated_at`,
            NULL
        FROM `$sourceDb`.`student_file_type_univ_program` sftup
        INNER JOIN `$targetDb`.`student_file_types` sft ON sftup.`student_file_type_id` = sft.`id`
        INNER JOIN `$targetDb`.`universities` u ON sftup.`univ_id` = u.`id`
    ";
    $affSft = $pdo->exec($sqlSft);
    $totalSftOld = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`student_file_type_univ_program`")->fetchColumn();
    echo "   -> Total sumber: $totalSftOld | Berhasil dimigrasikan: $affSft\n\n";

    // =========================================================================
    // TAHAP 3: student_payment_discounts
    // =========================================================================
    echo "3. Memigrasi tabel student_payment_discounts...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`student_payment_discounts`");
    $hasDiscTable = $pdo->query("SHOW TABLES FROM `$sourceDb` LIKE 'student_payment_discounts'")->fetch();
    $affDisc = 0;
    if ($hasDiscTable) {
        $sqlDisc = "
            INSERT INTO `$targetDb`.`student_payment_discounts` (
                `id`,
                `title`,
                `description`,
                `nominal`,
                `student_payment_id`
            )
            SELECT 
                spd.`id`,
                spd.`title`,
                spd.`description`,
                spd.`nominal`,
                spd.`student_payment_id`
            FROM `$sourceDb`.`student_payment_discounts` spd
            INNER JOIN `$targetDb`.`payments` p ON spd.`student_payment_id` = p.`id`
        ";
        $affDisc = $pdo->exec($sqlDisc);
    }
    echo "   -> Berhasil dimigrasikan: $affDisc data (Tabel sumber " . ($hasDiscTable ? "ditemukan" : "tidak ada di DB lama") . ")\n\n";

    // =========================================================================
    // TAHAP 4: student_student_payment
    // =========================================================================
    echo "4. Memigrasi tabel student_student_payment...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`student_student_payment`");
    
    $sqlSsp = "
        INSERT INTO `$targetDb`.`student_student_payment` (
            `id`,
            `student_id`,
            `student_payment_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            ssp.`id`,
            ssp.`student_id`,
            ssp.`student_payment_id`,
            NOW(),
            NOW(),
            NULL
        FROM `$sourceDb`.`student_student_payment` ssp
        INNER JOIN `$targetDb`.`students` st ON ssp.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`payments` p ON ssp.`student_payment_id` = p.`id`
    ";
    $affSsp = $pdo->exec($sqlSsp);
    $totalSspOld = (int) $pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`student_student_payment`")->fetchColumn();
    echo "   -> Total sumber: $totalSspOld | Berhasil dimigrasikan: $affSsp\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "====================================================================\n";
    echo "               RINGKASAN HASIL MIGRASI                              \n";
    echo "====================================================================\n";
    echo " - student_favorites            : $affFav / $totalFavOld baris\n";
    echo " - student_file_type_univ_program: $affSft / $totalSftOld baris\n";
    echo " - student_payment_discounts    : $affDisc baris\n";
    echo " - student_student_payment      : $affSsp / $totalSspOld baris\n";
    echo "====================================================================\n";
    echo "    MIGRASI DATA RELASI STUDENTS SELESAI DENGAN SUKSES!             \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
