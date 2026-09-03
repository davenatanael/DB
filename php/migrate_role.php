<?php

/**
 * migrate_role.php
 *
 * Migrasi master Roles dan master Privileges:
 * 1. Roles: Inisialisasi daftar role standar (1-9).
 * 2. Privileges: Migrasi outclassco_marketing.privileges -> db_ybaik_new.privileges.
 *
 * Catatan Struktur Privileges:
 * - Kolom bersama: id, name, deleted_at (dipertahankan 1:1).
 * - Kolom baru di DB target:
 *   - created_at: diisi NOW() (CURRENT_TIMESTAMP)
 *   - updated_at: diisi NOW() (CURRENT_TIMESTAMP)
 *   - keterangan: diisi NULL (disediakan untuk penambahan deskripsi hak akses jika diperlukan)
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

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // -------------------------------------------------------------
    // 1. Inisialisasi Tabel Roles
    // -------------------------------------------------------------
    echo "Memulai migrasi master Roles...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`roles`;");

    $sqlRoles = "
        INSERT INTO `$targetDb`.`roles` (`id`, `name`) VALUES
        (1, 'Superadmin'),
        (2, 'Admin'),
        (3, 'Korwil'),
        (4, 'Koordinator'),
        (5, 'Consultant'),
        (6, 'Finance'),
        (7, 'School'),
        (8, 'Parent'),
        (9, 'Student');
    ";
    $pdo->exec($sqlRoles);
    echo "-> Tabel roles berhasil direset dan diisi 9 roles standar.\n\n";

    // -------------------------------------------------------------
    // 2. Migrasi Tabel Privileges
    // -------------------------------------------------------------
    echo "Memulai migrasi master Privileges...\n";
    $pdo->exec("DELETE FROM `$targetDb`.`privileges`;");

    $migratePrivilegesSql = "
        INSERT INTO `$targetDb`.`privileges` (
            `id`,
            `name`,
            `created_at`,
            `updated_at`,
            `deleted_at`,
            `keterangan`
        )
        SELECT 
            `id`,
            `name`,
            NOW() AS `created_at`,
            NOW() AS `updated_at`,
            `deleted_at`,
            NULL AS `keterangan`
        FROM `$sourceDb`.`privileges`
    ";
    $affectedPrivileges = $pdo->exec($migratePrivilegesSql);
    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`privileges`")->fetch()['c'];

    echo "-> Total privileges di sumber : $totalSource\n";
    echo "-> Berhasil dimigrasi         : $affectedPrivileges\n\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Migrasi Roles & Privileges selesai.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}