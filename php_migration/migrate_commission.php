<?php

/**
 * migrate_commissions.php
 *
 * Migrasi tabel commissions (customer_id -> user_id).
 * Resolve lewat: customers.id = customer_id -> customers.user_id
 *
 * commission_details BELUM dimigrasi di sini (ditunda dulu).
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

    // Nonaktifkan Foreign Key Checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi tabel commissions...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`commissions`");

    $migrateCommissionsSql = "
        INSERT INTO `$targetDb`.`commissions` (
            `id`, `user_id`, `total_amount`, `status`, `tanggal_keberangkatan`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            co.`id`, cu.`user_id`, co.`total_amount`, co.`status`, co.`tanggal_keberangkatan`,
            co.`created_at`, co.`updated_at`, co.`deleted_at`
        FROM `$sourceDb`.`commissions` co
        INNER JOIN `$sourceDb`.`customers` cu ON co.`customer_id` = cu.`id`
        WHERE cu.`user_id` IS NOT NULL
    ";
    $affectedCommissions = $pdo->exec($migrateCommissionsSql);

    $totalCommissionsSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`commissions`")->fetch()['c'];
    $skippedCommissions = $totalCommissionsSource - $affectedCommissions;

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "-> Total sumber : $totalCommissionsSource\n";
    echo "-> Berhasil     : $affectedCommissions\n";
    echo "-> Di-skip      : $skippedCommissions (customer_id tidak ketemu / user_id NULL)\n";

    if ($skippedCommissions > 0) {
        echo "\nDetail baris yang di-skip:\n";
        $skipStmt = $pdo->query("
            SELECT co.id, co.customer_id
            FROM `$sourceDb`.`commissions` co
            LEFT JOIN `$sourceDb`.`customers` cu ON co.customer_id = cu.id
            WHERE cu.id IS NULL OR cu.user_id IS NULL
        ");
        foreach ($skipStmt->fetchAll() as $row) {
            echo "   - id={$row['id']} customer_id={$row['customer_id']}\n";
        }
    }

    echo "\nMigrasi commissions selesai.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}