<?php
// migrate_bank_accounts.php

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

    echo "Memulai migrasi tabel bank_accounts (Hanya Data Unik)...\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // TRUNCATE otomatis menghapus semua data dan me-reset AUTO_INCREMENT kembali ke 1
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`bank_accounts`");

    // Menggunakan UNION (tanpa ALL) untuk otomatis deduplikasi
    $migrateSql = "
        INSERT INTO `$targetDb`.`bank_accounts` (`nama_bank`, `nomor_rekening`)
        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`consultants`
        WHERE deleted_at IS NULL
          AND NULLIF(TRIM(nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL

        UNION

        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`koordinators`
        WHERE deleted_at IS NULL
          AND NULLIF(TRIM(nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL

        UNION

        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`korwils`
        WHERE deleted_at IS NULL
          AND NULLIF(TRIM(nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL

        UNION

        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`students`
        WHERE deleted_at IS NULL
          AND NULLIF(TRIM(nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
    ";

    $affectedRows = $pdo->exec($migrateSql);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Migrasi sukses! Sebanyak $affectedRows rekening unik berhasil dipindahkan ke $targetDb.bank_accounts (ID 1 s/d $affectedRows).\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}