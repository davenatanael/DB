<?php
// migrate.php
set_time_limit(0);
ini_set('memory_limit', '2G');

// Konfigurasi Database Sumber
$sourceConfig = [
    'host' => '127.0.0.1',
    'dbname' => 'outclassco_marketing',
    'user' => 'root',
    'pass' => ''
];

// Konfigurasi Database Tujuan
$targetConfig = [
    'host' => '127.0.0.1',
    'dbname' => 'db_ybaik_new',
    'user' => 'root',
    'pass' => ''
];

// Definisi skema tabel standar target (db_ybaik_new) dengan tipe data id BIGINT UNSIGNED
$tableSchemas = [
    'regions' => "CREATE TABLE IF NOT EXISTS `regions` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
        `translations` text COLLATE utf8mb4_unicode_ci,
        `flag` tinyint(1) NOT NULL DEFAULT '1',
        `wikiDataId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'subregions' => "CREATE TABLE IF NOT EXISTS `subregions` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
        `translations` text COLLATE utf8mb4_unicode_ci,
        `region_id` bigint unsigned NOT NULL,
        `flag` tinyint(1) NOT NULL DEFAULT '1',
        `wikiDataId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `subregion_continent` (`region_id`),
        CONSTRAINT `subregion_continent_final` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'countries' => "CREATE TABLE IF NOT EXISTS `countries` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
        `iso3` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `numeric_code` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `iso2` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `phonecode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `capital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `currency_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `currency_symbol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `tld` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `native` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `region_id` bigint unsigned DEFAULT NULL,
        `subregion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `subregion_id` bigint unsigned DEFAULT NULL,
        `nationality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `timezones` text COLLATE utf8mb4_unicode_ci,
        `translations` text COLLATE utf8mb4_unicode_ci,
        `latitude` decimal(10,8) DEFAULT NULL,
        `longitude` decimal(11,8) DEFAULT NULL,
        `emoji` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `emojiU` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `flag` tinyint(1) NOT NULL DEFAULT '1',
        `wikiDataId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `country_continent` (`region_id`),
        KEY `country_subregion` (`subregion_id`),
        CONSTRAINT `country_continent_final` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`),
        CONSTRAINT `country_subregion_final` FOREIGN KEY (`subregion_id`) REFERENCES `subregions` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'states' => "CREATE TABLE IF NOT EXISTS `states` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
        `country_id` bigint unsigned NOT NULL,
        `country_code` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `fips_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `iso2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `level` int DEFAULT NULL,
        `parent_id` int DEFAULT NULL,
        `native` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `latitude` decimal(10,8) DEFAULT NULL,
        `longitude` decimal(11,8) DEFAULT NULL,
        `flag` tinyint(1) NOT NULL DEFAULT '1',
        `wikiDataId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `country_region` (`country_id`),
        CONSTRAINT `country_region_final` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'cities' => "CREATE TABLE IF NOT EXISTS `cities` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
        `state_id` bigint unsigned NOT NULL,
        `state_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `country_id` bigint unsigned NOT NULL,
        `country_code` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `latitude` decimal(10,8) DEFAULT NULL,
        `longitude` decimal(11,8) DEFAULT NULL,
        `wikiDataId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
        `flag` tinyint(1) NOT NULL DEFAULT '1',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `cities_test_ibfk_1` (`state_id`),
        KEY `cities_test_ibfk_2` (`country_id`),
        CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`),
        CONSTRAINT `cities_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

try {
    $sourcePdo = new PDO(
        "mysql:host={$sourceConfig['host']};dbname={$sourceConfig['dbname']};charset=utf8mb4",
        $sourceConfig['user'],
        $sourceConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $targetPdo = new PDO(
        "mysql:host={$targetConfig['host']};dbname={$targetConfig['dbname']};charset=utf8mb4",
        $targetConfig['user'],
        $targetConfig['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Koneksi database berhasil.\n";

    // Nonaktifkan Foreign Key Checks sementara di database target
    $targetPdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Urutan tabel sesuai relasi data
    $tables = [
        'regions',
        'subregions',
        'countries',
        'states',
        'cities'
    ];

    $chunkSize = 5000;

    foreach ($tables as $table) {
        echo "\n=== Memproses Tabel: {$table} ===\n";

        // 1. Cek apakah tabel sumber ada
        $checkSourceStmt = $sourcePdo->prepare(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = :dbname AND table_name = :tablename LIMIT 1"
        );
        $checkSourceStmt->execute([
            ':dbname' => $sourceConfig['dbname'],
            ':tablename' => $table
        ]);
        if (!$checkSourceStmt->fetchColumn()) {
            echo "Tabel {$table} tidak ditemukan di database sumber. Dilewati.\n";
            continue;
        }

        // 2. Pastikan tabel di target dibuat menggunakan skema baru jika belum ada
        if (isset($tableSchemas[$table])) {
            $targetPdo->exec($tableSchemas[$table]);
        }

        // 3. Ambil daftar kolom yang beririsan (ada di sumber & tujuan)
        $sourceCols = $sourcePdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        $targetCols = $targetPdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        $commonCols = array_values(array_intersect($sourceCols, $targetCols));

        if (empty($commonCols)) {
            echo "Tidak ada kolom yang cocok untuk tabel {$table}. Dilewati.\n";
            continue;
        }

        // 4. Kosongkan data tabel tujuan sebelum import
        $targetPdo->exec("TRUNCATE TABLE `{$table}`;");

        // 5. Cek total baris data sumber
        $totalRows = (int) $sourcePdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo "Total data yang akan dimigrasi: {$totalRows} baris.\n";

        if ($totalRows === 0) {
            echo "Tabel kosong, lewati proses transfer baris.\n";
            continue;
        }

        // 6. Migrasi data menggunakan chunking berdasarkan common columns
        $columnList = '`' . implode('`, `', $commonCols) . '`';
        $placeholders = implode(', ', array_fill(0, count($commonCols), '?'));
        $insertSql = "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})";
        $insertStmt = $targetPdo->prepare($insertSql);

        $offset = 0;
        while ($offset < $totalRows) {
            $selectSql = "SELECT {$columnList} FROM `{$table}` LIMIT :limit OFFSET :offset";
            $stmt = $sourcePdo->prepare($selectSql);
            $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_NUM);

            if (empty($rows)) {
                break;
            }

            $targetPdo->beginTransaction();
            foreach ($rows as $row) {
                $insertStmt->execute($row);
            }
            $targetPdo->commit();

            $offset += count($rows);
            echo "Proses: {$offset} / {$totalRows} baris selesai...\n";
        }

        echo "Tabel {$table} selesai dimigrasi.\n";
    }

    // Aktifkan kembali Foreign Key Checks
    $targetPdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "\nMigrasi selesai seluruhnya ke db_ybaik_new.\n";

} catch (Exception $e) {
    if (isset($targetPdo) && $targetPdo->inTransaction()) {
        $targetPdo->rollBack();
    }
    if (isset($targetPdo)) {
        $targetPdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
    echo "Error Migrasi: " . $e->getMessage() . "\n";
}