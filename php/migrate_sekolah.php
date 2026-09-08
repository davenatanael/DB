<?php

/**
 * migrate_sekolah.php
 *
 * Migrasi data master sekolah dan relasi agent dari database lama (outclassco_marketing)
 * ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.sekolah
 * Target : db_ybaik_new.sekolah
 *
 * Logika Pencocokan Agent ID:
 *   - Pada skema baru, data agent disatukan ke dalam tabel `agents`.
 *   - Kolom `agent_id` di db_ybaik_new.sekolah mengambil relasi dari `consultant_id` data lama.
 *   - Karena setelah migrasi data consultant disatukan ke tabel `agents`, ID-nya berubah.
 *   - Penyesuaian ID lama ke ID baru dilakukan melalui relasi user_id:
 *       outclassco_marketing.consultants (id -> user_id) -> db_ybaik_new.agents (users_id -> id)
 *   - Jika consultant_id NULL, maka agent_id diisi NULL.
 *
 * Catatan Schema & Data:
 *   - Kolom `agent_id` diizinkan NULL (`DEFAULT NULL`) karena sebagian besar sekolah merupakan
 *     data master nasional yang belum memiliki agen/konsultan terkait.
 *   - Kolom `country_id` dinormalisasi dengan default 102 (Indonesia) jika NULL.
 *   - Kolom `npsn` di-trim dan di-NULL-kan jika string kosong untuk mematuhi UNIQUE KEY.
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
    $pdo->exec("SET sql_mode = ''");

    echo "====================================================================\n";
    echo "    MEMULAI MIGRASI DATA SEKOLAH & RELASI AGENTS (CONSULTANT)       \n";
    echo "====================================================================\n\n";

    // 1. Pastikan kolom agent_id mengizinkan NULL (karena di DDL awal mungkin NOT NULL)
    $pdo->exec("ALTER TABLE `$targetDb`.`sekolah` MODIFY `agent_id` bigint unsigned NULL DEFAULT NULL");

    // 2. Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`sekolah`");
    echo "-> Tabel `sekolah` di $targetDb berhasil dikosongkan.\n\n";

    // 3. Ambil data referensi mapping Consultant ke Agents untuk logging & verifikasi
    $stmtCs = $pdo->query("
        SELECT cs.id AS old_consultant_id, cs.name AS consultant_name, cs.user_id, a.id AS new_agent_id
        FROM `$sourceDb`.`consultants` cs
        JOIN `$targetDb`.`agents` a ON cs.user_id = a.users_id
        ORDER BY cs.id ASC
    ");
    $consultantMap = $stmtCs->fetchAll();
    echo "-> Terdeteksi " . count($consultantMap) . " Consultant terhubung ke Agents di target DB.\n\n";

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
            `agent_id`,
            `created_by`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            s.`id`,
            COALESCE(s.`country_id`, 102) AS `country_id`,
            s.`kode_prop`,
            s.`propinsi`,
            s.`kode_kab_kota`,
            s.`kabupaten_kota`,
            s.`kode_kec`,
            s.`kecamatan`,
            NULLIF(TRIM(s.`npsn`), '') AS `npsn`,
            s.`sekolah`,
            s.`bentuk`,
            s.`status`,
            s.`alamat_jalan`,
            s.`lintang`,
            s.`bujur`,
            -- Pemetaan agent_id: Mengambil dari consultant_id lama yang disesuaikan ke new agent_id
            a_cs.`id` AS `agent_id`,
            s.`created_by`,
            s.`created_at`,
            s.`updated_at`,
            s.`deleted_at`
        FROM `$sourceDb`.`sekolah` s
        -- Map consultant_id (outclassco_marketing.consultants.id -> user_id -> db_ybaik_new.agents.id)
        LEFT JOIN `$sourceDb`.`consultants` cs ON s.`consultant_id` = cs.`id`
        LEFT JOIN `$targetDb`.`agents` a_cs ON cs.`user_id` = a_cs.`users_id`
    ";

    $affected = $pdo->exec($sql);
    $elapsed = round(microtime(true) - $startTime, 2);

    $totalInTarget = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah`")->fetchColumn();
    $withAgent     = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE agent_id IS NOT NULL")->fetchColumn();

    echo "\n=== HASIL MIGRASI DATA SEKOLAH ===\n";
    echo "Waktu eksekusi                      : $elapsed detik\n";
    echo "Total data berhasil dimasukkan      : $affected baris\n";
    echo "Total data di target DB             : $totalInTarget baris\n";
    echo "Total Sekolah dengan Agent terhubung: $withAgent sekolah\n";

    echo "\nDetail Sekolah yang Terhubung ke Consultant (New Agent ID):\n";
    $detailSekolah = $pdo->query("
        SELECT 
            s.id, 
            s.sekolah, 
            s.agent_id, 
            u.name AS consultant_name,
            u.email AS consultant_email
        FROM `$targetDb`.`sekolah` s
        JOIN `$targetDb`.`agents` a ON s.agent_id = a.id
        JOIN `$targetDb`.`users` u ON a.users_id = u.id
        ORDER BY s.id ASC
    ")->fetchAll();

    foreach ($detailSekolah as $ds) {
        echo sprintf(
            "   - [Sekolah ID: %-6d] %-35s -> agent_id: %-3d (%s)\n",
            $ds['id'],
            $ds['sekolah'],
            $ds['agent_id'],
            $ds['consultant_name']
        );
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI DATA SEKOLAH SELESAI DENGAN SUKSES!                     \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
    exit(1);
}

