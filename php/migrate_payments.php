<?php
// migrate_payments.php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    // Koneksi PDO dengan mendatabase-kan target langsung ke DSN untuk menghindari error 1046
    $pdo = new PDO("mysql:host=$host;dbname=$targetDb;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Nonaktifkan Foreign Key Checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Memulai migrasi grup tabel payments...\n\n";

    // Kosongkan tabel tujuan dengan urutan dari anak/terkait ke induk
    $pdo->exec("TRUNCATE TABLE `students_has_payments`");
    $pdo->exec("TRUNCATE TABLE `payment_receipts`");
    $pdo->exec("TRUNCATE TABLE `payment_details`");
    $pdo->exec("TRUNCATE TABLE `payments`");
    $pdo->exec("TRUNCATE TABLE `student_payment_types`");

    // ==========================================
    // 1. student_payment_types (prasyarat)
    // ==========================================
    echo "1. Migrasi student_payment_types...\n";
    $migrateTypes = "
        INSERT INTO `student_payment_types` (
            `id`,
            `name`,
            `need_enrollment`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `name`,
            `need_enrollment`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`student_payment_types`
    ";
    $affectedTypes = $pdo->exec($migrateTypes);
    echo "   -> Berhasil dimigrasi : $affectedTypes baris.\n\n";

    // ==========================================
    // 2. payments (dari student_payments)
    // ==========================================
    echo "2. Migrasi payments...\n";
    $migratePayments = "
        INSERT INTO `payments` (
            `id`,
            `student_payment_type_id`,
            `invoice_number`,
            `jumlah_nominal`,
            `jatuh_tempo`,
            `keterangan`,
            `jenis`,
            `package`,
            `status`,
            `filename`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `student_payment_type_id`,
            `invoice_number`,
            `jumlah_nominal`,
            `jatuh_tempo`,
            `keterangan`,
            `jenis`,
            `package`,
            `status`,
            `filename`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`student_payments`
    ";
    $affectedPayments = $pdo->exec($migratePayments);

    $totalPaymentsStmt = $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_payments`");
    $totalPayments = (int) $totalPaymentsStmt->fetch()['c'];
    $skippedPayments = $totalPayments - $affectedPayments;

    echo "   -> Total di sumber : $totalPayments\n";
    echo "   -> Berhasil dimigrasi : $affectedPayments\n";
    echo "   -> Di-skip : $skippedPayments\n\n";

    // ==========================================
    // 3. payment_details (gabungan student_payment_details + student_payment_receipts)
    // ==========================================
    echo "3. Migrasi payment_details (gabung dengan data receipt)...\n";

    // Buat temporary table untuk mengambil receipt "terbaru" (MAX id) per payment_detail_id
    $pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_latest_receipt");
    $pdo->exec("
        CREATE TEMPORARY TABLE tmp_latest_receipt AS
        SELECT r1.*
        FROM `$sourceDb`.`student_payment_receipts` r1
        INNER JOIN (
            SELECT `payment_detail_id`, MAX(`id`) AS max_id
            FROM `$sourceDb`.`student_payment_receipts`
            WHERE `payment_detail_id` IS NOT NULL
            GROUP BY `payment_detail_id`
        ) latest ON latest.`payment_detail_id` = r1.`payment_detail_id` AND latest.max_id = r1.`id`
    ");

    $migrateDetails = "
        INSERT INTO `payment_details` (
            `id`,
            `payment_id`,
            `jatuh_tempo`,
            `nominal`,
            `tanggal_pembayaran`,
            `status_pembayaran`,
            `status_verifikasi`,
            `status_by`,
            `filename`,
            `keterangan`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            spd.`id`,
            spd.`payment_id`,
            spd.`jatuh_tempo`,
            spd.`nominal`,
            r.`tanggal_pembayaran`,
            CASE
                WHEN spd.`status` IN ('not_paid','partially_paid','paid','paid_late','refunded')
                    THEN spd.`status`
                ELSE 'not_paid'
            END,
            CASE
                WHEN r.`status` IN ('verified','rejected') THEN r.`status`
                ELSE NULL
            END,
            r.`status_by`,
            r.`filename`,
            r.`keterangan`,
            spd.`created_at`,
            spd.`updated_at`,
            spd.`deleted_at`
        FROM `$sourceDb`.`student_payment_details` spd
        LEFT JOIN tmp_latest_receipt r ON r.`payment_detail_id` = spd.`id`
        WHERE spd.`payment_id` IS NOT NULL
    ";
    $affectedDetails = $pdo->exec($migrateDetails);

    $totalDetailsStmt = $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_payment_details`");
    $totalDetails = (int) $totalDetailsStmt->fetch()['c'];
    $skippedDetails = $totalDetails - $affectedDetails;

    echo "   -> Total di sumber : $totalDetails\n";
    echo "   -> Berhasil dimigrasi : $affectedDetails\n";
    echo "   -> Di-skip (payment_id NULL) : $skippedDetails\n\n";

    // ==========================================
    // 4. payment_receipts (dari student_payment_receipts)
    // ==========================================
    echo "4. Migrasi payment_receipts...\n";
    $migrateReceipts = "
        INSERT INTO `payment_receipts` (
            `id`,
            `payment_detail_id`,
            `nominal`,
            `filename`,
            `keterangan`,
            `tanggal_pembayaran`,
            `status`,
            `status_by`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT
            `id`,
            `payment_detail_id`,
            `nominal`,
            `filename`,
            `keterangan`,
            `tanggal_pembayaran`,
            `status`,
            `status_by`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        FROM `$sourceDb`.`student_payment_receipts`
        WHERE `payment_detail_id` IS NOT NULL
    ";
    $affectedReceipts = $pdo->exec($migrateReceipts);

    $totalReceiptsStmt = $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`student_payment_receipts`");
    $totalReceipts = (int) $totalReceiptsStmt->fetch()['c'];
    $skippedReceipts = $totalReceipts - $affectedReceipts;

    echo "   -> Total di sumber : $totalReceipts\n";
    echo "   -> Berhasil dimigrasi : $affectedReceipts\n";
    echo "   -> Di-skip (payment_detail_id NULL) : $skippedReceipts\n\n";

    // ==========================================
    // 5. students_has_payments (dari student_student_payment)
    // ==========================================
    echo "5. Migrasi students_has_payments...\n";
    $migratePivot = "
        INSERT INTO `students_has_payments` (
            `id`,
            `student_id`,
            `payment_id`
        )
        SELECT
            `id`,
            `student_id`,
            `student_payment_id`
        FROM `$sourceDb`.`student_student_payment`
    ";
    $affectedPivot = $pdo->exec($migratePivot);
    echo "   -> Berhasil dimigrasi : $affectedPivot baris.\n\n";

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== Migrasi grup payments selesai sepenuhnya ===\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}