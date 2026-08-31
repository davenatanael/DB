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

    // ==========================================
    // 1. MIGRASI DATA univ_categories
    // ==========================================
    echo "Memulai migrasi tabel univ_categories...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_categories`");

    $migrateCategoriesSql = "
        INSERT INTO `$targetDb`.`univ_categories` (
            `id`,
            `name`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `name`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`univ_categories`
    ";
    $affectedCategories = $pdo->exec($migrateCategoriesSql);
    echo "-> Sukses: $affectedCategories data pada tabel univ_categories berhasil dimigrasi.\n\n";

    // ==========================================
    // 2. MIGRASI DATA universities (tanpa customer_id)
    // ==========================================
    echo "Memulai migrasi tabel universities...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`universities`");

    $migrateUniversitiesSql = "
        INSERT INTO `$targetDb`.`universities` (
            `id`,
            `kode`,
            `nama_univ_china`,
            `register_link`,
            `nama_univ_international`,
            `photo`,
            `logo`,
            `cover`,
            `video_url`,
            `judul1`,
            `photo1`,
            `deskripsi1`,
            `judul2`,
            `photo2`,
            `deskripsi2`,
            `judul3`,
            `photo3`,
            `deskripsi3`,
            `judul4`,
            `deskripsi4`,
            `kategori`,
            `is_active`,
            `participation`,
            `kuota`,
            `kota`,
            `provinsi`,
            `country`,
            `edurank`,
            `url_edurank`,
            `usnews`,
            `url_usnews`,
            `times`,
            `url_times`,
            `qs`,
            `url_qs`,
            `shanghai_rank`,
            `url_shanghai_rank`,
            `jumlah_murid`,
            `atribut`,
            `level`,
            `type`,
            `living_expense`,
            `more_info`,
            `accomodation_description`,
            `status`,
            `t_status`,
            `admin_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            `id`,
            `kode`,
            `nama_univ_china`,
            `register_link`,
            `nama_univ_international`,
            `photo`,
            `logo`,
            `cover`,
            `video_url`,
            `judul1`,
            `photo1`,
            `deskripsi1`,
            `judul2`,
            `photo2`,
            `deskripsi2`,
            `judul3`,
            `photo3`,
            `deskripsi3`,
            `judul4`,
            `deskripsi4`,
            `kategori`,
            `is_active`,
            `participation`,
            `kuota`,
            `kota`,
            `provinsi`,
            `country`,
            `edurank`,
            `url_edurank`,
            `usnews`,
            `url_usnews`,
            `times`,
            `url_times`,
            `qs`,
            `url_qs`,
            `shanghai_rank`,
            `url_shanghai_rank`,
            `jumlah_murid`,
            `atribut`,
            `level`,
            `type`,
            `living_expense`,
            `more_info`,
            `accomodation_description`,
            `status`,
            `t_status`,
            `admin_id`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`universities`
    ";
    $affectedUniversities = $pdo->exec($migrateUniversitiesSql);
    echo "-> Sukses: $affectedUniversities data pada tabel universities berhasil dimigrasi (kolom customer_id diabaikan).\n\n";

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== Migrasi univ_categories dan universities Selesai ===\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
