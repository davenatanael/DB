<?php

/**
 * 01_core_users_geo_agents.php
 *
 * MODUL 1: AUTENTIKASI, PENGGUNA, GEOGRAFIS, DAN HIERARKI AGENT
 * =============================================================================
 *
 * ALUR MIGRASI PADA FILE INI (Berurutan untuk menjamin integritas Foreign Key):
 *
 * 1. roles:
 *    Mengisi 9 role standar sistem baru:
 *    (1: Superadmin, 2: Admin, 3: Korwil, 4: Koordinator, 5: Consultant,
 *     6: Finance, 7: School, 8: Parent, 9: Student).
 *
 * 2. privileges:
 *    Migrasi master hak akses dari `outclassco_marketing.privileges`.
 *
 * 3. privileges_has_roles:
 *    Menghubungkan hak akses dengan role (dari `outclassco_marketing.role_privileges`).
 *    Baris dengan role_id atau privilege_id NULL di-skip.
 *
 * 4. bank_accounts:
 *    Deduplikasi nomor rekening bank unik dari 4 tabel sumber:
 *    consultants, koordinators, korwils, dan students.
 *
 * 5. users:
 *    Migrasi data pengguna dari `outclassco_marketing.users` digabung dengan
 *    data profil pelanggan `outclassco_marketing.customers` (referensi, referral_code).
 *    Role ID lama dipetakan ke Role ID baru (Marketing/Accounting -> Finance, dll).
 *
 * 6. Integrasi users.bank_accounts_id:
 *    Mencocokkan dan memperbarui FK `bank_accounts_id` pada tabel `users` berdasarkan
 *    nama bank & nomor rekening dari konsultan, koordinator, korwil, dan siswa.
 *
 * 7. regions -> 8. subregions -> 9. countries -> 10. states -> 11. cities:
 *    Migrasi berjenjang data wilayah dunia secara hierarkis (FK: country -> state -> city).
 *
 * 12. locations:
 *    Migrasi master lokasi kantor / operasional.
 *
 * 13. agents:
 *    Penyatuan hierarki agen ke tabel tunggal `agents`:
 *    - Level 1: Korwil (customers category 9 -> role 3)
 *    - Level 2: Koordinator (customers category 4 -> role 4)
 *    - Level 3: Consultant (customers category 5 -> role 5, membawa consultant_type)
 *    - Level 4: School (customers category 6 -> role 7)
 *
 * 14. sekolah:
 *    Migrasi data master sekolah nasional (~130rb baris).
 *    Penyesuaian relasi agent: kolom `agent_id` mengambil dari `consultant_id` lama
 *    yang disesuaikan dengan ID agen baru di tabel `agents` via relasi `user_id`.
 *    Jika tidak memiliki konsultan, `agent_id` diset NULL.
 */

require_once __DIR__ . '/db.php';

$sourceDb = MigrationDB::SOURCE_DB;
$targetDb = MigrationDB::TARGET_DB;
$pdo      = MigrationDB::getConnection();

MigrationDB::logHeader("MODUL 1: AUTENTIKASI, USERS, GEOGRAFI, AGENTS & SEKOLAH");
$startTime = microtime(true);

try {
    MigrationDB::disableForeignKeyChecks($pdo);

    // =========================================================================
    // TAHAP 1: ROLES & PRIVILEGES
    // =========================================================================
    MigrationDB::logSection("1/14", "Menginisialisasi Master Roles");
    MigrationDB::truncate($pdo, 'roles');
    $sqlRoles = "
        INSERT INTO `$targetDb`.`roles` (`id`, `name`) VALUES
        (1, 'Superadmin'),
        (2, 'Admin'),
        (3, 'Korwil'),
        (4, 'Koordinator'),
        (5, 'Consultant'),
        (6, 'Finance'),
        (7, 'School'),
        (8, 'Parent'),
        (9, 'Student');
    ";
    $affectedRoles = $pdo->exec($sqlRoles);
    MigrationDB::logResult('roles', $affectedRoles);

    MigrationDB::logSection("2/14", "Memigrasi Master Privileges");
    MigrationDB::truncate($pdo, 'privileges');
    $sqlPrivileges = "
        INSERT INTO `$targetDb`.`privileges` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`, `keterangan`)
        SELECT `id`, `name`, NOW(), NOW(), `deleted_at`, NULL
        FROM `$sourceDb`.`privileges`
    ";
    $affectedPriv = $pdo->exec($sqlPrivileges);
    $totalSourcePriv = (int)$pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`privileges`")->fetchColumn();
    MigrationDB::logResult('privileges', $affectedPriv, $totalSourcePriv);

    MigrationDB::logSection("3/14", "Memigrasi Privileges Has Roles");
    MigrationDB::truncate($pdo, 'privileges_has_roles');
    $sqlPrivRoles = "
        INSERT INTO `$targetDb`.`privileges_has_roles` (`id`, `privilege_id`, `role_id`)
        SELECT `id`, `privilege_id`, `role_id`
        FROM `$sourceDb`.`role_privileges`
        WHERE `role_id` IS NOT NULL AND `privilege_id` IS NOT NULL
    ";
    $affectedPrivRoles = $pdo->exec($sqlPrivRoles);
    $totalSourcePrivRoles = (int)$pdo->query("SELECT COUNT(*) FROM `$sourceDb`.`role_privileges`")->fetchColumn();
    MigrationDB::logResult('privileges_has_roles', $affectedPrivRoles, $totalSourcePrivRoles);

    // =========================================================================
    // TAHAP 2: BANK ACCOUNTS & USERS
    // =========================================================================
    MigrationDB::logSection("4/14", "Memigrasi Master Bank Accounts (Deduplikasi Unik)");
    MigrationDB::truncate($pdo, 'bank_accounts');
    $sqlBankAccounts = "
        INSERT INTO `$targetDb`.`bank_accounts` (`nama_bank`, `nomor_rekening`)
        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`consultants`
        WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
        UNION
        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`koordinators`
        WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
        UNION
        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`korwils`
        WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
        UNION
        SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45)
        FROM `$sourceDb`.`students`
        WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
    ";
    $affectedBank = $pdo->exec($sqlBankAccounts);
    MigrationDB::logResult('bank_accounts', $affectedBank);

    MigrationDB::logSection("5/14", "Memigrasi Data Users");
    MigrationDB::truncate($pdo, 'users');
    $sqlUsers = "
        INSERT INTO `$targetDb`.`users` (
            `id`, `role_id`, `bank_accounts_id`, `first_name`, `last_name`, `name`,
            `username`, `email`, `phone_code`, `phone`, `password`, `approval`,
            `plain_password`, `remember_token`, `referensi`, `referral_code`,
            `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            u.`id`,
            CASE u.`role_id`
                WHEN 1 THEN 6 -- Marketing lama -> Finance (6)
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
        WHERE u.`role_id` != 8
    ";
    $affectedUsers = $pdo->exec($sqlUsers);
    MigrationDB::logResult('users', $affectedUsers);

    MigrationDB::logSection("6/14", "Mengintegrasikan Relasi bank_accounts_id ke Users");
    $updateBankStatements = [
        'consultants' => "
            UPDATE `$targetDb`.`users` u
            INNER JOIN `$sourceDb`.`consultants` c ON c.user_id = u.id
            INNER JOIN `$targetDb`.`bank_accounts` ba 
                ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(c.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
               AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(c.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
            SET u.bank_accounts_id = ba.id
            WHERE u.bank_accounts_id IS NULL AND c.deleted_at IS NULL
              AND NULLIF(TRIM(c.nama_bank), '') IS NOT NULL AND NULLIF(TRIM(c.nomor_rekening), '') IS NOT NULL
        ",
        'koordinators' => "
            UPDATE `$targetDb`.`users` u
            INNER JOIN `$sourceDb`.`koordinators` k ON k.user_id = u.id
            INNER JOIN `$targetDb`.`bank_accounts` ba 
                ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(k.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
               AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(k.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
            SET u.bank_accounts_id = ba.id
            WHERE u.bank_accounts_id IS NULL AND k.deleted_at IS NULL
              AND NULLIF(TRIM(k.nama_bank), '') IS NOT NULL AND NULLIF(TRIM(k.nomor_rekening), '') IS NOT NULL
        ",
        'korwils' => "
            UPDATE `$targetDb`.`users` u
            INNER JOIN `$sourceDb`.`korwils` kw ON kw.user_id = u.id
            INNER JOIN `$targetDb`.`bank_accounts` ba 
                ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(kw.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
               AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(kw.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
            SET u.bank_accounts_id = ba.id
            WHERE u.bank_accounts_id IS NULL AND kw.deleted_at IS NULL
              AND NULLIF(TRIM(kw.nama_bank), '') IS NOT NULL AND NULLIF(TRIM(kw.nomor_rekening), '') IS NOT NULL
        ",
        'students' => "
            UPDATE `$targetDb`.`users` u
            INNER JOIN `$sourceDb`.`customers` c ON c.user_id = u.id
            INNER JOIN `$sourceDb`.`students` s ON s.customer_id = c.id
            INNER JOIN `$targetDb`.`bank_accounts` ba 
                ON (ba.nama_bank COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(s.nama_bank), 45) COLLATE utf8mb4_unicode_ci)
               AND (ba.nomor_rekening COLLATE utf8mb4_unicode_ci) = (LEFT(TRIM(s.nomor_rekening), 45) COLLATE utf8mb4_unicode_ci)
            SET u.bank_accounts_id = ba.id
            WHERE u.bank_accounts_id IS NULL AND s.deleted_at IS NULL
              AND NULLIF(TRIM(s.nama_bank), '') IS NOT NULL AND NULLIF(TRIM(s.nomor_rekening), '') IS NOT NULL
        "
    ];
    $totalBankLinked = 0;
    foreach ($updateBankStatements as $roleLabel => $updateSql) {
        $up = $pdo->exec($updateSql);
        $totalBankLinked += $up;
    }
    echo "   -> Sukses: $totalBankLinked user berhasil di-link ke nomor rekening bank.\n\n";

    // =========================================================================
    // TAHAP 3: GEOGRAFI & LOCATIONS
    // =========================================================================
    MigrationDB::logSection("7/14", "Memigrasi Geografis: Regions");
    MigrationDB::truncate($pdo, 'regions');
    $sqlRegions = "
        INSERT INTO `$targetDb`.`regions` (`id`, `name`, `translations`, `flag`, `wikiDataId`, `created_at`, `updated_at`)
        SELECT `id`, `name`, `translations`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        FROM `$sourceDb`.`regions`
    ";
    $affRegions = $pdo->exec($sqlRegions);
    MigrationDB::logResult('regions', $affRegions);

    MigrationDB::logSection("8/14", "Memigrasi Geografis: Subregions");
    MigrationDB::truncate($pdo, 'subregions');
    $sqlSubregions = "
        INSERT INTO `$targetDb`.`subregions` (`id`, `name`, `translations`, `region_id`, `flag`, `wikiDataId`, `created_at`, `updated_at`)
        SELECT `id`, `name`, `translations`, `region_id`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        FROM `$sourceDb`.`subregions`
    ";
    $affSub = $pdo->exec($sqlSubregions);
    MigrationDB::logResult('subregions', $affSub);

    MigrationDB::logSection("9/14", "Memigrasi Geografis: Countries");
    MigrationDB::truncate($pdo, 'countries');
    $sqlCountries = "
        INSERT INTO `$targetDb`.`countries` (
            `id`, `name`, `iso3`, `numeric_code`, `iso2`, `phonecode`, `capital`, `currency`,
            `currency_name`, `currency_symbol`, `tld`, `native`, `region`, `region_id`, `subregion`,
            `subregion_id`, `nationality`, `timezones`, `translations`, `latitude`, `longitude`,
            `emoji`, `emojiU`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        )
        SELECT 
            `id`, `name`, `iso3`, `numeric_code`, `iso2`, `phonecode`, `capital`, `currency`,
            `currency_name`, `currency_symbol`, `tld`, `native`, `region`, `region_id`, `subregion`,
            `subregion_id`, `nationality`, `timezones`, `translations`, `latitude`, `longitude`,
            `emoji`, `emojiU`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        FROM `$sourceDb`.`countries`
    ";
    $affCountries = $pdo->exec($sqlCountries);
    MigrationDB::logResult('countries', $affCountries);

    MigrationDB::logSection("10/14", "Memigrasi Geografis: States");
    MigrationDB::truncate($pdo, 'states');
    $sqlStates = "
        INSERT INTO `$targetDb`.`states` (
            `id`, `name`, `country_id`, `country_code`, `fips_code`, `iso2`, `type`, `level`,
            `parent_id`, `native`, `latitude`, `longitude`, `flag`, `wikiDataId`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `name`, `country_id`, `country_code`, `fips_code`, `iso2`, `type`, `level`,
            `parent_id`, `native`, `latitude`, `longitude`, `flag`, `wikiDataId`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`states`
    ";
    $affStates = $pdo->exec($sqlStates);
    MigrationDB::logResult('states', $affStates);

    MigrationDB::logSection("11/14", "Memigrasi Geografis: Cities (~152rb baris)");
    MigrationDB::truncate($pdo, 'cities');
    $sqlCities = "
        INSERT INTO `$targetDb`.`cities` (
            `id`, `name`, `state_id`, `state_code`, `country_id`, `country_code`,
            `latitude`, `longitude`, `wikiDataId`, `flag`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `name`, `state_id`, `state_code`, `country_id`, `country_code`,
            `latitude`, `longitude`, `wikiDataId`, `flag`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`cities`
    ";
    $affCities = $pdo->exec($sqlCities);
    MigrationDB::logResult('cities', $affCities);

    MigrationDB::logSection("12/14", "Memigrasi Master Locations");
    MigrationDB::truncate($pdo, 'locations');
    $sqlLocations = "
        INSERT INTO `$targetDb`.`locations` (`id`, `name`, `status`, `created_at`, `updated_at`, `deleted_at`)
        SELECT 
            l.`id`, l.`name`, l.`status`,
            CASE 
                WHEN l.`created_at` IS NULL OR l.`created_at` = '0000-00-00 00:00:00' 
                THEN COALESCE(NULLIF(l.`updated_at`, '0000-00-00 00:00:00'), NOW())
                ELSE l.`created_at`
            END,
            CASE 
                WHEN l.`updated_at` IS NULL OR l.`updated_at` = '0000-00-00 00:00:00' 
                THEN NOW()
                ELSE l.`updated_at`
            END,
            NULLIF(l.`deleted_at`, '0000-00-00 00:00:00')
        FROM `$sourceDb`.`locations` l
    ";
    $affLoc = $pdo->exec($sqlLocations);
    MigrationDB::logResult('locations', $affLoc);

    // =========================================================================
    // TAHAP 4: AGENTS & SEKOLAH
    // =========================================================================
    MigrationDB::logSection("13/14", "Memigrasi Penyatuan Agen (Korwil, Koordinator, Consultant, School) ke tabel Agents");
    MigrationDB::truncate($pdo, 'agents');

    // 13a. Korwil (Category 9)
    $sqlKorwil = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id`, NULL, NULL,
            reg.`id`, subreg.`id`, cntry.`id`, st.`id`, ct.`id`,
            kw.`alamat`, kw.`about`, kw.`note`, c.`created_at`, c.`updated_at`, c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        LEFT JOIN `$sourceDb`.`korwils` kw ON (kw.`customer_id` = c.`id` OR kw.`user_id` = c.`user_id`)
        LEFT JOIN `$targetDb`.`regions` reg ON reg.`id` = kw.`region_id`
        LEFT JOIN `$targetDb`.`subregions` subreg ON subreg.`id` = kw.`subregion_id`
        LEFT JOIN `$targetDb`.`countries` cntry ON cntry.`id` = kw.`country_id`
        LEFT JOIN `$targetDb`.`states` st ON st.`id` = kw.`state_id`
        LEFT JOIN `$targetDb`.`cities` ct ON ct.`id` = kw.`city_id`
        WHERE c.`category` = 9 AND c.`user_id` IS NOT NULL
    ";
    $affKw = $pdo->exec($sqlKorwil);

    // 13b. Koordinator (Category 4)
    $sqlKoordinator = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id`, NULL, NULL,
            reg.`id`, subreg.`id`, cntry.`id`, st.`id`, ct.`id`,
            kd.`alamat`, kd.`about`, kd.`note`, c.`created_at`, c.`updated_at`, c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        LEFT JOIN `$sourceDb`.`koordinators` kd ON (kd.`customer_id` = c.`id` OR kd.`user_id` = c.`user_id`)
        LEFT JOIN `$targetDb`.`regions` reg ON reg.`id` = kd.`region_id`
        LEFT JOIN `$targetDb`.`subregions` subreg ON subreg.`id` = kd.`subregion_id`
        LEFT JOIN `$targetDb`.`countries` cntry ON cntry.`id` = kd.`country_id`
        LEFT JOIN `$targetDb`.`states` st ON st.`id` = kd.`state_id`
        LEFT JOIN `$targetDb`.`cities` ct ON ct.`id` = kd.`city_id`
        WHERE c.`category` = 4 AND c.`user_id` IS NOT NULL
    ";
    $affKd = $pdo->exec($sqlKoordinator);

    // 13c. Consultant (Category 5)
    $sqlConsultant = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id`, NULL,
            CASE 
                WHEN cs.`role` IN ('consultant', 'senior_consultant', 'referral') THEN cs.`role`
                ELSE 'consultant'
            END,
            reg.`id`, subreg.`id`, cntry.`id`, st.`id`, ct.`id`,
            cs.`alamat`, cs.`about`, cs.`note`, c.`created_at`, c.`updated_at`, c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        LEFT JOIN `$sourceDb`.`consultants` cs ON (cs.`customer_id` = c.`id` OR cs.`user_id` = c.`user_id`)
        LEFT JOIN `$targetDb`.`regions` reg ON reg.`id` = cs.`region_id`
        LEFT JOIN `$targetDb`.`subregions` subreg ON subreg.`id` = cs.`subregion_id`
        LEFT JOIN `$targetDb`.`countries` cntry ON cntry.`id` = cs.`country_id`
        LEFT JOIN `$targetDb`.`states` st ON st.`id` = cs.`state_id`
        LEFT JOIN `$targetDb`.`cities` ct ON ct.`id` = cs.`city_id`
        WHERE c.`category` = 5 AND c.`user_id` IS NOT NULL
    ";
    $affCs = $pdo->exec($sqlConsultant);

    // 13d. School (Category 6)
    $sqlSchool = "
        INSERT INTO `$targetDb`.`agents` (
            `users_id`, `parent_agents_id`, `consultant_type`,
            `regions_id`, `subregions_id`, `countries_id`, `states_id`, `cities_id`,
            `alamat`, `about`, `note`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            c.`user_id`, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
            NULL, NULL, NULL, c.`created_at`, c.`updated_at`, c.`deleted_at`
        FROM `$sourceDb`.`customers` c
        INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id`
        WHERE c.`category` = 6 AND c.`user_id` IS NOT NULL
    ";
    $affSch = $pdo->exec($sqlSchool);
    $totalAgents = $affKw + $affKd + $affCs + $affSch;
    echo "   -> Sukses: Total $totalAgents baris dimasukkan ke tabel `agents` (Korwil: $affKw, Koor: $affKd, Cons: $affCs, School: $affSch).\n\n";

    MigrationDB::logSection("14/14", "Memigrasi Master Sekolah & Relasi Agent (dari consultant_id disesuaikan ke new agent_id)");
    $pdo->exec("ALTER TABLE `$targetDb`.`sekolah` MODIFY `agent_id` bigint unsigned NULL DEFAULT NULL");
    MigrationDB::truncate($pdo, 'sekolah');

    $sqlSekolah = "
        INSERT INTO `$targetDb`.`sekolah` (
            `id`, `country_id`, `kode_prop`, `propinsi`, `kode_kab_kota`, `kabupaten_kota`,
            `kode_kec`, `kecamatan`, `npsn`, `sekolah`, `bentuk`, `status`,
            `alamat_jalan`, `lintang`, `bujur`, `agent_id`, `created_by`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            s.`id`,
            COALESCE(s.`country_id`, 102),
            s.`kode_prop`,
            s.`propinsi`,
            s.`kode_kab_kota`,
            s.`kabupaten_kota`,
            s.`kode_kec`,
            s.`kecamatan`,
            NULLIF(TRIM(s.`npsn`), ''),
            s.`sekolah`,
            s.`bentuk`,
            s.`status`,
            s.`alamat_jalan`,
            s.`lintang`,
            s.`bujur`,
            -- Pemetaan agent_id: Mengambil dari consultant_id lama yang disesuaikan ke new agents.id
            a_cs.`id` AS `agent_id`,
            s.`created_by`,
            s.`created_at`,
            s.`updated_at`,
            s.`deleted_at`
        FROM `$sourceDb`.`sekolah` s
        LEFT JOIN `$sourceDb`.`consultants` cs ON s.`consultant_id` = cs.`id`
        LEFT JOIN `$targetDb`.`agents` a_cs ON cs.`user_id` = a_cs.`users_id`
    ";
    $affSekolah = $pdo->exec($sqlSekolah);
    $totalSekolah = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah`")->fetchColumn();
    $sekolahWithAgent = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`sekolah` WHERE agent_id IS NOT NULL")->fetchColumn();
    MigrationDB::logResult('sekolah', $affSekolah, $totalSekolah);
    echo "   -> Info: Sebanyak $sekolahWithAgent sekolah terhubung ke agen konsultan aktif.\n\n";

    MigrationDB::enableForeignKeyChecks($pdo);

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "====================================================================\n";
    echo "  MODUL 1 SELESAI DENGAN SUKSES! Waktu eksekusi: $elapsed detik.\n";
    echo "====================================================================\n\n";

} catch (Exception $e) {
    MigrationDB::enableForeignKeyChecks($pdo);
    echo "\n[ERROR MODUL 1] " . $e->getMessage() . "\n";
    exit(1);
}
