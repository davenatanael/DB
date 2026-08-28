<?php

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

    // Nonaktifkan Foreign Key Checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi tabel student_education_backgrounds...\n";
    echo "(catatan: tabel ini FK ke students, jalankan SETELAH migrate_students.php)\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`student_education_backgrounds`");

    // student_id di skema baru NOT NULL (beda dari lama yang nullable), jadi
    // baris dengan student_id NULL otomatis di-skip lewat WHERE di bawah.
    $migrateSql = "
        INSERT INTO `$targetDb`.`student_education_backgrounds` (
            `id`,
            `student_id`,
            `jenjang`,
            `nama_sekolah`,
            `masuk`,
            `keluar`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `student_id`,
            `jenjang`,
            `nama_sekolah`,
            `masuk`,
            `keluar`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`student_education_backgrounds`
        WHERE `student_id` IS NOT NULL
    ";

    $affectedRows = $pdo->exec($migrateSql);

    $totalSourceStmt = $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_education_backgrounds`");
    $totalSource = (int) $totalSourceStmt->fetch()['c'];
    $skipped = $totalSource - $affectedRows;

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "-> Total di sumber : $totalSource\n";
    echo "-> Berhasil dimigrasi : $affectedRows\n";
    echo "-> Di-skip (student_id NULL) : $skipped\n";

    if ($skipped > 0) {
        echo "\nDetail baris yang di-skip:\n";
        $skipDetailStmt = $pdo->query("
            SELECT `id` FROM `$sourceDb`.`student_education_backgrounds` WHERE `student_id` IS NULL
        ");
        foreach ($skipDetailStmt->fetchAll() as $row) {
            echo "   - id={$row['id']} : student_id NULL\n";
        }
    }

    echo "\nMigrasi student_education_backgrounds selesai.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}