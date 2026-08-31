<?php

/**
 * migrate_agents.php
 * 
 * Skrip migrasi data Agents dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 * 
 * Struktur Sumber (outclassco_marketing):
 * - customers: Tabel utama dengan category:
 *     4 = Koordinator
 *     5 = Consultant
 *     6 = School
 *     9 = Korwil
 * - korwils, koordinators, consultants, schools: Berisi detail alamat, geo, deskripsi/about, dan relasi.
 * 
 * Struktur Target (db_ybaik_new):
 * - agents: Tabel gabungan hierarki agen dengan kolom:
 *     id, users_id, parent_agents_id, consultant_type,
 *     regions_id, subregions_id, countries_id, states_id, cities_id,
 *     alamat, about, note, created_at, updated_at, deleted_at
 * - users: Sinkronisasi role_id sesuai spesifikasi role terbaru:
 *     1 = Superadmin
 *     2 = Admin
 *     3 = Korwil
 *     4 = Koordinator
 *     5 = Consultant
 *     6 = Finance
 *     7 = School
 *     8 = Parent
 *     9 = Student
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
    echo "            MEMULAI MIGRASI DATA AGENTS (db_ybaik_new.agents)       \n";
    echo "====================================================================\n";

    // 1. Reset / Truncate tabel agents di target database
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`agents`");
    echo "-> Tabel `$targetDb`.`agents` berhasil dikosongkan (TRUNCATE).\n\n";

    // =========================================================================
    // TAHAP 1: MIGRASI KORWIL (Category 9 -> Role 3)
    // Level paling atas (Top-level), parent_agents_id = NULL
    // =========================================================================
    echo "1. Memigrasi data Korwil (Category 9)...\n";
    $sqlKorwil = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id` AS `users_id`,
            NULL AS `parent_agents_id`,
            NULL AS `consultant_type`,
            reg.`id` AS `regions_id`,
            subreg.`id` AS `subregions_id`,
            cntry.`id` AS `countries_id`,
            st.`id` AS `states_id`,
            ct.`id` AS `cities_id`,
            kw.`alamat`,
            kw.`about`,
            kw.`note`,
            c.`created_at`,
            c.`updated_at`,
            c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        LEFT JOIN `$sourceDb`.`korwils` kw ON (kw.`customer_id` = c.`id` OR kw.`user_id` = c.`user_id`)
        LEFT JOIN `$targetDb`.`regions` reg ON reg.`id` = kw.`region_id`
        LEFT JOIN `$targetDb`.`subregions` subreg ON subreg.`id` = kw.`subregion_id`
        LEFT JOIN `$targetDb`.`countries` cntry ON cntry.`id` = kw.`country_id`
        LEFT JOIN `$targetDb`.`states` st ON st.`id` = kw.`state_id`
        LEFT JOIN `$targetDb`.`cities` ct ON ct.`id` = kw.`city_id`
        WHERE c.`category` = 9
          AND c.`user_id` IS NOT NULL
    ";
    $affectedKorwil = $pdo->exec($sqlKorwil);
    echo "   -> Sukses: $affectedKorwil data Korwil dimasukkan ke tabel agents.\n\n";

    // =========================================================================
    // TAHAP 2: MIGRASI KOORDINATOR (Category 4 -> Role 4)
    // Level menengah, parent_agents_id terhubung ke Korwil terkait
    // =========================================================================
    echo "2. Memigrasi data Koordinator (Category 4)...\n";
    $sqlKoordinator = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id` AS `users_id`,
            NULL AS `parent_agents_id`,
            NULL AS `consultant_type`,
            reg.`id` AS `regions_id`,
            subreg.`id` AS `subregions_id`,
            cntry.`id` AS `countries_id`,
            st.`id` AS `states_id`,
            ct.`id` AS `cities_id`,
            kd.`alamat`,
            kd.`about`,
            kd.`note`,
            c.`created_at`,
            c.`updated_at`,
            c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        LEFT JOIN `$sourceDb`.`koordinators` kd ON (kd.`customer_id` = c.`id` OR kd.`user_id` = c.`user_id`)
        LEFT JOIN `$targetDb`.`regions` reg ON reg.`id` = kd.`region_id`
        LEFT JOIN `$targetDb`.`subregions` subreg ON subreg.`id` = kd.`subregion_id`
        LEFT JOIN `$targetDb`.`countries` cntry ON cntry.`id` = kd.`country_id`
        LEFT JOIN `$targetDb`.`states` st ON st.`id` = kd.`state_id`
        LEFT JOIN `$targetDb`.`cities` ct ON ct.`id` = kd.`city_id`
        WHERE c.`category` = 4
          AND c.`user_id` IS NOT NULL
    ";
    $affectedKoordinator = $pdo->exec($sqlKoordinator);
    echo "   -> Sukses: $affectedKoordinator data Koordinator dimasukkan ke tabel agents.\n\n";

    // =========================================================================
    // TAHAP 3: MIGRASI CONSULTANT (Category 5 -> Role 5)
    // Membawa consultant_type ('consultant','senior_consultant','referral')
    // =========================================================================
    echo "3. Memigrasi data Consultant (Category 5)...\n";
    $sqlConsultant = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id` AS `users_id`,
            NULL AS `parent_agents_id`,
            CASE 
                WHEN cs.`role` IN ('consultant', 'senior_consultant', 'referral') THEN cs.`role`
                ELSE 'consultant'
            END AS `consultant_type`,
            reg.`id` AS `regions_id`,
            subreg.`id` AS `subregions_id`,
            cntry.`id` AS `countries_id`,
            st.`id` AS `states_id`,
            ct.`id` AS `cities_id`,
            cs.`alamat`,
            cs.`about`,
            cs.`note`,
            c.`created_at`,
            c.`updated_at`,
            c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        LEFT JOIN `$sourceDb`.`consultants` cs ON (cs.`customer_id` = c.`id` OR cs.`user_id` = c.`user_id`)
        LEFT JOIN `$targetDb`.`regions` reg ON reg.`id` = cs.`region_id`
        LEFT JOIN `$targetDb`.`subregions` subreg ON subreg.`id` = cs.`subregion_id`
        LEFT JOIN `$targetDb`.`countries` cntry ON cntry.`id` = cs.`country_id`
        LEFT JOIN `$targetDb`.`states` st ON st.`id` = cs.`state_id`
        LEFT JOIN `$targetDb`.`cities` ct ON ct.`id` = cs.`city_id`
        WHERE c.`category` = 5
          AND c.`user_id` IS NOT NULL
    ";
    $affectedConsultant = $pdo->exec($sqlConsultant);
    echo "   -> Sukses: $affectedConsultant data Consultant dimasukkan ke tabel agents.\n\n";

    // =========================================================================
    // TAHAP 4: MIGRASI SCHOOL (Category 6 -> Role 7) Jika Ada
    // =========================================================================
    echo "4. Memigrasi data School (Category 6)...\n";
    $sqlSchool = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id` AS `users_id`,
            NULL AS `parent_agents_id`,
            NULL AS `consultant_type`,
            NULL AS `regions_id`,
            NULL AS `subregions_id`,
            NULL AS `countries_id`,
            NULL AS `states_id`,
            NULL AS `cities_id`,
            NULL AS `alamat`,
            NULL AS `about`,
            NULL AS `note`,
            c.`created_at`,
            c.`updated_at`,
            c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        WHERE c.`category` = 6
          AND c.`user_id` IS NOT NULL
    ";
    $affectedSchool = $pdo->exec($sqlSchool);
    echo "   -> Sukses: $affectedSchool data School dimasukkan ke tabel agents.\n\n";

    // =========================================================================
    // TAHAP 5: PENGHUBUNGAN HIERARKI PARENT AGENTS (parent_agents_id)
    // =========================================================================
    echo "5. Menghubungkan relasi hierarki (parent_agents_id)...\n";

    // 5a. Koordinator -> Parent adalah Korwil (via customers.korwil_id -> korwils.id)
    $sqlParentKoor = "
        UPDATE `$targetDb`.`agents` child_a
        INNER JOIN `$sourceDb`.`customers` c ON c.`user_id` = child_a.`users_id` AND c.`category` = 4
        INNER JOIN `$sourceDb`.`korwils` kw ON kw.`id` = c.`korwil_id`
        INNER JOIN `$targetDb`.`agents` parent_a ON parent_a.`users_id` = kw.`user_id`
        SET child_a.`parent_agents_id` = parent_a.`id`
        WHERE child_a.`parent_agents_id` IS NULL
    ";
    $parentKoorLinked = $pdo->exec($sqlParentKoor);
    echo "   -> $parentKoorLinked Koordinator berhasil dihubungkan ke Korwil induk.\n";

    // 5b. Consultant -> Parent Koordinator (via customers.koordinator_id -> koordinators.id)
    $sqlParentConsKoor = "
        UPDATE `$targetDb`.`agents` child_a
        INNER JOIN `$sourceDb`.`customers` c ON c.`user_id` = child_a.`users_id` AND c.`category` = 5
        INNER JOIN `$sourceDb`.`koordinators` kd ON kd.`id` = c.`koordinator_id`
        INNER JOIN `$targetDb`.`agents` parent_a ON parent_a.`users_id` = kd.`user_id`
        SET child_a.`parent_agents_id` = parent_a.`id`
        WHERE child_a.`parent_agents_id` IS NULL
    ";
    $parentConsKoorLinked = $pdo->exec($sqlParentConsKoor);
    echo "   -> $parentConsKoorLinked Consultant berhasil dihubungkan ke Koordinator induk.\n";

    // 5c. Consultant -> Fallback Parent Korwil jika Koordinator tidak ada (via customers.korwil_id -> korwils.id)
    $sqlParentConsKw = "
        UPDATE `$targetDb`.`agents` child_a
        INNER JOIN `$sourceDb`.`customers` c ON c.`user_id` = child_a.`users_id` AND c.`category` = 5
        INNER JOIN `$sourceDb`.`korwils` kw ON kw.`id` = c.`korwil_id`
        INNER JOIN `$targetDb`.`agents` parent_a ON parent_a.`users_id` = kw.`user_id`
        SET child_a.`parent_agents_id` = parent_a.`id`
        WHERE child_a.`parent_agents_id` IS NULL
    ";
    $parentConsKwLinked = $pdo->exec($sqlParentConsKw);
    echo "   -> $parentConsKwLinked Consultant (tanpa Koordinator) berhasil dihubungkan langsung ke Korwil induk.\n";

    // 5d. School (Category 6) -> Parent Consultant / Koordinator / Korwil (jika ada)
    $sqlParentSchool = "
        UPDATE `$targetDb`.`agents` child_a
        INNER JOIN `$sourceDb`.`customers` c ON c.`user_id` = child_a.`users_id` AND c.`category` = 6
        LEFT JOIN `$sourceDb`.`consultants` cs ON cs.`id` = c.`consultant_id`
        LEFT JOIN `$sourceDb`.`koordinators` kd ON kd.`id` = c.`koordinator_id`
        LEFT JOIN `$sourceDb`.`korwils` kw ON kw.`id` = c.`korwil_id`
        LEFT JOIN `$targetDb`.`agents` p_cs ON p_cs.`users_id` = cs.`user_id`
        LEFT JOIN `$targetDb`.`agents` p_kd ON p_kd.`users_id` = kd.`user_id`
        LEFT JOIN `$targetDb`.`agents` p_kw ON p_kw.`users_id` = kw.`user_id`
        SET child_a.`parent_agents_id` = COALESCE(p_cs.`id`, p_kd.`id`, p_kw.`id`)
        WHERE child_a.`parent_agents_id` IS NULL
          AND COALESCE(p_cs.`id`, p_kd.`id`, p_kw.`id`) IS NOT NULL
    ";
    $parentSchoolLinked = $pdo->exec($sqlParentSchool);
    echo "   -> $parentSchoolLinked School berhasil dihubungkan ke Agent induk.\n\n";

    // =========================================================================
    // TAHAP 6: SINKRONISASI ROLE USER TERBARU (db_ybaik_new.users.role_id)
    // 3=Korwil, 4=Koordinator, 5=Consultant, 7=School
    // =========================================================================
    echo "6. Sinkronisasi role_id pada tabel users sesuai mapping role terbaru...\n";
    $sqlSyncRoles = "
        UPDATE `$targetDb`.`users` u
        INNER JOIN `$sourceDb`.`customers` c ON c.`user_id` = u.`id`
        SET u.`role_id` = CASE c.`category`
            WHEN 9 THEN 3 -- Korwil
            WHEN 4 THEN 4 -- Koordinator
            WHEN 5 THEN 5 -- Consultant
            WHEN 6 THEN 7 -- School
            ELSE u.`role_id`
        END
        WHERE c.`category` IN (4, 5, 6, 9)
    ";
    $rolesSynced = $pdo->exec($sqlSyncRoles);
    echo "   -> Sukses: Sebanyak $rolesSynced user telah diselaraskan role_id-nya.\n\n";

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $totalAgents = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`agents`")->fetch()['c'];

    echo "====================================================================\n";
    echo "                 MIGRASI DATA AGENTS SELESAI                        \n";
    echo "====================================================================\n";
    echo " - Total Agents berhasil dimigrasi : $totalAgents data\n";
    echo "   * Korwil (Role 3)              : $affectedKorwil data\n";
    echo "   * Koordinator (Role 4)         : $affectedKoordinator data\n";
    echo "   * Consultant (Role 5)          : $affectedConsultant data\n";
    echo "   * School (Role 7)              : $affectedSchool data\n";
    echo " - Hierarki parent_agents_id terhubung : " . ($parentKoorLinked + $parentConsKoorLinked + $parentConsKwLinked + $parentSchoolLinked) . " relasi\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
}
