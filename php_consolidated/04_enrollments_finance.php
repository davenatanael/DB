<?php

/**
 * 04_enrollments_finance.php
 *
 * MODUL 4: PENDAFTARAN (ENROLLMENTS), KEBERANGKATAN, KEUANGAN, DAN KOMISI
 * =============================================================================
 *
 * ALUR MIGRASI PADA FILE INI (Berurutan untuk menjamin integritas Foreign Key):
 *
 * 1. enrollments:
 *    Migrasi pendaftaran universitas siswa dari `student_programs` (student_id -> students_id).
 *
 * 2. enrollment_programs:
 *    Pilihan program studi dalam pendaftaran siswa dari `student_program_details`.
 *
 * 3. enrollment_scholarships:
 *    Pengajuan beasiswa pendaftaran dari `enrollment_scholarships`.
 *
 * 4. enrollment_timelines & 5. enrollment_timeline_media:
 *    Pembaruan lini masa proses pendaftaran siswa dan berkas bukti/media lampiran.
 *
 * 6. student_enrollment_documents & 7. student_enrollment_document_programs:
 *    Dokumen pendaftaran per universitas dan per program studi.
 *
 * 8. enrollment_examinations:
 *    Jadwal dan hasil ujian pendaftaran (tes tertulis, wawancara, dsb.).
 *
 * 9. departure:
 *    Informasi jadwal keberangkatan mahasiswa ke negara/kampus tujuan.
 *
 * 10. student_payment_types -> 11. payments -> 12. payment_details -> 13. payment_receipts:
 *     Ekosistem pembayaran siswa:
 *     - Master jenis tagihan (student_payment_types).
 *     - Master invoice tagihan siswa (payments, asal: student_payments).
 *     - Rincian termin tagihan (payment_details, asal: student_payment_details digabung receipt).
 *     - Bukti pembayaran (payment_receipts, asal: student_payment_receipts).
 *
 * 14. students_has_payments:
 *     Relasi many-to-many siswa dan tagihan (asal: student_student_payment).
 *
 * 15. payment_student_programs:
 *     Relasi pembayaran tagihan ke program pendaftaran siswa.
 *
 * 16. student_payment_discounts:
 *     Potongan harga/diskon pada tagihan pembayaran siswa.
 *
 * 17. commissions -> 18. commission_details:
 *     Komisi agen/rekanan dari `commissions` dan rincian penerima komisi pada `commission_details`.
 *     ID penerima lama (recipient_id) dipetakan ke ID pengguna baru (`user_id` di tabel users)
 *     sesuai peran masing-masing (consultant, koordinator, korwil, student, school).
 */

require_once __DIR__ . '/db.php';

$sourceDb = MigrationDB::SOURCE_DB;
$targetDb = MigrationDB::TARGET_DB;
$pdo      = MigrationDB::getConnection();

MigrationDB::logHeader("MODUL 4: PENDAFTARAN, KEBERANGKATAN, KEUANGAN & KOMISI");
$startTime = microtime(true);

try {
    MigrationDB::disableForeignKeyChecks($pdo);

    // =========================================================================
    // TAHAP 1: ENROLLMENTS & ENROLLMENT PROGRAMS
    // =========================================================================
    MigrationDB::logSection("1/11", "Memigrasi Enrollments (student_programs)");
    MigrationDB::truncate($pdo, 'enrollment_programs');
    MigrationDB::truncate($pdo, 'enrollments');

    $sqlEnrollments = "
        INSERT INTO `$targetDb`.`enrollments` (
            `id`, `students_id`, `email`, `password`, `registration_id`, `university_id`,
            `priorities_order`, `status`, `uni_status`, `locked_at`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            sp.`id`, sp.`student_id`, sp.`email`, sp.`password`, sp.`registration_id`, sp.`university_id`,
            sp.`priorities_order`, sp.`status`, sp.`uni_status`, sp.`locked_at`,
            sp.`created_at`, sp.`updated_at`, sp.`deleted_at`
        FROM `$sourceDb`.`student_programs` sp
        INNER JOIN `$targetDb`.`students` st ON sp.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`universities` u ON sp.`university_id` = u.`id`
    ";
    $affEnr = $pdo->exec($sqlEnrollments);
    MigrationDB::logResult('enrollments', $affEnr);

    MigrationDB::logSection("2/11", "Memigrasi Enrollment Programs (student_program_details)");
    $sqlEnrProg = "
        INSERT INTO `$targetDb`.`enrollment_programs` (
            `id`, `student_program_id`, `program_id`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            spd.`id`, spd.`student_program_id`, spd.`program_id`,
            spd.`created_at`, spd.`updated_at`, spd.`deleted_at`
        FROM `$sourceDb`.`student_program_details` spd
        INNER JOIN `$targetDb`.`enrollments` e ON spd.`student_program_id` = e.`id`
        INNER JOIN `$targetDb`.`univ_programs` up ON spd.`program_id` = up.`id`
    ";
    $affEnrProg = $pdo->exec($sqlEnrProg);
    MigrationDB::logResult('enrollment_programs', $affEnrProg);

    // =========================================================================
    // TAHAP 2: BEASISWA, TIMELINE & MEDIA
    // =========================================================================
    MigrationDB::logSection("3/11", "Memigrasi Enrollment Scholarships");
    MigrationDB::truncate($pdo, 'enrollment_scholarships');
    $sqlEnrSch = "
        INSERT INTO `$targetDb`.`enrollment_scholarships` (
            `id`, `student_program_id`, `category`, `nominal_tuition_fee`, `nominal_accomodation`,
            `nominal_stipend`, `nominal_tuition_fee_percentage`, `nominal_accomodation_percentage`,
            `created_at`, `updated_at`
        )
        SELECT 
            es.`id`, es.`student_program_id`, es.`category`, es.`nominal_tuition_fee`,
            es.`nominal_accomodation`, es.`nominal_stipend`, es.`nominal_tuition_fee_percentage`,
            es.`nominal_accomodation_percentage`, es.`created_at`, es.`updated_at`
        FROM `$sourceDb`.`enrollment_scholarships` es
        LEFT JOIN `$targetDb`.`enrollments` e ON es.`student_program_id` = e.`id`
        WHERE es.`student_program_id` IS NULL OR e.`id` IS NOT NULL
    ";
    $affEnrSch = $pdo->exec($sqlEnrSch);
    MigrationDB::logResult('enrollment_scholarships', $affEnrSch);

    MigrationDB::logSection("4/11", "Memigrasi Enrollment Timelines & Timeline Media");
    MigrationDB::truncate($pdo, 'enrollment_timeline_media');
    MigrationDB::truncate($pdo, 'enrollment_timelines');

    $sqlTimeline = "
        INSERT INTO `$targetDb`.`enrollment_timelines` (
            `id`, `student_program_id`, `type`, `is_public`, `title`, `content`,
            `created_by`, `reminder_sent_at`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            et.`id`, et.`student_program_id`, et.`type`, et.`is_public`, et.`title`, et.`content`,
            et.`created_by`, et.`reminder_sent_at`, et.`created_at`, et.`updated_at`, NULL
        FROM `$sourceDb`.`enrollment_timelines` et
        LEFT JOIN `$targetDb`.`enrollments` e ON et.`student_program_id` = e.`id`
        WHERE et.`student_program_id` IS NULL OR e.`id` IS NOT NULL
    ";
    $affTimeline = $pdo->exec($sqlTimeline);

    $sqlMedia = "
        INSERT INTO `$targetDb`.`enrollment_timeline_media` (
            `id`, `enrollment_timeline_id`, `file_path`, `file_type`, `caption`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            etm.`id`, etm.`enrollment_timeline_id`, etm.`file_path`, etm.`file_type`, etm.`caption`,
            etm.`created_at`, etm.`updated_at`, NULL
        FROM `$sourceDb`.`enrollment_timeline_media` etm
        INNER JOIN `$targetDb`.`enrollment_timelines` et ON etm.`enrollment_timeline_id` = et.`id`
    ";
    $affMedia = $pdo->exec($sqlMedia);
    echo "   -> Sukses: timelines=$affTimeline, media=$affMedia baris.\n\n";

    // =========================================================================
    // TAHAP 3: DOKUMEN PENDAFTARAN & UJIAN
    // =========================================================================
    MigrationDB::logSection("5/11", "Memigrasi Dokumen Pendaftaran (student_enrollment_documents & programs)");
    MigrationDB::truncate($pdo, 'student_enrollment_document_programs');
    MigrationDB::truncate($pdo, 'student_enrollment_documents');

    $sqlEnrDocs = "
        INSERT INTO `$targetDb`.`student_enrollment_documents` (
            `id`, `university_id`, `filename`, `path`, `content`, `status`,
            `verified_note`, `verified_by`, `verified_at`, `category`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            sed.`id`, sed.`university_id`, sed.`filename`, sed.`path`, sed.`content`, sed.`status`,
            sed.`verified_note`, sed.`verified_by`, sed.`verified_at`, sed.`category`,
            sed.`created_at`, sed.`updated_at`, sed.`deleted_at`
        FROM `$sourceDb`.`student_enrollment_documents` sed
        INNER JOIN `$targetDb`.`universities` u ON sed.`university_id` = u.`id`
    ";
    $affEnrDocs = $pdo->exec($sqlEnrDocs);

    $sqlEnrDocProg = "
        INSERT INTO `$targetDb`.`student_enrollment_document_programs` (
            `id`, `student_enrollment_document_id`, `student_program_id`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            sedp.`id`, sedp.`student_enrollment_document_id`, sedp.`student_program_id`,
            sedp.`created_at`, sedp.`updated_at`, NULL
        FROM `$sourceDb`.`student_enrollment_document_programs` sedp
        INNER JOIN `$targetDb`.`student_enrollment_documents` sed ON sedp.`student_enrollment_document_id` = sed.`id`
    ";
    $affEnrDocProg = $pdo->exec($sqlEnrDocProg);
    echo "   -> Sukses: docs=$affEnrDocs, doc_programs=$affEnrDocProg baris.\n\n";

    MigrationDB::logSection("6/11", "Memigrasi Ujian Pendaftaran (enrollment_examinations)");
    MigrationDB::truncate($pdo, 'enrollment_examinations');
    $sqlExaminations = "
        INSERT INTO `$targetDb`.`enrollment_examinations` (
            `id`, `student_program_id`, `type`, `status`, `notes`, `exam_date`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            ee.`id`,
            COALESCE(ee.`student_program_id`, eesp.`student_program_id`),
            COALESCE(NULLIF(TRIM(ee.`type`), ''), 'written_test'),
            COALESCE(NULLIF(TRIM(ee.`status`), ''), 'published'),
            NULLIF(ee.`notes`, 'null'),
            COALESCE(ee.`exam_date`, CURDATE()),
            COALESCE(ee.`created_at`, NOW()),
            COALESCE(ee.`updated_at`, NOW()),
            NULLIF(ee.`deleted_at`, '0000-00-00 00:00:00')
        FROM `$sourceDb`.`enrollment_examinations` ee
        LEFT JOIN `$sourceDb`.`enrollment_examination_student_programs` eesp 
            ON ee.`id` = eesp.`enrollment_examination_id`
    ";
    $affExam = $pdo->exec($sqlExaminations);
    MigrationDB::logResult('enrollment_examinations', $affExam);

    // =========================================================================
    // TAHAP 4: DEPARTURE (KEBERANGKATAN)
    // =========================================================================
    MigrationDB::logSection("7/11", "Memigrasi Data Departure (Keberangkatan)");
    MigrationDB::truncate($pdo, 'departure');
    $sqlDeparture = "
        INSERT INTO `$targetDb`.`departure` (
            `id`, `student_id`, `student_program_id`, `student_program_detail_id`,
            `univ_program_id`, `enrollment_scholarship_id`, `package_category`,
            `depart`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            d.`id`, d.`student_id`, d.`student_program_id`, d.`student_program_detail_id`,
            d.`univ_program_id`, d.`enrollment_scholarship_id`, d.`package_category`,
            d.`depart`, d.`created_at`, d.`updated_at`, d.`deleted_at`
        FROM `$sourceDb`.`departure` d
    ";
    $affDep = $pdo->exec($sqlDeparture);
    MigrationDB::logResult('departure', $affDep);

    // =========================================================================
    // TAHAP 5: PAYMENTS, DETAILS & RECEIPTS
    // =========================================================================
    MigrationDB::logSection("8/11", "Memigrasi Ekosistem Payments (Types, Payments, Details, Receipts)");
    MigrationDB::truncate($pdo, 'payment_student_programs');
    MigrationDB::truncate($pdo, 'students_has_payments');
    MigrationDB::truncate($pdo, 'student_payment_discounts');
    MigrationDB::truncate($pdo, 'payment_receipts');
    MigrationDB::truncate($pdo, 'payment_details');
    MigrationDB::truncate($pdo, 'payments');
    MigrationDB::truncate($pdo, 'student_payment_types');

    // 8a. Types
    $sqlPaymentTypes = "
        INSERT INTO `$targetDb`.`student_payment_types` (`id`, `name`, `need_enrollment`, `created_at`, `updated_at`, `deleted_at`)
        SELECT `id`, `name`, `need_enrollment`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`student_payment_types`
    ";
    $affPayTypes = $pdo->exec($sqlPaymentTypes);

    // 8b. Payments
    $pdo->exec("ALTER TABLE `$targetDb`.`payments` MODIFY COLUMN `student_payment_type_id` BIGINT UNSIGNED NULL");
    $sqlPayments = "
        INSERT INTO `$targetDb`.`payments` (
            `id`, `student_payment_type_id`, `invoice_number`, `jumlah_nominal`, `jatuh_tempo`,
            `keterangan`, `jenis`, `package`, `status`, `filename`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `student_payment_type_id`, `invoice_number`, `jumlah_nominal`, `jatuh_tempo`,
            `keterangan`, `jenis`, `package`, `status`, `filename`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`student_payments`
    ";
    $affPayments = $pdo->exec($sqlPayments);

    // 8c. Payment Details (dengan data receipt terbaru)
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

    $sqlPayDetails = "
        INSERT INTO `$targetDb`.`payment_details` (
            `id`, `payment_id`, `jatuh_tempo`, `nominal`, `tanggal_pembayaran`,
            `status_pembayaran`, `status_verifikasi`, `status_by`, `filename`,
            `keterangan`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            spd.`id`, spd.`payment_id`, spd.`jatuh_tempo`, spd.`nominal`, r.`tanggal_pembayaran`,
            CASE 
                WHEN spd.`status` IN ('not_paid','partially_paid','paid','paid_late','refunded') THEN spd.`status`
                ELSE 'not_paid'
            END,
            CASE 
                WHEN r.`status` IN ('verified','rejected') THEN r.`status`
                ELSE NULL
            END,
            r.`status_by`, r.`filename`, r.`keterangan`,
            spd.`created_at`, spd.`updated_at`, spd.`deleted_at`
        FROM `$sourceDb`.`student_payment_details` spd
        LEFT JOIN tmp_latest_receipt r ON r.`payment_detail_id` = spd.`id`
        WHERE spd.`payment_id` IS NOT NULL
    ";
    $affPayDetails = $pdo->exec($sqlPayDetails);

    // 8d. Payment Receipts
    $sqlPayReceipts = "
        INSERT INTO `$targetDb`.`payment_receipts` (
            `id`, `payment_detail_id`, `nominal`, `filename`, `keterangan`,
            `tanggal_pembayaran`, `status`, `status_by`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `payment_detail_id`, `nominal`, `filename`, `keterangan`,
            `tanggal_pembayaran`, `status`, `status_by`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`student_payment_receipts`
        WHERE `payment_detail_id` IS NOT NULL
    ";
    $affPayReceipts = $pdo->exec($sqlPayReceipts);
    echo "   -> Sukses: types=$affPayTypes, payments=$affPayments, details=$affPayDetails, receipts=$affPayReceipts baris.\n\n";

    // =========================================================================
    // TAHAP 6: RELASI PEMBAYARAN SISWA (PIVOT, PROGRAMS, DISCOUNTS)
    // =========================================================================
    MigrationDB::logSection("9/11", "Memigrasi Relasi Tagihan Siswa (students_has_payments, programs, discounts)");

    // 9a. students_has_payments
    $sqlShp = "
        INSERT INTO `$targetDb`.`students_has_payments` (`id`, `student_id`, `payment_id`)
        SELECT ssp.`id`, ssp.`student_id`, ssp.`student_payment_id`
        FROM `$sourceDb`.`student_student_payment` ssp
        INNER JOIN `$targetDb`.`students` st ON ssp.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`payments` p ON ssp.`student_payment_id` = p.`id`
    ";
    $affShp = $pdo->exec($sqlShp);

    // 9b. payment_student_programs
    $sqlPsp = "
        INSERT INTO `$targetDb`.`payment_student_programs` (
            `id`, `payment_id`, `student_program_id`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            psp.`id`, psp.`payment_id`, psp.`student_program_id`,
            COALESCE(psp.`created_at`, NOW()), COALESCE(psp.`updated_at`, NOW()), NULL
        FROM `$sourceDb`.`payment_student_programs` psp
        INNER JOIN `$targetDb`.`payments` p ON psp.`payment_id` = p.`id`
    ";
    $affPsp = $pdo->exec($sqlPsp);

    // 9c. student_payment_discounts
    $affDiscounts = 0;
    $hasDiscTable = $pdo->query("SHOW TABLES FROM `$sourceDb` LIKE 'student_payment_discounts'")->fetch();
    if ($hasDiscTable) {
        $sqlDiscounts = "
            INSERT INTO `$targetDb`.`student_payment_discounts` (`id`, `title`, `description`, `nominal`, `student_payment_id`)
            SELECT spd.`id`, spd.`title`, spd.`description`, spd.`nominal`, spd.`student_payment_id`
            FROM `$sourceDb`.`student_payment_discounts` spd
            INNER JOIN `$targetDb`.`payments` p ON spd.`student_payment_id` = p.`id`
        ";
        $affDiscounts = $pdo->exec($sqlDiscounts);
    }
    echo "   -> Sukses: student_payments=$affShp, payment_programs=$affPsp, discounts=$affDiscounts baris.\n\n";

    // =========================================================================
    // TAHAP 7: COMMISSIONS & DETAILS
    // =========================================================================
    MigrationDB::logSection("10/11", "Memigrasi Master Commissions");
    MigrationDB::truncate($pdo, 'commission_details');
    MigrationDB::truncate($pdo, 'commissions');

    $sqlCommissions = "
        INSERT INTO `$targetDb`.`commissions` (
            `id`, `user_id`, `total_amount`, `status`, `tanggal_keberangkatan`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            co.`id`, cu.`user_id`, co.`total_amount`, co.`status`, co.`tanggal_keberangkatan`,
            co.`created_at`, co.`updated_at`, co.`deleted_at`
        FROM `$sourceDb`.`commissions` co
        INNER JOIN `$sourceDb`.`customers` cu ON co.`customer_id` = cu.`id`
        WHERE cu.`user_id` IS NOT NULL
    ";
    $affCom = $pdo->exec($sqlCommissions);
    MigrationDB::logResult('commissions', $affCom);

    MigrationDB::logSection("11/11", "Memigrasi Commission Details (Resolusi user_id penerima)");
    $sqlCommDetails = "
        INSERT INTO `$targetDb`.`commission_details` (
            `id`, `commission_id`, `recipient_type`, `user_id`, `name`, `amount`,
            `status`, `paid_at`, `is_approved`, `level`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            cd.`id`,
            cd.`commission_id`,
            cd.`recipient_type`,
            CASE cd.`recipient_type`
                WHEN 'consultant' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'senior_consultant' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'referral' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'koordinator' THEN kd.`user_id`
                WHEN 'korwil' THEN kw.`user_id`
                WHEN 'student' THEN st_cust.`user_id`
                WHEN 'school' THEN sch_cs.`user_id`
                ELSE NULL
            END AS `user_id`,
            cd.`name`,
            cd.`amount`,
            cd.`status`,
            cd.`paid_at`,
            cd.`is_approved`,
            cd.`level`,
            cd.`created_at`,
            cd.`updated_at`,
            cd.`deleted_at`
        FROM `$sourceDb`.`commission_details` cd
        LEFT JOIN `$sourceDb`.`consultants` cs ON cs.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`customers` cust ON cust.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`koordinators` kd ON kd.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`korwils` kw ON kw.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`students` st ON st.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`customers` st_cust ON st_cust.`id` = st.`customer_id`
        LEFT JOIN `$sourceDb`.`sekolah` sch ON sch.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`consultants` sch_cs ON sch_cs.`id` = sch.`consultant_id`
        INNER JOIN `$targetDb`.`commissions` comm ON comm.`id` = cd.`commission_id`
    ";
    $affCommDet = $pdo->exec($sqlCommDetails);
    MigrationDB::logResult('commission_details', $affCommDet);

    MigrationDB::enableForeignKeyChecks($pdo);

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "====================================================================\n";
    echo "  MODUL 4 SELESAI DENGAN SUKSES! Waktu eksekusi: $elapsed detik.\n";
    echo "====================================================================\n\n";

} catch (Exception $e) {
    MigrationDB::enableForeignKeyChecks($pdo);
    echo "\n[ERROR MODUL 4] " . $e->getMessage() . "\n";
    exit(1);
}
