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

        // 1. Cek apakah tabel sudah ada di database tujuan
        $checkTableStmt = $targetPdo->prepare(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = :dbname AND table_name = :tablename LIMIT 1"
        );
        $checkTableStmt->execute([
            ':dbname' => $targetConfig['dbname'],
            ':tablename' => $table
        ]);
        $tableExists = (bool) $checkTableStmt->fetchColumn();

        $needsCreation = true;

        if ($tableExists) {
            // Cek kesesuaian kolom antara database sumber dan tujuan
            $sourceCols = $sourcePdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
            $targetCols = $targetPdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);

            if ($sourceCols === $targetCols) {
                $needsCreation = false;
                echo "Tabel {$table} sudah ada dan strukturnya sesuai. Melewati proses pembuatan tabel.\n";
            } else {
                echo "Tabel {$table} sudah ada namun kolom tidak cocok. Memperbarui struktur tabel...\n";
                $targetPdo->exec("DROP TABLE `{$table}`;");
            }
        }

        // Buat tabel jika belum ada atau strukturnya belum sesuai
        if ($needsCreation) {
            $showCreate = $sourcePdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $createSql = $showCreate['Create Table'];
            $createSql = preg_replace('/^CREATE TABLE /i', 'CREATE TABLE IF NOT EXISTS ', $createSql);
            $targetPdo->exec($createSql);
            echo "Struktur tabel {$table} berhasil dibuat di database tujuan.\n";
        }

        // 2. Kosongkan data tabel tujuan sebelum import
        $targetPdo->exec("TRUNCATE TABLE `{$table}`;");

        // 3. Cek total baris data sumber
        $totalRows = (int) $sourcePdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo "Total data yang akan dimigrasi: {$totalRows} baris.\n";

        if ($totalRows === 0) {
            echo "Tabel kosong, lewati proses transfer baris.\n";
            continue;
        }

        // 4. Migrasi data menggunakan chunking
        $offset = 0;
        while ($offset < $totalRows) {
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
    echo "Error Migrasi: " . $e->getMessage() . "\n";
}