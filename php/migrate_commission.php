<?php
// migrate_commissions.php

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

    echo "Memulai migrasi tabel commissions dan commission_details...\n";
    
    // Kosongkan tabel tujuan dengan urutan dari anak (details) ke induk (commissions)
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`commission_details`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`commissions`");

    // 1. Migrasi tabel commissions
    $migrateCommissions = "
        INSERT INTO `$targetDb`.`commissions` (
            `id`,
            `user_id`,
            `total_amount`,
            `status`,
            `tanggal_keberangkatan`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `user_id`,
            `total_amount`,
            `status`,
            `tanggal_keberangkatan`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`commissions`
        WHERE `user_id` IS NOT NULL
    ";
    $affectedCommissions = $pdo->exec($migrateCommissions);

    // 2. Migrasi tabel commission_details
    $migrateDetails = "
        INSERT INTO `$targetDb`.`commission_details` (
            `id`,
            `commission_id`,
            `recipient_type`,
            `user_id`,
            `name`,
            `amount`,
            `status`,
            `paid_at`,
            `is_approved`,
            `level`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `commission_id`,
            `recipient_type`,
            `user_id`,
            `name`,
            `amount`,
            `status`,
            `paid_at`,
            `is_approved`,
            `level`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`commission_details`
        WHERE `commission_id` IS NOT NULL AND `user_id` IS NOT NULL
    ";
    $affectedDetails = $pdo->exec($migrateDetails);

    // Hitung total sumber untuk verifikasi
    $totalCommSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`commissions`")->fetch()['c'];
    $totalDetailSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`commission_details`")->fetch()['c'];

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "-> Total commissions di sumber : $totalCommSource | Berhasil dimigrasi : $affectedCommissions\n";
    echo "-> Total commission_details di sumber : $totalDetailSource | Berhasil dimigrasi : $affectedDetails\n";
    echo "\nMigrasi commissions selesai.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}