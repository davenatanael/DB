<?php

/**
 * migrate_commission_details.php
 * 
 * Skrip migrasi data Commission Details dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 * 
 * PRASYARAT:
 * 1. Tabel `users` sudah dimigrasikan (FK ke users).
 * 2. Tabel `commissions` sudah dimigrasikan (FK ke commissions).
 * 
 * PERUBAHAN STRUKTUR & PEMETAAN:
 * - outclassco_marketing.commission_details menggunakan `recipient_id` yang merujuk ke ID tabel spesifik berdasarkan `recipient_type`:
 *     - 'consultant' / 'senior_consultant' / 'referral' -> outclassco_marketing.consultants.id -> consultants.user_id
 *     - 'koordinator'                                  -> outclassco_marketing.koordinators.id -> koordinators.user_id
 *     - 'korwil'                                       -> outclassco_marketing.korwils.id -> korwils.user_id
 *     - 'student'                                      -> outclassco_marketing.students.id -> customers.user_id
 *     - 'school'                                       -> outclassco_marketing.sekolah.id -> sekolah.consultant_id -> consultants.user_id
 * - db_ybaik_new.commission_details sekarang menggunakan kolom `user_id` (FK langsung ke db_ybaik_new.users.id).
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "====================================================================\n";
    echo "       MEMULAI MIGRASI DATA COMMISSION DETAILS                      \n";
    echo "====================================================================\n\n";

    // 1. Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`commission_details`");
    echo "-> Tabel `$targetDb`.`commission_details` berhasil dikosongkan (TRUNCATE).\n\n";

    // 2. Query migrasi dengan resolution user_id berdasarkan recipient_type
    $migrateSql = "
        INSERT INTO `$targetDb`.`commission_details` (
            `id`,
            `commission_id`,
            `recipient_type`,
            `user_id`,
            `name`,
            `amount`,
            `status`,
            `paid_at`,
            `is_approved`,
            `level`,
            `created_at`,
            `updated_at`,
            `deleted_at`
        )
        SELECT 
            cd.`id`,
            cd.`commission_id`,
            cd.`recipient_type`,
            CASE cd.`recipient_type`
                -- 1. Consultant / Senior Consultant / Referral -> consultants.user_id (atau fallback customers.user_id)
                WHEN 'consultant' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'senior_consultant' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'referral' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                
                -- 2. Koordinator -> koordinators.user_id
                WHEN 'koordinator' THEN kd.`user_id`
                
                -- 3. Korwil -> korwils.user_id
                WHEN 'korwil' THEN kw.`user_id`
                
                -- 4. Student -> customers.user_id
                WHEN 'student' THEN st_cust.`user_id`
                
                -- 5. School -> sekolah.consultant_id -> consultants.user_id
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
        -- Relasi Consultant / Referral
        LEFT JOIN `$sourceDb`.`consultants` cs ON cs.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`customers` cust ON cust.`id` = cd.`recipient_id`
        -- Relasi Koordinator
        LEFT JOIN `$sourceDb`.`koordinators` kd ON kd.`id` = cd.`recipient_id`
        -- Relasi Korwil
        LEFT JOIN `$sourceDb`.`korwils` kw ON kw.`id` = cd.`recipient_id`
        -- Relasi Student
        LEFT JOIN `$sourceDb`.`students` st ON st.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`customers` st_cust ON st_cust.`id` = st.`customer_id`
        -- Relasi School
        LEFT JOIN `$sourceDb`.`sekolah` sch ON sch.`id` = cd.`recipient_id`
        LEFT JOIN `$sourceDb`.`consultants` sch_cs ON sch_cs.`id` = sch.`consultant_id`
        
        -- Filter memastikan parent commission ada dan user_id berhasil diresolve
        INNER JOIN `$targetDb`.`commissions` comm ON comm.`id` = cd.`commission_id`
        WHERE (
            CASE cd.`recipient_type`
                WHEN 'consultant' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'senior_consultant' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'referral' THEN COALESCE(cs.`user_id`, cust.`user_id`)
                WHEN 'koordinator' THEN kd.`user_id`
                WHEN 'korwil' THEN kw.`user_id`
                WHEN 'student' THEN st_cust.`user_id`
                WHEN 'school' THEN sch_cs.`user_id`
                ELSE NULL
            END
        ) IS NOT NULL
    ";

    $affectedRows = $pdo->exec($migrateSql);
    $totalSource = (int) $pdo->query("SELECT COUNT(*) c FROM `$sourceDb`.`commission_details`")->fetch()['c'];
    $skipped = $totalSource - $affectedRows;

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "1. Rekapitulasi Migrasi:\n";
    echo "   -> Total data di sumber     : $totalSource baris\n";
    echo "   -> Berhasil dimasukkan      : $affectedRows baris\n";
    echo "   -> Di-skip (unresolved)     : $skipped baris\n\n";

    // 3. Rekapitulasi per tipe recipient
    echo "2. Rincian Data per Recipient Type:\n";
    printf(
        "%-20s | %-12s | %-18s\n",
        "RECIPIENT TYPE", "JUMLAH DATA", "TOTAL NOMINAL (Rp)"
    );
    echo str_repeat("-", 58) . "\n";

    $stmtSummary = $pdo->query("
        SELECT 
            recipient_type,
            COUNT(*) AS total_count,
            SUM(amount) AS total_amount
        FROM `$targetDb`.`commission_details`
        GROUP BY recipient_type
        ORDER BY total_count DESC
    ");
    foreach ($stmtSummary->fetchAll() as $row) {
        printf(
            "%-20s | %-12d | Rp %-15s\n",
            $row['recipient_type'],
            $row['total_count'],
            number_format($row['total_amount'], 0, ',', '.')
        );
    }
    echo str_repeat("=", 58) . "\n\n";

    // 4. Sampel 5 data hasil migrasi
    echo "3. Sampel Hasil Migrasi (5 Data Teratas):\n";
    printf(
        "%-5s | %-8s | %-12s | %-8s | %-24s | %-14s | %-8s\n",
        "ID", "COMM_ID", "TYPE", "USER_ID", "NAMA PENERIMA", "JUMLAH (Rp)", "STATUS"
    );
    echo str_repeat("-", 95) . "\n";
    $samples = $pdo->query("
        SELECT id, commission_id, recipient_type, user_id, name, amount, status 
        FROM `$targetDb`.`commission_details` 
        LIMIT 5
    ")->fetchAll();
    foreach ($samples as $s) {
        printf(
            "%-5d | %-8d | %-12s | %-8d | %-24s | Rp %-11s | %-8s\n",
            $s['id'],
            $s['commission_id'],
            $s['recipient_type'],
            $s['user_id'],
            mb_strimwidth($s['name'], 0, 24, '..'),
            number_format($s['amount'], 0, ',', '.'),
            $s['status']
        );
    }
    echo str_repeat("=", 95) . "\n\n";

    echo "====================================================================\n";
    echo "            MIGRASI COMMISSION DETAILS SELESAI                     \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
}
