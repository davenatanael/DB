<?php

// ============================================================
// MIGRASI TABEL: consultations
// ============================================================
// Sumber : outclassco_marketing.consultations
// Tujuan : db_ybaik_new.consultations
//
// Catatan Struktur:
//   Struktur tabel lama dan baru IDENTIK:
//     id, guest_id, referrer, language, admission_type,
//     student_id, assigned_to, preferred_datetime, status,
//     consultation_type, admin_notes, meeting_summary,
//     created_at, updated_at, deleted_at
//
//   Perbedaan minor:
//     - DB baru menggunakan COLLATE utf8mb4_unicode_ci (hanya DDL)
//     - DB baru memiliki DEFAULT CURRENT_TIMESTAMP pada created_at/updated_at
//     - DB baru memiliki FK CONSTRAINT ke tabel guests (ON DELETE SET NULL)
//
//   PENTING:
//     Script ini HARUS dijalankan SETELAH migrate_guests.php selesai,
//     karena tabel consultations memiliki foreign key ke tabel guests.
//
//   Catatan data:
//     - Beberapa baris consultations memiliki guest_id = NULL
//       (konsultasi yang terhubung langsung ke student_id).
//       Ini valid dan akan dimigrasi apa adanya.
//     - Kolom assigned_to merujuk ke consultants (bukan FK resmi di skema).
//       Dipastikan data dimigrasi tanpa transformasi.
// ============================================================

$host     = '127.0.0.1';
$user     = 'root';
$pass     = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Nonaktifkan Foreign Key Checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi tabel consultations...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`consultations`");

    // Migrasi langsung: semua kolom identik antara lama dan baru.
    // Baris dengan guest_id = NULL diikutkan (konsultasi tanpa tamu terdaftar).
    // Pastikan tabel guests sudah dimigrasi terlebih dahulu.
    $migrateSql = "
        INSERT INTO `$targetDb`.`consultations` (
            `id`,
            `guest_id`,
            `referrer`,
            `language`,
            `admission_type`,
            `student_id`,
            `assigned_to`,
            `preferred_datetime`,
            `status`,
            `consultation_type`,
            `admin_notes`,
            `meeting_summary`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            c.`id`,
            -- Hanya ambil guest_id jika guest tersebut sudah ada di tabel baru
            -- (menghindari FK violation jika ada guest_id yg tidak ikut termigrasi)
            CASE
                WHEN c.`guest_id` IS NULL THEN NULL
                WHEN EXISTS (
                    SELECT 1 FROM `$targetDb`.`guests` g WHERE g.`id` = c.`guest_id`
                ) THEN c.`guest_id`
                ELSE NULL
            END AS `guest_id`,
            c.`referrer`,
            c.`language`,
            c.`admission_type`,
            c.`student_id`,
            c.`assigned_to`,
            c.`preferred_datetime`,
            c.`status`,
            c.`consultation_type`,
            c.`admin_notes`,
            c.`meeting_summary`,
            c.`created_at`,
            c.`updated_at`,
            c.`deleted_at`
        FROM `$sourceDb`.`consultations` c
    ";

    $affectedRows = $pdo->exec($migrateSql);

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Migrasi sukses! Sebanyak $affectedRows data consultations berhasil dipindahkan ke $targetDb.consultations.\n";

    // -- Laporan tambahan: cek apakah ada guest_id yang di-NULL-kan karena tidak ditemukan --
    $checkSql = "
        SELECT COUNT(*) AS total_orphan
        FROM `$sourceDb`.`consultations` c
        WHERE c.`guest_id` IS NOT NULL
          AND NOT EXISTS (
              SELECT 1 FROM `$targetDb`.`guests` g WHERE g.`id` = c.`guest_id`
          )
    ";
    $orphanCount = $pdo->query($checkSql)->fetchColumn();
    if ($orphanCount > 0) {
        echo "PERINGATAN: $orphanCount baris consultations memiliki guest_id yang tidak ditemukan di tabel guests baru.\n";
        echo "            guest_id tersebut telah di-set NULL pada data tujuan.\n";
    } else {
        echo "Validasi: Semua guest_id pada consultations berhasil ditemukan di tabel guests baru.\n";
    }

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
