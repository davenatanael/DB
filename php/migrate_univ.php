<?php
// migrate_univ.php
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

// Definisi skema manual khusus tabel yang belum ada di database sumber
$manualSchemas = [
    'univ_has_facilities' => "CREATE TABLE IF NOT EXISTS `univ_has_facilities` (
        `univ_id` BIGINT NOT NULL,
        `univ_facilities_id` BIGINT NOT NULL,
        `name` VARCHAR(45) NULL,
        `image` VARCHAR(500) NULL,
        `created_at` TIMESTAMP NULL DEFAULT NULL,
        `updated_at` TIMESTAMP NULL DEFAULT NULL,
        `deleted_at` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`univ_id`, `univ_facilities_id`),
        INDEX `idx_univ_id` (`univ_id`),
        INDEX `idx_facilities_id` (`univ_facilities_id`)
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

    // Nonaktifkan Foreign Key Checks sementara
    $targetPdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Daftar tabel modul universitas
    $tables = [
        'univ_categories',
        'univ_facilities',
        'universities',
        'univ_has_categories',
        'univ_fee_structures',
        'univ_entry_requirements',
        'univ_programs',
        'univ_has_facilities',
        'univ_facilities_details',
        'univ_accomodations',
        'univ_scholarships',
        'scholarship',
        'univ_accomodation_details',
        'univ_accomodation_photos'
    ];

    $chunkSize = 5000;

    foreach ($tables as $table) {
        echo "\n=== Memproses Tabel: {$table} ===\n";

        // 1. Cek ketersediaan tabel di database sumber
        $checkSourceStmt = $sourcePdo->prepare(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = :dbname AND table_name = :tablename LIMIT 1"
        );
        $checkSourceStmt->execute([
            ':dbname' => $sourceConfig['dbname'],
            ':tablename' => $table
        ]);
        $sourceExists = (bool) $checkSourceStmt->fetchColumn();

        // Penanganan tabel yang belum ada di database sumber
        if (!$sourceExists) {
            if (isset($manualSchemas[$table])) {
                $targetPdo->exec($manualSchemas[$table]);
                echo "Tabel '{$table}' tidak ada di sumber. Skema manual dibuat di target. Dilewati.\n";
            } else {
                echo "Tabel '{$table}' tidak ada di sumber & tidak ada skema manual. Dilewati.\n";
            }
            continue;
        }

        // Ambil info kolom & total baris di database sumber
        $sourceCols = $sourcePdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
        $sourceRowCount = (int) $sourcePdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

        // 2. Cek apakah tabel sudah ada di database tujuan
        $checkTargetStmt = $targetPdo->prepare(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = :dbname AND table_name = :tablename LIMIT 1"
        );
        $checkTargetStmt->execute([
            ':dbname' => $targetConfig['dbname'],
            ':tablename' => $table
        ]);
        $targetExists = (bool) $checkTargetStmt->fetchColumn();

        $tableReadyForInsert = false;

        if ($targetExists) {
            $targetCols = $targetPdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
            $targetRowCount = (int) $targetPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

            // Pengecekan: Jika kolom cocok dan total baris data identik, langsung skip
            if ($sourceCols === $targetCols && $sourceRowCount === $targetRowCount) {
                echo "[SKIP] Tabel '{$table}' sudah sesuai (Struktur cocok & Data: {$targetRowCount} baris sama).\n";
                continue;
            }

            if ($sourceCols !== $targetCols) {
                echo "Tabel '{$table}' ada tetapi struktur kolom berbeda. Membuat ulang skema...\n";
                $targetPdo->exec("DROP TABLE `{$table}`;");
            } else {
                // Struktur kolom sama, namun jumlah data berbeda
                echo "Tabel '{$table}' struktur cocok namun data berbeda (Target: {$targetRowCount}, Sumber: {$sourceRowCount}). Mengulang import data...\n";
                $targetPdo->exec("TRUNCATE TABLE `{$table}`;");
                $tableReadyForInsert = true;
            }
        }

        // 3. Buat skema tabel jika belum ada / baru saja di-drop
        if (!$tableReadyForInsert) {
            $showCreate = $sourcePdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $createSql = $showCreate['Create Table'];
            $createSql = preg_replace('/^CREATE TABLE /i', 'CREATE TABLE IF NOT EXISTS ', $createSql);
            $targetPdo->exec($createSql);
            $targetPdo->exec("TRUNCATE TABLE `{$table}`;");
            echo "Struktur tabel '{$table}' berhasil disiapkan.\n";
        }

        // 4. Jika sumber kosong, lanjut ke tabel berikutnya
        if ($sourceRowCount === 0) {
            echo "Data di database sumber kosong, dilewati.\n";
            continue;
        }

        // 5. Transfer data menggunakan chunking
        echo "Memulai migrasi {$sourceRowCount} baris...\n";
        $offset = 0;
        while ($offset < $sourceRowCount) {
            $stmt = $sourcePdo->prepare("SELECT * FROM `{$table}` LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                break;
            }

            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $insertSql = "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})";
            $insertStmt = $targetPdo->prepare($insertSql);

            $targetPdo->beginTransaction();
            foreach ($rows as $row) {
                $insertStmt->execute(array_values($row));
            }
            $targetPdo->commit();

            $offset += count($rows);
            echo "Proses: {$offset} / {$sourceRowCount} baris selesai...\n";
        }

        echo "Tabel '{$table}' berhasil dimigrasi.\n";
    }

    // Aktifkan kembali Foreign Key Checks
    $targetPdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "\nProses migrasi selesai.\n";

} catch (Exception $e) {
    if (isset($targetPdo) && $targetPdo->inTransaction()) {
        $targetPdo->rollBack();
    }
    echo "Error Migrasi: " . $e->getMessage() . "\n";
}