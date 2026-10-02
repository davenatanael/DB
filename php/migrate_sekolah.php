<?php

/**
 * migrate_sekolah.php
 *
 * Migrasi data master sekolah dan relasi agent (Korwil, Koordinator, Consultant)
 * dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 *
 * Sumber : outclassco_marketing.sekolah
 * Target : db_ybaik_new.sekolah
 *
 * Logika Pencocokan Agent ID (Korwil, Koordinator, Consultant):
 *   - Pada skema baru, seluruh entitas agent (Korwil, Koordinator, Consultant) disatukan ke tabel `agents`.
 *   - Tabel `sekolah` memiliki 3 kolom relasi agent terpisah:
 *       1. `korwil_id`      -> FK ke `agents.id` (berdasarkan outclassco_marketing.korwils.user_id)
 *       2. `koordinator_id` -> FK ke `agents.id` (berdasarkan outclassco_marketing.koordinators.user_id)
 *       3. `consultant_id`  -> FK ke `agents.id` (berdasarkan outclassco_marketing.consultants.user_id)
 *   - Penyesuaian ID lama ke ID baru dilakukan melalui relasi user_id ke db_ybaik_new.agents.users_id.
 *   - Kolom-kolom ini bersifat NULL (`DEFAULT NULL`) karena sebagian besar sekolah merupakan
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
    echo "    MEMULAI MIGRASI DATA SEKOLAH & RELASI AGENTS HIERARKI           \n";
    echo "    (KORWIL, KOORDINATOR, CONSULTANT)                               \n";
    echo "====================================================================\n\n";

    // 1. DDL Sync: Pastikan skema tabel `sekolah` memiliki korwil_id, koordinator_id, consultant_id
    echo "1. Memeriksa dan menyesuaikan struktur kolom tabel `$targetDb`.`sekolah`...\n";

    // Cek kolom yang ada di target db
    $existingColsStmt = $pdo->query("
        SELECT COLUMN_NAME 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = '$targetDb' AND TABLE_NAME = 'sekolah'
    ");
    $existingCols = $existingColsStmt->fetchAll(PDO::FETCH_COLUMN);

    // Drop kolom agent_id lama jika masih ada
    if (in_array('agent_id', $existingCols)) {
        // Cek nama FK constraint pada kolom agent_id
        $fkStmt = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '$targetDb' 
              AND TABLE_NAME = 'sekolah' 
              AND COLUMN_NAME = 'agent_id' 
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fks = $fkStmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($fks as $fkName) {
            $pdo->exec("ALTER TABLE `$targetDb`.`sekolah` DROP FOREIGN KEY `$fkName`");
            echo "   -> Dropped FK `$fkName` pada kolom agent_id.\n";
        }

        $pdo->exec("ALTER TABLE `$targetDb`.`sekolah` DROP COLUMN `agent_id`");
        echo "   -> Kolom lama `agent_id` berhasil dihapus dari tabel `$targetDb`.`sekolah`.\n";
    }

    // Tambah kolom korwil_id, koordinator_id, consultant_id jika belum ada
    if (!in_array('korwil_id', $existingCols)) {
        $pdo->exec("ALTER TABLE `$targetDb`.`sekolah` ADD COLUMN `korwil_id` BIGINT UNSIGNED NULL AFTER `bujur`");
        echo "   -> Kolom `korwil_id` berhasil ditambahkan.\n";
    }
    if (!in_array('koordinator_id', $existingCols)) {
        $pdo->exec("ALTER TABLE `$targetDb`.`sekolah` ADD COLUMN `koordinator_id` BIGINT UNSIGNED NULL AFTER `korwil_id`");
        echo "   -> Kolom `koordinator_id` berhasil ditambahkan.\n";
    }
    if (!in_array('consultant_id', $existingCols)) {
        $pdo->exec("ALTER TABLE `$targetDb`.`sekolah` ADD COLUMN `consultant_id` BIGINT UNSIGNED NULL AFTER `koordinator_id`");
        echo "   -> Kolom `consultant_id` berhasil ditambahkan.\n";
    }

    // Tambah foreign key constraints jika belum ada
    $existingFkStmt = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = '$targetDb' 
          AND TABLE_NAME = 'sekolah' 
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ");
    $existingFks = $existingFkStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('fk_sekolah_korwil_id', $existingFks)) {
        try {
            $pdo->exec("
                ALTER TABLE `$targetDb`.`sekolah` 
                ADD CONSTRAINT `fk_sekolah_korwil_id` 
                    FOREIGN KEY (`korwil_id`) REFERENCES `agents` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ");
            echo "   -> FK `fk_sekolah_korwil_id` berhasil dibuat.\n";
        } catch (Exception $e) {
            // Abaikan jika sudah ada dengan nama berbeda
        }
    }

    if (!in_array('fk_sekolah_koordinator_id', $existingFks)) {
        try {
            $pdo->exec("
                ALTER TABLE `$targetDb`.`sekolah` 
                ADD CONSTRAINT `fk_sekolah_koordinator_id` 
                    FOREIGN KEY (`koordinator_id`) REFERENCES `agents` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ");
            echo "   -> FK `fk_sekolah_koordinator_id` berhasil dibuat.\n";
        } catch (Exception $e) {
            // Abaikan jika sudah ada dengan nama berbeda
        }
    }

    if (!in_array('fk_sekolah_consultant_id', $existingFks)) {
        try {
            $pdo->exec("
                ALTER TABLE `$targetDb`.`sekolah` 
                ADD CONSTRAINT `fk_sekolah_consultant_id` 
                    FOREIGN KEY (`consultant_id`) REFERENCES `agents` (`id`) 
                    ON DELETE SET NULL ON UPDATE CASCADE
            ");
            echo "   -> FK `fk_sekolah_consultant_id` berhasil dibuat.\n";
        } catch (Exception $e) {
            // Abaikan jika sudah ada dengan nama berbeda
        }
    }

    echo "   -> Struktur tabel `$targetDb`.`sekolah` sudah sesuai.\n\n";

    // 2. Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`sekolah`");
    echo "-> Tabel `sekolah` di $targetDb berhasil dikosongkan.\n\n";

    // 3. Eksekusi Migrasi Data
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
            -- Pemetaan korwil_id: outclassco_marketing.korwils.id -> user_id -> db_ybaik_new.agents.id
            a_kw.`id` AS `korwil_id`,
            -- Pemetaan koordinator_id: outclassco_marketing.koordinators.id -> user_id -> db_ybaik_new.agents.id
            a_kd.`id` AS `koordinator_id`,
            -- Pemetaan consultant_id: outclassco_marketing.consultants.id -> user_id -> db_ybaik_new.agents.id
            a_cs.`id` AS `consultant_id`,
            s.`created_by`,
            s.`created_at`,
            s.`updated_at`,
            s.`deleted_at`
        FROM `$sourceDb`.`sekolah` s
        -- Relasi Korwil
        LEFT JOIN `$sourceDb`.`korwils` kw ON s.`korwil_id` = kw.`id`
        LEFT JOIN `$targetDb`.`agents` a_kw ON kw.`user_id` = a_kw.`users_id`
        -- Relasi Koordinator
        LEFT JOIN `$sourceDb`.`koordinators` kd ON s.`koordinator_id` = kd.`id`
        LEFT JOIN `$targetDb`.`agents` a_kd ON kd.`user_id` = a_kd.`users_id`
        -- Relasi Consultant
        LEFT JOIN `$sourceDb`.`consultants` cs ON s.`consultant_id` = cs.`id`
        LEFT JOIN `$targetDb`.`agents` a_cs ON cs.`user_id` = a_cs.`users_id`
    ";

    $affected = $pdo->exec($sql);
    $elapsed = round(microtime(true) - $startTime, 2);

    $totalInTarget  = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah`")->fetchColumn();
    $withKorwil     = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE korwil_id IS NOT NULL")->fetchColumn();
    $withKoor       = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE koordinator_id IS NOT NULL")->fetchColumn();
    $withCons       = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE consultant_id IS NOT NULL")->fetchColumn();
    $withAnyAgent   = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE korwil_id IS NOT NULL OR koordinator_id IS NOT NULL OR consultant_id IS NOT NULL")->fetchColumn();

    echo "\n=== HASIL MIGRASI DATA SEKOLAH ===\n";
    echo "Waktu eksekusi                        : $elapsed detik\n";
    echo "Total data berhasil dimasukkan        : $affected baris\n";
    echo "Total data di target DB               : $totalInTarget baris\n";
    echo "Total Sekolah terhubung ke Korwil     : $withKorwil sekolah\n";
    echo "Total Sekolah terhubung ke Koordinator: $withKoor sekolah\n";
    echo "Total Sekolah terhubung ke Consultant : $withCons sekolah\n";
    echo "Total Sekolah dengan minimal 1 Agent  : $withAnyAgent sekolah\n";

    echo "\nDetail Sekolah yang Terhubung ke Agent (Korwil / Koordinator / Consultant):\n";
    $detailSekolah = $pdo->query("
        SELECT 
            s.id, 
            s.sekolah, 
            s.korwil_id,
            u_kw.name AS korwil_name,
            s.koordinator_id,
            u_kd.name AS koordinator_name,
            s.consultant_id,
            u_cs.name AS consultant_name
        FROM `$targetDb`.`sekolah` s
        LEFT JOIN `$targetDb`.`agents` a_kw ON s.korwil_id = a_kw.id
        LEFT JOIN `$targetDb`.`users` u_kw ON a_kw.users_id = u_kw.id
        LEFT JOIN `$targetDb`.`agents` a_kd ON s.koordinator_id = a_kd.id
        LEFT JOIN `$targetDb`.`users` u_kd ON a_kd.users_id = u_kd.id
        LEFT JOIN `$targetDb`.`agents` a_cs ON s.consultant_id = a_cs.id
        LEFT JOIN `$targetDb`.`users` u_cs ON a_cs.users_id = u_cs.id
        WHERE s.korwil_id IS NOT NULL OR s.koordinator_id IS NOT NULL OR s.consultant_id IS NOT NULL
        ORDER BY s.id ASC
    ")->fetchAll();

    foreach ($detailSekolah as $ds) {
        $infoKorwil = $ds['korwil_id'] ? "Korwil [ID: {$ds['korwil_id']} - {$ds['korwil_name']}]" : null;
        $infoKoor   = $ds['koordinator_id'] ? "Koor [ID: {$ds['koordinator_id']} - {$ds['koordinator_name']}]" : null;
        $infoCons   = $ds['consultant_id'] ? "Cons [ID: {$ds['consultant_id']} - {$ds['consultant_name']}]" : null;

        $agentsList = implode(', ', array_filter([$infoKorwil, $infoKoor, $infoCons]));

        echo sprintf(
            "   - [Sekolah ID: %-6d] %-35s -> %s\n",
            $ds['id'],
            $ds['sekolah'],
            $agentsList
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
