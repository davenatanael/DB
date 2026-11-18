<?php
/**
 * Script Migrasi Data: Update `sekolah_id` pada tabel `students` di database `db_ybaik_new`
 * Sumber data: `outclassco_marketing.customers.school_id` melalui `outclassco_marketing.students.customer_id`
 *
 * Catatan Keamanan:
 * - SCRIPT INI SAMA SEKALI TIDAK MENGGUNAKAN TRUNCATE.
 * - HANYA MELAKUKAN UPDATE PADA KOLOM `sekolah_id` DI TABEL `students`.
 * - DATA LAIN TETAP AMAN DAN TIDAK AKAN TERHAPUS.
 */

// Konfigurasi Database Lokal
$host     = '127.0.0.1';
$port     = 3306;
$username = 'root';
$password = '';

$dbOld = 'outclassco_marketing';
$dbNew = 'db_ybaik_new';

echo "========================================================\n";
echo " MIGRATION: UPDATE sekolah_id PADA TABEL students\n";
echo " Tanpa Truncate - Hanya Update Kolom sekolah_id\n";
echo "========================================================\n\n";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "[1/4] Terhubung ke MySQL Server...\n";

    // 1. Cek apakah kolom sekolah_id ada di db_ybaik_new.students
    $checkCol = $pdo->query("
        SELECT COUNT(*) as cnt
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = '$dbNew'
          AND TABLE_NAME = 'students'
          AND COLUMN_NAME = 'sekolah_id'
    ")->fetch();

    if ($checkCol['cnt'] == 0) {
        echo "[INFO] Kolom `sekolah_id` belum ada di tabel `$dbNew.students`.\n";
        echo "       Menambahkan kolom `sekolah_id`...\n";
        $pdo->exec("
            ALTER TABLE `$dbNew`.`students`
            ADD COLUMN `sekolah_id` BIGINT UNSIGNED NULL AFTER `level`,
            ADD CONSTRAINT `fk_students_sekolah_id`
                FOREIGN KEY (`sekolah_id`) REFERENCES `$dbNew`.`sekolah` (`id`)
                ON DELETE SET NULL
        ");
        echo "       [SUKSES] Kolom `sekolah_id` berhasil ditambahkan!\n";
    } else {
        echo "[2/4] Kolom `sekolah_id` sudah ada di tabel `$dbNew.students`.\n";
    }

    // 2. Hitung jumlah data yang akan di-update
    $countStmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM `$dbNew`.`students` s
        JOIN `$dbOld`.`students` s_old ON s.id = s_old.id
        JOIN `$dbOld`.`customers` c ON s_old.customer_id = c.id
        JOIN `$dbNew`.`sekolah` sek ON c.school_id = sek.id
        WHERE c.school_id IS NOT NULL AND c.school_id > 0
    ");
    $totalEligible = $countStmt->fetch()['total'];

    echo "[3/4] Ditemukan $totalEligible data siswa yang memiliki `school_id` di database lama `$dbOld`.\n";

    if ($totalEligible == 0) {
        echo "[SELESAI] Tidak ada data yang perlu di-update.\n";
        exit;
    }

    // 3. Eksekusi UPDATE (Murni UPDATE, TANPA TRUNCATE)
    echo "       Sedang meng-update data sekolah_id ke `$dbNew.students`...\n";

    $updateSql = "
        UPDATE `$dbNew`.`students` s
        JOIN `$dbOld`.`students` s_old ON s.id = s_old.id
        JOIN `$dbOld`.`customers` c ON s_old.customer_id = c.id
        JOIN `$dbNew`.`sekolah` sek ON c.school_id = sek.id
        SET s.sekolah_id = c.school_id,
            s.updated_at = NOW()
        WHERE c.school_id IS NOT NULL AND c.school_id > 0
    ";

    $affected = $pdo->exec($updateSql);

    echo "[4/4] [BERHASIL] Sebanyak $affected baris student berhasil di-update dengan sekolah_id masing-masing!\n\n";

    // 4. Verifikasi sampel hasil
    echo "--- Sampel 5 Data Hasil Migrasi ---\n";
    $sampleStmt = $pdo->query("
        SELECT s.id as student_id, u.name as student_name, s.sekolah_id, sek.sekolah as nama_sekolah
        FROM `$dbNew`.`students` s
        LEFT JOIN `$dbNew`.`users` u ON s.user_id = u.id
        LEFT JOIN `$dbNew`.`sekolah` sek ON s.sekolah_id = sek.id
        WHERE s.sekolah_id IS NOT NULL
        ORDER BY s.id ASC
        LIMIT 5
    ");
    $samples = $sampleStmt->fetchAll();

    foreach ($samples as $row) {
        echo "ID: {$row['student_id']} | Nama: {$row['student_name']} | Sekolah ID: {$row['sekolah_id']} | Nama Sekolah: {$row['nama_sekolah']}\n";
    }

    echo "\n========================================================\n";
    echo " MIGRASI sekolah_id SELESAI DENGAN AMAN TANPA TRUNCATE!\n";
    echo "========================================================\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Terjadi kesalahan: " . $e->getMessage() . "\n";
}