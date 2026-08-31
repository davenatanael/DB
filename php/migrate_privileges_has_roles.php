<?php

/**
 * migrate_privileges_has_roles.php
 *
 * Migrasi role_privileges (lama) -> privileges_has_roles (baru).
 *
 * PRASYARAT: roles dan privileges HARUS sudah dimigrasikan duluan
 * (privileges_has_roles FK ke keduanya).
 *
 * -----------------------------------------------------------------------
 * CATATAN PERUBAHAN STRUKTUR
 * -----------------------------------------------------------------------
 * - role_id & privilege_id: lama nullable, baru NOT NULL -> baris dengan
 *   salah satu NULL otomatis di-skip (dari cek data, 0 baris begini).
 * - deleted_at: DIHAPUS TOTAL di tabel baru (tidak ada soft-delete lagi).
 *   Karena tidak ada tempat buat merepresentasikan status "terhapus",
 *   SEMUA baris (termasuk yang di lama sudah soft-deleted) tetap
 *   dimigrasikan sebagai baris aktif -- sesuai arahan, bukan di-skip.
 * - id: tetap dipertahankan 1:1 (meski jadi bagian composite PK di baru),
 *   supaya konsisten dengan pola ID-preservation di seluruh migrasi ini.
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

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi role_privileges -> privileges_has_roles...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`privileges_has_roles`");

    $migrateSql = "
        INSERT INTO `$targetDb`.`privileges_has_roles` (`id`, `privilege_id`, `role_id`)
        SELECT `id`, `privilege_id`, `role_id`
        FROM `$sourceDb`.`role_privileges`
        WHERE `role_id` IS NOT NULL
          AND `privilege_id` IS NOT NULL
    ";
    $affected = $pdo->exec($migrateSql);

    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`role_privileges`")->fetch()['c'];
    $skipped = $totalSource - $affected;

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "-> Total di sumber : $totalSource\n";
    echo "-> Berhasil dimigrasi (termasuk yang dulu soft-deleted) : $affected\n";
    echo "-> Di-skip (role_id atau privilege_id NULL) : $skipped\n";

    if ($skipped > 0) {
        echo "\nDetail baris yang di-skip:\n";
        $skipStmt = $pdo->query("
            SELECT `id`, `role_id`, `privilege_id`
            FROM `$sourceDb`.`role_privileges`
            WHERE `role_id` IS NULL OR `privilege_id` IS NULL
        ");
        foreach ($skipStmt->fetchAll() as $row) {
            echo "   - id={$row['id']} role_id={$row['role_id']} privilege_id={$row['privilege_id']} : role_id/privilege_id NULL\n";
        }
    }

    echo "\nMigrasi privileges_has_roles selesai.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}