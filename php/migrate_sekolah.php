<?php

/**
 * migrate_sekolah.php
 *
 * Migrasi data master sekolah dan relasi agent (korwil, koordinator, consultant)
 * dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.sekolah
 * Target : db_ybaik_new.sekolah
 *
 * Logika Pencocokan Agent ID:
 *   - korwil_id      : outclassco_marketing.korwils (id -> user_id) -> db_ybaik_new.agents (users_id -> id)
 *   - koordinator_id : outclassco_marketing.koordinators (id -> user_id) -> db_ybaik_new.agents (users_id -> id)
 *   - consultant_id  : outclassco_marketing.consultants (id -> user_id) -> db_ybaik_new.agents (users_id -> id)
 *
 * PRASYARAT: countries dan agents HARUS sudah dimigrasikan duluan.
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
    echo "    MEMULAI MIGRASI DATA SEKOLAH & RELASI AGENTS                    \n";
    echo "====================================================================\n\n";

    // Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`sekolah`");
    echo "-> Tabel `sekolah` di $targetDb berhasil dikosongkan.\n\n";

    $startTime = microtime(true);
    echo "-> Memproses migrasi data sekolah dari $sourceDb.sekolah ke $targetDb.sekolah...\n";

    $sql = "
        INSERT INTO `$targetDb`.`sekolah` (
            `id`,
            `country_id`,
            `kode_prop`,
            `propinsi`,
            `kode_kab_kota`,
            `kabupaten_kota`,
            `kode_kec`,
            `kecamatan`,
            `npsn`,
            `sekolah`,
            `bentuk`,
            `status`,
            `alamat_jalan`,
            `lintang`,
            `bujur`,
            `korwil_id`,
            `koordinator_id`,
            `consultant_id`,
            `created_by`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            s.`id`,
            s.`country_id`,
            s.`kode_prop`,
            s.`propinsi`,
            s.`kode_kab_kota`,
            s.`kabupaten_kota`,
            s.`kode_kec`,
            s.`kecamatan`,
            s.`npsn`,
            s.`sekolah`,
            s.`bentuk`,
            s.`status`,
            s.`alamat_jalan`,
            s.`lintang`,
            s.`bujur`,
            a_kw.`id` AS `korwil_id`,
            a_kd.`id` AS `koordinator_id`,
            a_cs.`id` AS `consultant_id`,
            s.`created_by`,
            s.`created_at`,
            s.`updated_at`,
            s.`deleted_at`
        FROM `$sourceDb`.`sekolah` s
        -- Map korwil_id (outclassco_marketing.korwils.id -> agents.id)
        LEFT JOIN `$sourceDb`.`korwils` kw ON s.`korwil_id` = kw.`id`
        LEFT JOIN `$targetDb`.`agents` a_kw ON kw.`user_id` = a_kw.`users_id`
        -- Map koordinator_id (outclassco_marketing.koordinators.id -> agents.id)
        LEFT JOIN `$sourceDb`.`koordinators` kd ON s.`koordinator_id` = kd.`id`
        LEFT JOIN `$targetDb`.`agents` a_kd ON kd.`user_id` = a_kd.`users_id`
        -- Map consultant_id (outclassco_marketing.consultants.id -> agents.id)
        LEFT JOIN `$sourceDb`.`consultants` cs ON s.`consultant_id` = cs.`id`
        LEFT JOIN `$targetDb`.`agents` a_cs ON cs.`user_id` = a_cs.`users_id`
    ";

    $affected = $pdo->exec($sql);
    $elapsed = round(microtime(true) - $startTime, 2);

    $totalInTarget = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah`")->fetchColumn();
    $withKw = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE korwil_id IS NOT NULL")->fetchColumn();
    $withKd = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE koordinator_id IS NOT NULL")->fetchColumn();
    $withCs = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE consultant_id IS NOT NULL")->fetchColumn();

    echo "\n=== HASIL MIGRASI DATA SEKOLAH ===\n";
    echo "Waktu eksekusi                  : $elapsed detik\n";
    echo "Total data berhasil dimasukkan  : $affected baris\n";
    echo "Total data di target DB         : $totalInTarget baris\n";
    echo "Sekolah dengan Korwil terhubung : $withKw sekolah\n";
    echo "Sekolah dengan Koor terhubung   : $withKd sekolah\n";
    echo "Sekolah dengan Cons terhubung   : $withCs sekolah\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI DATA SEKOLAH SELESAI DENGAN SUKSES!                     \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
