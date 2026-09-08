<?php

/**
 * 05_operations.php
 *
 * MODUL 5: OPERASIONAL, PERCAKAPAN (CHAT), KARYAWAN, KONSULTASI, DAN NOTIFIKASI
 * =============================================================================
 *
 * ALUR MIGRASI PADA FILE INI (Berurutan untuk menjamin integritas Foreign Key):
 *
 * 1. chats & 2. chat_users & 3. chat_messages:
 *    Migrasi modul ruang obrolan, keanggotaan pengguna di grup/chat pribadi,
 *    dan rekaman pesan chat beserta lampiran.
 *
 * 4. employees & 5. employee_kinerjas & 6. employee_warnings:
 *    Data kepegawaian staf/karyawan:
 *    - Master profil karyawan (employees, rename updated_at -> update_at).
 *    - Evaluasi tabungan & kinerja karyawan (employee_kinerjas).
 *    - Surat peringatan kedisiplinan (employee_warnings).
 *
 * 7. guests:
 *    Buku tamu / calon siswa yang mengisi formulir prospek.
 *
 * 8. consultations:
 *    Jadwal sesi konsultasi calon siswa (FK ke guests dan students).
 *    Dijalankan setelah guests dan students selesai dimigrasikan.
 *
 * 9. notifications:
 *    Pemberitahuan sistem ke admin siswa (FK admin_student_id -> admin_students).
 */

require_once __DIR__ . '/db.php';

$sourceDb = MigrationDB::SOURCE_DB;
$targetDb = MigrationDB::TARGET_DB;
$pdo      = MigrationDB::getConnection();

MigrationDB::logHeader("MODUL 5: CHAT, KARYAWAN, BUKU TAMU, KONSULTASI & NOTIFIKASI");
$startTime = microtime(true);

try {
    MigrationDB::disableForeignKeyChecks($pdo);

    // =========================================================================
    // TAHAP 1: MODUL CHAT (CHATS, USERS, MESSAGES)
    // =========================================================================
    MigrationDB::logSection("1/4", "Memigrasi Modul Chat (chats, chat_users, chat_messages)");
    MigrationDB::truncate($pdo, 'chat_messages');
    MigrationDB::truncate($pdo, 'chat_users');
    MigrationDB::truncate($pdo, 'chats');

    // 1a. chats
    $sqlChats = "
        INSERT INTO `$targetDb`.`chats` (`id`, `title`, `created_at`, `updated_at`, `deleted_at`)
        SELECT `id`, `title`,
               COALESCE(`created_at`, NOW()), COALESCE(`updated_at`, NOW()), NULL AS `deleted_at`
        FROM `$sourceDb`.`chats`
    ";
    $affChats = $pdo->exec($sqlChats);

    // 1b. chat_users
    $sqlChatUsers = "
        INSERT INTO `$targetDb`.`chat_users` (`user_id`, `chat_id`, `created_at`, `updated_at`, `deleted_at`)
        SELECT cu.`user_id`, cu.`chat_id`,
               COALESCE(cu.`created_at`, NOW()), COALESCE(cu.`updated_at`, NOW()), NULL AS `deleted_at`
        FROM `$sourceDb`.`chat_users` cu
        INNER JOIN `$targetDb`.`users` u ON cu.`user_id` = u.`id`
        INNER JOIN `$targetDb`.`chats` c ON cu.`chat_id` = c.`id`
    ";
    $affChatUsers = $pdo->exec($sqlChatUsers);

    // 1c. chat_messages
    $sqlChatMessages = "
        INSERT INTO `$targetDb`.`chat_messages` (
            `id`, `chat_id`, `user_id`, `content`, `attachment_path`,
            `is_read`, `read_at`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            cm.`id`, cm.`chat_id`, cm.`user_id`, cm.`content`, cm.`attachment_path`,
            cm.`is_read`, cm.`read_at`,
            COALESCE(cm.`created_at`, NOW()), COALESCE(cm.`updated_at`, NOW()), cm.`deleted_at`
        FROM `$sourceDb`.`chat_messages` cm
        INNER JOIN `$targetDb`.`users` u ON cm.`user_id` = u.`id`
        INNER JOIN `$targetDb`.`chats` c ON cm.`chat_id` = c.`id`
    ";
    $affChatMsg = $pdo->exec($sqlChatMessages);
    echo "   -> Sukses: chats=$affChats, chat_users=$affChatUsers, chat_messages=$affChatMsg baris.\n\n";

    // =========================================================================
    // TAHAP 2: MODUL KARYAWAN (EMPLOYEES, KINERJAS, WARNINGS)
    // =========================================================================
    MigrationDB::logSection("2/4", "Memigrasi Modul Karyawan (employees, employee_kinerjas, employee_warnings)");
    MigrationDB::truncate($pdo, 'employee_warnings');
    MigrationDB::truncate($pdo, 'employee_kinerjas');
    MigrationDB::truncate($pdo, 'employees');

    $sqlEmp = "
        INSERT INTO `$targetDb`.`employees` (
            `id`, `id_number`, `employee_id_number`, `first_name`, `last_name`,
            `gender`, `place_of_birth`, `date_of_birth`, `main_address`, `alternate_address`,
            `email`, `corporate_email`, `phone_number`, `corporate_phone_number`,
            `marriage_status`, `total_child`, `start_work_date`, `position`, `work_status`,
            `photo`, `id_card_photo`, `resign_at`, `resign_reason`, `division_id`,
            `emergency_contact_name`, `emergency_contact_address`, `emergency_contact_phone`, `emergency_contact_relation`,
            `status`, `created_at`, `update_at`, `deleted_at`
        )
        SELECT 
            `id`, `id_number`, `employee_id_number`, `first_name`, `last_name`,
            `gender`, `place_of_birth`, `date_of_birth`, `main_address`, `alternate_address`,
            `email`, `corporate_email`, `phone_number`, `corporate_phone_number`,
            `marriage_status`, `total_child`, `start_work_date`, `position`, `work_status`,
            `photo`, `id_card_photo`, `resign_at`, `resign_reason`, `division_id`,
            `emergency_contact_name`, `emergency_contact_address`, `emergency_contact_phone`, `emergency_contact_relation`,
            `status`,
            NULLIF(`created_at`, '0000-00-00 00:00:00'),
            NULLIF(`updated_at`, '0000-00-00 00:00:00'),
            `deleted_at`
        FROM `$sourceDb`.`employees`
    ";
    $affEmp = $pdo->exec($sqlEmp);

    $sqlKin = "
        INSERT INTO `$targetDb`.`employee_kinerjas` (
            `id`, `employees_id`, `periode`, `nominal_tabungan`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT `id`, `employee_id`, `periode`, `nominal_tabungan`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`employee_kinerjas`
    ";
    $affKin = $pdo->exec($sqlKin);

    $sqlWarn = "
        INSERT INTO `$targetDb`.`employee_warnings` (
            `id`, `employees_id`, `level`, `year`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT `id`, `employee_id`, `level`, `year`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`employee_warnings`
    ";
    $affWarn = $pdo->exec($sqlWarn);
    echo "   -> Sukses: employees=$affEmp, kinerjas=$affKin, warnings=$affWarn baris.\n\n";

    // =========================================================================
    // TAHAP 3: BUKU TAMU & JADWAL KONSULTASI
    // =========================================================================
    MigrationDB::logSection("3/4", "Memigrasi Buku Tamu (guests) & Jadwal Konsultasi (consultations)");
    MigrationDB::truncate($pdo, 'consultations');
    MigrationDB::truncate($pdo, 'guests');

    $sqlGuests = "
        INSERT INTO `$targetDb`.`guests` (
            `id`, `name`, `email`, `phone`, `notes`,
            `converted_student_id`, `is_converted`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `name`, `email`, `phone`, `notes`,
            `converted_student_id`, `is_converted`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`guests`
    ";
    $affGuests = $pdo->exec($sqlGuests);

    $sqlConsultations = "
        INSERT INTO `$targetDb`.`consultations` (
            `id`, `guest_id`, `referrer`, `language`, `admission_type`,
            `student_id`, `assigned_to`, `preferred_datetime`, `status`,
            `consultation_type`, `admin_notes`, `meeting_summary`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`id`,
            CASE 
                WHEN c.`guest_id` IS NULL THEN NULL
                WHEN EXISTS (SELECT 1 FROM `$targetDb`.`guests` g WHERE g.`id` = c.`guest_id`) THEN c.`guest_id`
                ELSE NULL
            END,
            c.`referrer`, c.`language`, c.`admission_type`,
            c.`student_id`, c.`assigned_to`, c.`preferred_datetime`, c.`status`,
            c.`consultation_type`, c.`admin_notes`, c.`meeting_summary`,
            c.`created_at`, c.`updated_at`, c.`deleted_at`
        FROM `$sourceDb`.`consultations` c
    ";
    $affConsultations = $pdo->exec($sqlConsultations);
    echo "   -> Sukses: guests=$affGuests, consultations=$affConsultations baris.\n\n";

    // =========================================================================
    // TAHAP 4: NOTIFIKASI SISTEM
    // =========================================================================
    MigrationDB::logSection("4/4", "Memigrasi Notifikasi Sistem (notifications)");
    MigrationDB::truncate($pdo, 'notifications');

    $sqlNotifications = "
        INSERT INTO `$targetDb`.`notifications` (
            `id`, `admin_student_id`, `message`, `status`, `to`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            n.`id`, n.`admin_student_id`, n.`message`, COALESCE(n.`status`, 0), n.`to`,
            CASE 
                WHEN n.`created_at` IS NULL OR n.`created_at` = '0000-00-00 00:00:00' 
                THEN COALESCE(NULLIF(n.`updated_at`, '0000-00-00 00:00:00'), NOW())
                ELSE n.`created_at`
            END,
            CASE 
                WHEN n.`updated_at` IS NULL OR n.`updated_at` = '0000-00-00 00:00:00' 
                THEN NOW()
                ELSE n.`updated_at`
            END,
            NULLIF(n.`deleted_at`, '0000-00-00 00:00:00')
        FROM `$sourceDb`.`notifications` n
        INNER JOIN `$targetDb`.`admin_students` ast ON n.`admin_student_id` = ast.`id`
    ";
    $affNotifications = $pdo->exec($sqlNotifications);
    MigrationDB::logResult('notifications', $affNotifications);

    MigrationDB::enableForeignKeyChecks($pdo);

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "====================================================================\n";
    echo "  MODUL 5 SELESAI DENGAN SUKSES! Waktu eksekusi: $elapsed detik.\n";
    echo "====================================================================\n\n";

} catch (Exception $e) {
    MigrationDB::enableForeignKeyChecks($pdo);
    echo "\n[ERROR MODUL 5] " . $e->getMessage() . "\n";
    exit(1);
}
