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

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $pdo->exec("TRUNCATE TABLE `$targetDb`.`roles`");
    $pdo->exec("
        INSERT INTO `$targetDb`.`roles` (`id`, `name`) VALUES
        (1, 'Superadmin'),
        (2, 'Admin'),
        (3, 'Korwil'),
        (4, 'Koordinator'),
        (5, 'Consultant'),
        (6, 'Finance'),
        (7, 'School'),
        (8, 'Parent'),
        (9, 'Student')
    ");

    $pdo->exec("TRUNCATE TABLE `$targetDb`.`users`");

    $migrateSql = "
        INSERT INTO `$targetDb`.`users` (
            `id`,
            `role_id`,
            `bank_accounts_id`,
            `first_name`,
            `last_name`,
            `name`,
            `username`,
            `email`,
            `phone_code`,
            `phone`,
            `password`,
            `approval`,
            `plain_password`,
            `remember_token`,
            `referensi`,
            `referral_code`,
            `created_by`,
            `updated_by`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            u.`id`,
            CASE u.`role_id`
                WHEN 1 THEN 6 -- CATATAN: Role Lama 'Marketing' (1) saat ini di-map ke 'Finance' (6) di Role Baru. Mohon pastikan apakah ini sesuai.
                WHEN 2 THEN 2 -- Admin -> Admin
                WHEN 3 THEN 6 -- Accounting -> Finance
                WHEN 4 THEN 4 -- Koordinator -> Koordinator
                WHEN 5 THEN 5 -- Consultant -> Consultant
                WHEN 6 THEN 7 -- School -> School
                WHEN 7 THEN 9 -- Student -> Student
                WHEN 9 THEN 3 -- Korwil -> Korwil
                ELSE 9
            END AS `role_id`,
            NULL AS `bank_accounts_id`,
            COALESCE(u.`first_name`, u.`name`, '') AS `first_name`,
            u.`last_name`,
            u.`name`,
            u.`email` AS `username`,
            u.`email`,
            u.`phone_code`,
            u.`phone`,
            u.`password`,
            1 AS `approval`,
            NULL AS `plain_password`,
            NULL AS `remember_token`,
            c.`referensi`,
            SUBSTRING(c.`referral_code`, 1, 5) AS `referral_code`,
            c.`created_by`,
            c.`updated_by`,
            u.`created_at`,
            u.`updated_at`,
            u.`deleted_at`
        FROM `$sourceDb`.`users` u
        LEFT JOIN (
            SELECT 
                `user_id`,
                MAX(`referensi`) AS `referensi`,
                MAX(`referral_code`) AS `referral_code`,
                MAX(`created_by`) AS `created_by`,
                MAX(`updated_by`) AS `updated_by`
            FROM `$sourceDb`.`customers`
            WHERE `user_id` IS NOT NULL
            GROUP BY `user_id`
        ) c ON u.`id` = c.`user_id`
        WHERE u.`role_id` != 8 -- Mengabaikan Role 'University' (8)
    ";

    $affectedRows = $pdo->exec($migrateSql);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Migrasi sukses! Sebanyak $affectedRows data user berhasil dipindahkan beserta atribut customer lama ke $targetDb.users.";
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage();
}