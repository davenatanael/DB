<?php
// integrate_bankAccounts_users.php

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

    // 1. Samakan aturan collation pada sesi koneksi
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    echo "Memulai matching bank_accounts ke users...\n";

    // Urutan prioritas: consultants > koordinators > korwils > students.
    // Kolom teks di kedua sisi dibungkus COLLATE utf8mb4_unicode_ci agar tidak error 1267.
    $statements = [];

    // 1) consultants -> punya user_id langsung
    $statements['consultants'] = "
        UPDATE `$targetDb`.`users` u
        INNER JOIN `$sourceDb`.`consultants` c 
            ON c.user_id = u.id
        INNER JOIN `$targetDb`.`bank_accounts` ba
            ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(c.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
           AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(c.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
        SET u.bank_accounts_id = ba.id
        WHERE u.bank_accounts_id IS NULL
          AND c.deleted_at IS NULL
          AND NULLIF(TRIM(c.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(c.nomor_rekening), '') IS NOT NULL
    ";

    // 2) koordinators -> punya user_id langsung
    $statements['koordinators'] = "
        UPDATE `$targetDb`.`users` u
        INNER JOIN `$sourceDb`.`koordinators` k 
            ON k.user_id = u.id
        INNER JOIN `$targetDb`.`bank_accounts` ba
            ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(k.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
           AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(k.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
        SET u.bank_accounts_id = ba.id
        WHERE u.bank_accounts_id IS NULL
          AND k.deleted_at IS NULL
          AND NULLIF(TRIM(k.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(k.nomor_rekening), '') IS NOT NULL
    ";

    // 3) korwils -> punya user_id langsung
    $statements['korwils'] = "
        UPDATE `$targetDb`.`users` u
        INNER JOIN `$sourceDb`.`korwils` kw 
            ON kw.user_id = u.id
        INNER JOIN `$targetDb`.`bank_accounts` ba
            ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(kw.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
           AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(kw.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
        SET u.bank_accounts_id = ba.id
        WHERE u.bank_accounts_id IS NULL
          AND kw.deleted_at IS NULL
          AND NULLIF(TRIM(kw.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(kw.nomor_rekening), '') IS NOT NULL
    ";

    // 4) students -> resolve lewat customers (customer_id -> user_id)
    $statements['students'] = "
        UPDATE `$targetDb`.`users` u
        INNER JOIN `$sourceDb`.`customers` c 
            ON c.user_id = u.id
        INNER JOIN `$sourceDb`.`students` s 
            ON s.customer_id = c.id
        INNER JOIN `$targetDb`.`bank_accounts` ba
            ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(s.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
           AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(s.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
        SET u.bank_accounts_id = ba.id
        WHERE u.bank_accounts_id IS NULL
          AND s.deleted_at IS NULL
          AND NULLIF(TRIM(s.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(s.nomor_rekening), '') IS NOT NULL
    ";

    $totalUpdated = 0;
    foreach ($statements as $label => $sql) {
        $affected = $pdo->exec($sql);
        $totalUpdated += $affected;
        echo "  [$label] $affected user ter-update.\n";
    }

    echo "\nSelesai! Total $totalUpdated user berhasil di-link ke bank_accounts.\n";

} catch (PDOException $e) {
    echo "Error migrasi: " . $e->getMessage() . "\n";
}