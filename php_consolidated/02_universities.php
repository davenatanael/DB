<?php

/**
 * 02_universities.php
 *
 * MODUL 2: MASTER UNIVERSITAS, PROGRAM STUDI, AKOMODASI, DAN FASILITAS
 * =============================================================================
 *
 * ALUR MIGRASI PADA FILE INI (Berurutan untuk menjamin integritas Foreign Key):
 *
 * 1. univ_categories:
 *    Migrasi kategori universitas dari `outclassco_marketing.univ_categories`.
 *
 * 2. universities:
 *    Migrasi master data universitas dari `outclassco_marketing.universities`.
 *    (Kolom `customer_id` diabaikan sesuai arsitektur skema baru).
 *
 * 3. univ_has_categories:
 *    Relasi many-to-many antara universitas dan kategori universitas.
 *
 * 4. univ_programs:
 *    Migrasi data jurusan/program studi yang tersedia di universitas.
 *
 * 5. univ_fee_structures:
 *    Rincian struktur biaya kuliah, formulir pendaftaran, dll.
 *
 * 6. univ_entry_requirements:
 *    Persyaratan masuk akademik dan dokumen untuk tiap program.
 *
 * 7. univ_scholarships:
 *    Data beasiswa (skema potongan biaya kuliah/akomodasi/uang saku).
 *
 * 8. univ_accomodations:
 *    Master gedung asrama / akomodasi tempat tinggal mahasiswa.
 *
 * 9. univ_accomodation_details:
 *    Rincian tipe kamar asrama (single, double, quad), harga, dan catatan fasilitas.
 *
 * 10. univ_accomodation_photos:
 *    Galeri foto asrama/akomodasi per universitas.
 *
 * 11. univ_facilities:
 *    Master 19 kategori fasilitas standar kampus (Library, Canteen, Laboratory, dsb.).
 *
 * 12. univ_has_facilities:
 *    Data detail fasilitas fisik kampus beserta foto, nama fasilitas, dan pemetaan
 *    otomatis ke kategori fasilitas menggunakan pencocokan kata kunci.
 */

require_once __DIR__ . '/db.php';

$sourceDb = MigrationDB::SOURCE_DB;
$targetDb = MigrationDB::TARGET_DB;
$pdo      = MigrationDB::getConnection();

MigrationDB::logHeader("MODUL 2: UNIVERSITAS, PROGRAM STUDI, AKOMODASI & FASILITAS");
$startTime = microtime(true);

try {
    MigrationDB::disableForeignKeyChecks($pdo);

    // =========================================================================
    // TAHAP 1: MASTER UNIVERSITAS & KATEGORI
    // =========================================================================
    MigrationDB::logSection("1/12", "Memigrasi Master Kategori Universitas");
    MigrationDB::truncate($pdo, 'univ_categories');
    $sqlCategories = "
        INSERT INTO `$targetDb`.`univ_categories` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`)
        SELECT `id`, `name`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`univ_categories`
    ";
    $affCat = $pdo->exec($sqlCategories);
    MigrationDB::logResult('univ_categories', $affCat);

    MigrationDB::logSection("2/12", "Memigrasi Master Universities");
    MigrationDB::truncate($pdo, 'universities');
    $sqlUniv = "
        INSERT INTO `$targetDb`.`universities` (
            `id`, `kode`, `nama_univ_china`, `register_link`, `nama_univ_international`,
            `photo`, `logo`, `cover`, `video_url`, `judul1`, `photo1`, `deskripsi1`,
            `judul2`, `photo2`, `deskripsi2`, `judul3`, `photo3`, `deskripsi3`,
            `judul4`, `deskripsi4`, `kategori`, `is_active`, `participation`, `kuota`,
            `kota`, `provinsi`, `country`, `edurank`, `url_edurank`, `usnews`, `url_usnews`,
            `times`, `url_times`, `qs`, `url_qs`, `shanghai_rank`, `url_shanghai_rank`,
            `jumlah_murid`, `atribut`, `level`, `type`, `living_expense`, `more_info`,
            `accomodation_description`, `status`, `t_status`, `admin_id`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `kode`, `nama_univ_china`, `register_link`, `nama_univ_international`,
            `photo`, `logo`, `cover`, `video_url`, `judul1`, `photo1`, `deskripsi1`,
            `judul2`, `photo2`, `deskripsi2`, `judul3`, `photo3`, `deskripsi3`,
            `judul4`, `deskripsi4`, `kategori`, `is_active`, `participation`, `kuota`,
            `kota`, `provinsi`, `country`, `edurank`, `url_edurank`, `usnews`, `url_usnews`,
            `times`, `url_times`, `qs`, `url_qs`, `shanghai_rank`, `url_shanghai_rank`,
            `jumlah_murid`, `atribut`, `level`, `type`, `living_expense`, `more_info`,
            `accomodation_description`, `status`, `t_status`, `admin_id`,
            `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`universities`
    ";
    $affUniv = $pdo->exec($sqlUniv);
    MigrationDB::logResult('universities', $affUniv);

    // =========================================================================
    // TAHAP 2: RELASI UNIVERSITAS & PROGRAM STUDI
    // =========================================================================
    MigrationDB::logSection("3/12", "Memigrasi Relasi univ_has_categories");
    MigrationDB::truncate($pdo, 'univ_has_categories');
    $sqlHasCat = "
        INSERT INTO `$targetDb`.`univ_has_categories` (`id`, `univ_id`, `category_id`, `created_at`, `updated_at`, `deleted_at`)
        SELECT `id`, `univ_id`, `category_id`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`univ_has_categories`
    ";
    $affHasCat = $pdo->exec($sqlHasCat);
    MigrationDB::logResult('univ_has_categories', $affHasCat);

    MigrationDB::logSection("4/12", "Memigrasi Program Studi (univ_programs)");
    MigrationDB::truncate($pdo, 'univ_programs');
    $sqlPrograms = "
        INSERT INTO `$targetDb`.`univ_programs` (
            `id`, `univ_id`, `course_name`, `admission_type`, `duration`, `c_duration`,
            `starting_date`, `application_deadline`, `starting_date2`, `application_deadline2`,
            `teaching_language`, `currency`, `c_tuition_fee`, `tuition_fee`,
            `c_application_fee`, `application_fee`, `c_service_fee`, `service_fee`,
            `program_description`, `entry_requirement`, `fee_structure`, `status`,
            `is_featured`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            `id`, `univ_id`, `course_name`, `admission_type`, `duration`, `c_duration`,
            `starting_date`, `application_deadline`, `starting_date2`, `application_deadline2`,
            `teaching_language`, `currency`, `c_tuition_fee`, `tuition_fee`,
            `c_application_fee`, `application_fee`, `c_service_fee`, `service_fee`,
            `program_description`, `entry_requirement`, `fee_structure`, `status`,
            `is_featured`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`univ_programs`
    ";
    $affProg = $pdo->exec($sqlPrograms);
    MigrationDB::logResult('univ_programs', $affProg);

    MigrationDB::logSection("5/12", "Memigrasi Struktur Biaya (univ_fee_structures)");
    MigrationDB::truncate($pdo, 'univ_fee_structures');
    $sqlFee = "
        INSERT INTO `$targetDb`.`univ_fee_structures` (
            `id`, `univ_id`, `fee_type`, `fee_name`, `fee_value`, `currency`, `nominal`, `sequence`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT `id`, `univ_id`, `fee_type`, `fee_name`, `fee_value`, `currency`, `nominal`, `sequence`,
               `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`univ_fee_structures`
    ";
    $affFee = $pdo->exec($sqlFee);
    MigrationDB::logResult('univ_fee_structures', $affFee);

    MigrationDB::logSection("6/12", "Memigrasi Syarat Masuk (univ_entry_requirements)");
    MigrationDB::truncate($pdo, 'univ_entry_requirements');
    $sqlEntry = "
        INSERT INTO `$targetDb`.`univ_entry_requirements` (
            `id`, `univ_id`, `admission_type`, `description`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT `id`, `univ_id`, `admission_type`, `description`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`univ_entry_requirements`
    ";
    $affEntry = $pdo->exec($sqlEntry);
    MigrationDB::logResult('univ_entry_requirements', $affEntry);

    MigrationDB::logSection("7/12", "Memigrasi Beasiswa Universitas (univ_scholarships)");
    MigrationDB::truncate($pdo, 'univ_scholarships');
    $sqlScholarships = "
        INSERT INTO `$targetDb`.`univ_scholarships` (
            `id`, `univ_id`, `admission_type`, `language`, `category`,
            `tuition_fee`, `accomodation_fee`, `insurance_fee`,
            `stipend_monthly`, `stipend_yearly`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT `id`, `univ_id`, `admission_type`, `language`, `category`,
               `tuition_fee`, `accomodation_fee`, `insurance_fee`,
               `stipend_monthly`, `stipend_yearly`,
               `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`univ_scholarships`
    ";
    $affSch = $pdo->exec($sqlScholarships);
    MigrationDB::logResult('univ_scholarships', $affSch);

    // =========================================================================
    // TAHAP 3: AKOMODASI UNIVERSITAS
    // =========================================================================
    MigrationDB::logSection("8/12", "Memigrasi Master Akomodasi/Asrama (univ_accomodations)");
    MigrationDB::truncate($pdo, 'univ_accomodations');
    $sqlAcc = "
        INSERT INTO `$targetDb`.`univ_accomodations` (`id`, `univ_id`, `name`, `description`, `created_at`, `updated_at`, `deleted_at`)
        SELECT acc.`id`, acc.`univ_id`, acc.`name`, acc.`description`, acc.`created_at`, acc.`updated_at`, acc.`deleted_at`
        FROM `$sourceDb`.`univ_accomodations` acc
        INNER JOIN `$targetDb`.`universities` u ON u.`id` = acc.`univ_id`
    ";
    $affAcc = $pdo->exec($sqlAcc);
    MigrationDB::logResult('univ_accomodations', $affAcc);

    MigrationDB::logSection("9/12", "Memigrasi Detail Akomodasi Kamar (univ_accomodation_details)");
    MigrationDB::truncate($pdo, 'univ_accomodation_details');
    $sqlAccDetails = "
        INSERT INTO `$targetDb`.`univ_accomodation_details` (
            `id`, `univ_accomodations_id`, `room_type`, `currency`, `room_price`,
            `price_note`, `photo`, `notes`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT dt.`id`, dt.`univ_accomodation_id`, dt.`room_type`, dt.`currency`, dt.`room_price`,
               dt.`price_note`, dt.`photo`, dt.`notes`, dt.`created_at`, dt.`updated_at`, dt.`deleted_at`
        FROM `$sourceDb`.`univ_accomodation_details` dt
        INNER JOIN `$targetDb`.`univ_accomodations` acc ON acc.`id` = dt.`univ_accomodation_id`
    ";
    $affAccDetails = $pdo->exec($sqlAccDetails);
    MigrationDB::logResult('univ_accomodation_details', $affAccDetails);

    MigrationDB::logSection("10/12", "Memigrasi Galeri Foto Akomodasi (univ_accomodation_photos)");
    MigrationDB::truncate($pdo, 'univ_accomodation_photos');
    $sqlAccPhotos = "
        INSERT INTO `$targetDb`.`univ_accomodation_photos` (
            `id`, `univ_id`, `univ_accomodations_id`, `name`, `photo`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT 
            p.`id`, p.`univ_id`,
            COALESCE(
                (SELECT acc.`id` FROM `$targetDb`.`univ_accomodations` acc WHERE acc.`univ_id` = p.`univ_id` ORDER BY acc.`id` ASC LIMIT 1),
                1
            ),
            p.`name`, p.`photo`, p.`created_at`, p.`updated_at`, p.`deleted_at`
        FROM `$sourceDb`.`univ_accomodation_photos` p
        INNER JOIN `$targetDb`.`universities` u ON u.`id` = p.`univ_id`
    ";
    $affAccPhotos = $pdo->exec($sqlAccPhotos);
    MigrationDB::logResult('univ_accomodation_photos', $affAccPhotos);

    // =========================================================================
    // TAHAP 4: FASILITAS UNIVERSITAS
    // =========================================================================
    MigrationDB::logSection("11/12", "Memasukkan 19 Master Kategori Fasilitas (univ_facilities)");
    MigrationDB::truncate($pdo, 'univ_facilities');
    $categories = [
        1 => 'Library', 2 => 'Sport Facilities', 3 => 'Canteen', 4 => 'Classroom',
        5 => 'Study Facilities', 6 => 'Museum', 7 => 'Gymnasium', 8 => 'Campus Building',
        9 => 'Theater/Studio', 10 => 'Swimming Pool', 11 => 'Laboratory', 12 => 'Other',
        13 => 'Campus Services', 14 => 'Outdoor Area', 15 => 'Lake', 16 => 'Hospital',
        17 => 'Dormitory/Accommodation', 18 => 'Bank', 19 => 'Gate',
    ];
    $categoryMap = array_flip($categories);
    $insertCatStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`univ_facilities` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`)
        VALUES (:id, :name, NOW(), NOW(), NULL)
    ");
    foreach ($categories as $catId => $catName) {
        $insertCatStmt->execute([':id' => $catId, ':name' => $catName]);
    }
    MigrationDB::logResult('univ_facilities', count($categories));

    MigrationDB::logSection("12/12", "Memigrasi Seluruh Fasilitas Kampus ke univ_has_facilities");
    // Penyesuaian struktur jika id auto increment belum ada
    $checkId = $pdo->query("SHOW COLUMNS FROM `$targetDb`.`univ_has_facilities` LIKE 'id'")->fetch();
    if (!$checkId) {
        $pdo->exec("
            ALTER TABLE `$targetDb`.`univ_has_facilities`
            DROP PRIMARY KEY,
            ADD COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST
        ");
    }
    $pdo->exec("ALTER TABLE `$targetDb`.`univ_has_facilities` MODIFY COLUMN `univ_facilities_id` BIGINT UNSIGNED NULL");
    $pdo->exec("ALTER TABLE `$targetDb`.`univ_has_facilities` MODIFY COLUMN `name` VARCHAR(255) NULL");

    MigrationDB::truncate($pdo, 'univ_has_facilities');

    function mapFacilityNameToCatId($name, $categoryMap) {
        $n = strtolower(trim($name));
        if (preg_match('/(library|reading pavilion)/i', $n)) return $categoryMap['Library'];
        if (preg_match('/(canteen|cafetaria|cafeteria|dining|food|restaurant)/i', $n)) return $categoryMap['Canteen'];
        if (preg_match('/(museum|art gallery)/i', $n)) return $categoryMap['Museum'];
        if (preg_match('/(swimming|swiming|natatorium|bathing beach)/i', $n)) return $categoryMap['Swimming Pool'];
        if (preg_match('/(gym|gymnasium)/i', $n)) return $categoryMap['Gymnasium'];
        if (preg_match('/(sport|basket|soccer|football|tennis|volleyball|stadium|track field|ball court|field)/i', $n)) return $categoryMap['Sport Facilities'];
        if (preg_match('/(classroom|class|smart classroom)/i', $n)) return $categoryMap['Classroom'];
        if (preg_match('/(study|learning|discussion area)/i', $n)) return $categoryMap['Study Facilities'];
        if (preg_match('/(theater|theatre|studio|broadcasting|acting|black box|rehearsing|editing room)/i', $n)) return $categoryMap['Theater/Studio'];
        if (preg_match('/(laborator|experiment)/i', $n)) return $categoryMap['Laboratory'];
        if (preg_match('/(hospital|medical|health)/i', $n)) return $categoryMap['Hospital'];
        if (preg_match('/(bedroom|dormitory|dorm|villa|hotel|accommodation|double room)/i', $n)) return $categoryMap['Dormitory/Accommodation'];
        if (preg_match('/(lake)/i', $n)) return $categoryMap['Lake'];
        if (preg_match('/(bank)/i', $n)) return $categoryMap['Bank'];
        if (preg_match('/(gate)/i', $n)) return $categoryMap['Gate'];
        if (preg_match('/(park|garden|outdoor|pavilion)/i', $n)) return $categoryMap['Outdoor Area'];
        if (preg_match('/(post office|shop|souvenir|recharge|service|coach|toilet)/i', $n)) return $categoryMap['Campus Services'];
        if (preg_match('/(building|hall|center|centre|campus|base|headquarters|workshop|school of design|secondary art school|meeting room)/i', $n)) return $categoryMap['Campus Building'];
        return null;
    }

    $sourceFacRows = $pdo->query("
        SELECT ufd.id AS detail_id, uf.univ_id, uf.name AS fac_name, ufd.name AS detail_name,
               ufd.image, ufd.created_at, ufd.updated_at, ufd.deleted_at
        FROM `$sourceDb`.`univ_facilities_details` ufd
        JOIN `$sourceDb`.`univ_facilities` uf ON ufd.`univ_facilities_id` = uf.`id`
        ORDER BY ufd.`id` ASC
    ")->fetchAll();

    $insertFacStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`univ_has_facilities` (
            `id`, `univ_id`, `univ_facilities_id`, `name`, `image`,
            `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :id, :univ_id, :univ_facilities_id, :name, :image,
            :created_at, :updated_at, :deleted_at
        )
    ");

    $affFacHas = 0;
    foreach ($sourceFacRows as $frow) {
        $catId = mapFacilityNameToCatId($frow['fac_name'], $categoryMap);
        $name = !empty($frow['detail_name']) ? trim($frow['detail_name']) : trim($frow['fac_name']);
        $insertFacStmt->execute([
            ':id'                 => $frow['detail_id'],
            ':univ_id'            => $frow['univ_id'],
            ':univ_facilities_id' => $catId,
            ':name'               => $name,
            ':image'              => !empty($frow['image']) ? trim($frow['image']) : null,
            ':created_at'         => !empty($frow['created_at']) ? $frow['created_at'] : date('Y-m-d H:i:s'),
            ':updated_at'         => !empty($frow['updated_at']) ? $frow['updated_at'] : null,
            ':deleted_at'         => !empty($frow['deleted_at']) ? $frow['deleted_at'] : null,
        ]);
        $affFacHas++;
    }
    MigrationDB::logResult('univ_has_facilities', $affFacHas, count($sourceFacRows));

    MigrationDB::enableForeignKeyChecks($pdo);

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "====================================================================\n";
    echo "  MODUL 2 SELESAI DENGAN SUKSES! Waktu eksekusi: $elapsed detik.\n";
    echo "====================================================================\n\n";

} catch (Exception $e) {
    MigrationDB::enableForeignKeyChecks($pdo);
    echo "\n[ERROR MODUL 2] " . $e->getMessage() . "\n";
    exit(1);
}
