<?php

// ============================================================
// MIGRASI TABEL: guests
// ============================================================
// Sumber : outclassco_marketing.guests
// Tujuan : db_ybaik_new.guests
//
// Catatan Struktur:
//   Struktur tabel lama dan baru IDENTIK:
//     id, name, email, phone, notes,
//     converted_student_id, is_converted,
//     created_at, updated_at, deleted_at
//
//   Perbedaan minor:
//     - DB baru menggunakan COLLATE utf8mb4_unicode_ci (hanya DDL)
//     - DB baru memiliki DEFAULT CURRENT_TIMESTAMP pada created_at/updated_at
//   Tidak ada transformasi data yang diperlukan.
// ============================================================

$host     = '127.0.0.1';
$user     = 'root';
$pass     = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Nonaktifkan Foreign Key Checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi tabel guests...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`guests`");

    // Migrasi langsung: semua kolom identik antara lama dan baru
    $migrateSql = "
        INSERT INTO `$targetDb`.`guests` (
            `id`,
            `name`,
            `email`,
            `phone`,
            `notes`,
            `converted_student_id`,
            `is_converted`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `name`,
            `email`,
            `phone`,
            `notes`,
            `converted_student_id`,
            `is_converted`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`guests`
    ";

    $affectedRows = $pdo->exec($migrateSql);

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Migrasi sukses! Sebanyak $affectedRows data guests berhasil dipindahkan ke $targetDb.guests.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
