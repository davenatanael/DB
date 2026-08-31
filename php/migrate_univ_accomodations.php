<?php

/**
 * migrate_univ_accomodations.php
 * 
 * Skrip migrasi data akomodasi universitas (Univ Accomodations) dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 * 
 * PRASYARAT:
 * - Tabel `universities` harus sudah dimigrasikan terlebih dahulu (FK ke universities).
 * 
 * TABEL YANG DIMIGRASIKAN:
 * 1. `db_ybaik_new`.`univ_accomodations` (Master Akomodasi/Asrama Universitas)
 * 2. `db_ybaik_new`.`univ_accomodation_details` (Detail Tipe Kamar, Harga, Foto, Catatan)
 * 3. `db_ybaik_new`.`univ_accomodation_photos` (Foto Galeri Akomodasi Universitas)
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
    echo "       MEMULAI MIGRASI DATA UNIV ACCOMODATIONS & RELASINYA          \n";
    echo "====================================================================\n\n";

    // 1. Kosongkan tabel target (TRUNCATE)
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_accomodation_photos`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_accomodation_details`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_accomodations`");
    echo "-> Tabel `univ_accomodations`, `univ_accomodation_details`, dan `univ_accomodation_photos` berhasil dikosongkan.\n\n";

    // =========================================================================
    // TAHAP 1: MIGRASI MASTER UNIV ACCOMODATIONS
    // =========================================================================
    echo "1. Memigrasi tabel master univ_accomodations...\n";
    $sqlAcc = "
        INSERT INTO `$targetDb`.`univ_accomodations` (
            `id`,
            `univ_id`,
            `name`,
            `description`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            acc.`id`,
            acc.`univ_id`,
            acc.`name`,
            acc.`description`,
            acc.`created_at`,
            acc.`updated_at`,
            acc.`deleted_at`
        FROM `$sourceDb`.`univ_accomodations` acc
        INNER JOIN `$targetDb`.`universities` u ON u.`id` = acc.`univ_id`
    ";
    $affectedAcc = $pdo->exec($sqlAcc);
    $totalSourceAcc = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`univ_accomodations`")->fetch()['c'];
    echo "   -> Sukses: $affectedAcc / $totalSourceAcc data univ_accomodations berhasil dimasukkan.\n\n";

    // =========================================================================
    // TAHAP 2: MIGRASI UNIV ACCOMODATION DETAILS
    // =========================================================================
    echo "2. Memigrasi tabel univ_accomodation_details...\n";
    $sqlDetails = "
        INSERT INTO `$targetDb`.`univ_accomodation_details` (
            `id`,
            `univ_accomodations_id`,
            `room_type`,
            `currency`,
            `room_price`,
            `price_note`,
            `photo`,
            `notes`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            dt.`id`,
            dt.`univ_accomodation_id` AS `univ_accomodations_id`,
            dt.`room_type`,
            dt.`currency`,
            dt.`room_price`,
            dt.`price_note`,
            dt.`photo`,
            dt.`notes`,
            dt.`created_at`,
            dt.`updated_at`,
            dt.`deleted_at`
        FROM `$sourceDb`.`univ_accomodation_details` dt
        INNER JOIN `$targetDb`.`univ_accomodations` acc ON acc.`id` = dt.`univ_accomodation_id`
    ";
    $affectedDetails = $pdo->exec($sqlDetails);
    $totalSourceDetails = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`univ_accomodation_details`")->fetch()['c'];
    echo "   -> Sukses: $affectedDetails / $totalSourceDetails data univ_accomodation_details berhasil dimasukkan.\n\n";

    // =========================================================================
    // TAHAP 3: MIGRASI UNIV ACCOMODATION PHOTOS
    // =========================================================================
    echo "3. Memigrasi tabel univ_accomodation_photos...\n";
    // Menghubungkan foto ke univ_accomodations_id berdasarkan univ_id
    $sqlPhotos = "
        INSERT INTO `$targetDb`.`univ_accomodation_photos` (
            `id`,
            `univ_id`,
            `univ_accomodations_id`,
            `name`,
            `photo`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            p.`id`,
            p.`univ_id`,
            COALESCE(
                (SELECT acc.`id` FROM `$targetDb`.`univ_accomodations` acc WHERE acc.`univ_id` = p.`univ_id` ORDER BY acc.`id` ASC LIMIT 1),
                1
            ) AS `univ_accomodations_id`,
            p.`name`,
            p.`photo`,
            p.`created_at`,
            p.`updated_at`,
            p.`deleted_at`
        FROM `$sourceDb`.`univ_accomodation_photos` p
        INNER JOIN `$targetDb`.`universities` u ON u.`id` = p.`univ_id`
    ";
    $affectedPhotos = $pdo->exec($sqlPhotos);
    $totalSourcePhotos = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`univ_accomodation_photos`")->fetch()['c'];
    echo "   -> Sukses: $affectedPhotos / $totalSourcePhotos data univ_accomodation_photos berhasil dimasukkan.\n\n";

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // =========================================================================
    // SAMPEL DATA HASIL MIGRASI
    // =========================================================================
    echo "4. Sampel Hasil Migrasi Accomodations (5 Data Teratas):\n";
    printf(
        "%-5s | %-8s | %-32s | %-40s\n",
        "ID", "UNIV ID", "NAMA AKOMODASI", "DESKRIPSI"
    );
    echo str_repeat("-", 92) . "\n";
    $samples = $pdo->query("
        SELECT id, univ_id, name, description 
        FROM `$targetDb`.`univ_accomodations` 
        LIMIT 5
    ")->fetchAll();
    foreach ($samples as $s) {
        printf(
            "%-5d | %-8d | %-32s | %-40s\n",
            $s['id'],
            $s['univ_id'],
            mb_strimwidth($s['name'], 0, 32, '..'),
            mb_strimwidth(strip_tags($s['description'] ?? '-'), 0, 40, '..')
        );
    }
    echo str_repeat("=", 92) . "\n\n";

    echo "====================================================================\n";
    echo "            MIGRASI UNIV ACCOMODATIONS SELESAI                      \n";
    echo "====================================================================\n";
    echo " - Total Master Accomodations  : $affectedAcc data\n";
    echo " - Total Accomodation Details  : $affectedDetails data\n";
    echo " - Total Accomodation Photos   : $affectedPhotos data\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
}
