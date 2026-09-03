<?php

/**
 * init_migration.php
 *
 * Mengosongkan (TRUNCATE) seluruh tabel di database target (db_ybaik_new)
 * secara dinamis dengan membaca metadata dari information_schema.tables.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$targetDb;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "====================================================================\n";
    echo "       MEMULAI RESET (TRUNCATE) SELURUH TABEL $targetDb\n";
    echo "====================================================================\n\n";

    // 1. Nonaktifkan foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 2. Ambil seluruh nama tabel dasar (BASE TABLE) di database target
    $stmt = $pdo->prepare("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = :db 
          AND table_type = 'BASE TABLE'
        ORDER BY table_name ASC
    ");
    $stmt->execute([':db' => $targetDb]);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "Tidak ditemukan tabel pada database `$targetDb`.\n";
    } else {
        $count = 0;
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `$targetDb`.`$table`;");
            $count++;
            echo "-> Truncated: `$table`\n";
        }
        echo "\nBerhasil me-reset (TRUNCATE) sebanyak $count tabel di `$targetDb`.\n";
    }

    // 3. Aktifkan kembali foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "====================================================================\n";
    echo "       DATABASE $targetDb BERSIH DAN SIAP DIMIGRASI!\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
    echo "Error saat inisialisasi/truncate: " . $e->getMessage() . "\n";
    exit(1);
}