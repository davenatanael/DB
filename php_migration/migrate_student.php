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

    echo "Memulai migrasi tabel students...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`students`");

    // Query migrasi: Menghubungkan students lama dengan customers lama untuk mendapatkan user_id
    $migrateSql = "
        INSERT INTO `$targetDb`.`students` (
            `id`,
            `user_id`,
            `chinese_name`,
            `nama_ayah`,
            `ayah_phone_code`,
            `ayah_phone`,
            `email_ayah`,
            `pekerjaan_ayah`,
            `kantor_ayah`,
            `nama_ibu`,
            `ibu_phone_code`,
            `ibu_phone`,
            `email_ibu`,
            `pekerjaan_ibu`,
            `kantor_ibu`,
            `jenjang`,
            `level`,
            `school_major`,
            `city_id_origin`,
            `address_origin`,
            `postal_code_origin`,
            `city_id_current`,
            `address_current`,
            `postal_code_current`,
            `note`,
            `gender`,
            `religion`,
            `tanggal_berangkat`,
            `tanggal_keberangkatan`,
            `pass_id_number`,
            `jenis_identitas`,
            `expired_passport`,
            `tempat_lahir`,
            `tanggal_lahir`,
            `graduation_time`,
            `average_score`,
            `test_selesai`,
            `test_detail`,
            `sponsor_status`,
            `nama_sponsor`,
            `perusahaan_sponsor`,
            `jabatan_sponsor`,
            `bidang_usaha_sponsor`,
            `alamat_usaha_sponsor`,
            `email_sponsor`,
            `hubungan_sponsor`,
            `status_siswa`,
            `keterangan_status`,
            `payment_completion_status`,
            `is_new_student`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            s.`id`,
            c.`user_id`,
            s.`chinese_name`,
            s.`nama_ayah`,
            s.`ayah_phone_code`,
            s.`ayah_phone`,
            s.`email_ayah`,
            s.`pekerjaan_ayah`,
            s.`kantor_ayah`,
            s.`nama_ibu`,
            s.`ibu_phone_code`,
            s.`ibu_phone`,
            s.`email_ibu`,
            s.`pekerjaan_ibu`,
            s.`kantor_ibu`,
            s.`jenjang`,
            s.`level`,
            s.`school_major`,
            s.`city_id_origin`,
            s.`address_origin`,
            s.`postal_code_origin`,
            s.`city_id_current`,
            s.`address_current`,
            s.`postal_code_current`,
            s.`note`,
            s.`gender`,
            s.`religion`,
            s.`tanggal_berangkat`,
            s.`tanggal_keberangkatan`,
            s.`pass_id_number`,
            s.`jenis_identitas`,
            s.`expired_passport`,
            s.`tempat_lahir`,
            s.`tanggal_lahir`,
            s.`graduation_time`,
            s.`average_score`,
            s.`test_selesai`,
            s.`test_detail`,
            s.`sponsor_status`,
            s.`nama_sponsor`,
            s.`perusahaan_sponsor`,
            s.`jabatan_sponsor`,
            s.`bidang_usaha_sponsor`,
            s.`alamat_usaha_sponsor`,
            s.`email_sponsor`,
            s.`hubungan_sponsor`,
            s.`status_siswa`,
            s.`keterangan_status`,
            s.`payment_completion_status`,
            COALESCE(s.`is_new_student`, 1),
            s.`created_at`,
            s.`updated_at`,
            s.`deleted_at`
        FROM `$sourceDb`.`students` s
        INNER JOIN `$sourceDb`.`customers` c ON s.`customer_id` = c.`id`
        WHERE c.`user_id` IS NOT NULL
    ";

    $affectedRows = $pdo->exec($migrateSql);

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Migrasi sukses! Sebanyak $affectedRows data student berhasil dipindahkan dengan mapping user_id ke $targetDb.students.\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
