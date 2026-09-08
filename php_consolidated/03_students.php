<?php

/**
 * 03_students.php
 *
 * MODUL 3: SISWA, PENDAMPING/ORANG TUA, BERKAS DOKUMEN, DAN FAVORIT
 * =============================================================================
 *
 * ALUR MIGRASI PADA FILE INI (Berurutan untuk menjamin integritas Foreign Key):
 *
 * 1. students:
 *    Migrasi data induk siswa dari `outclassco_marketing.students` digabung
 *    dengan `outclassco_marketing.customers` untuk mendapatkan `user_id`.
 *    Relasi agen (korwil_id, koordinator_id, consultant_id) langsung dicocokkan
 *    dan dihubungkan ke ID baru pada tabel `db_ybaik_new.agents`.
 *
 * 2. admin_students:
 *    Menghubungkan siswa dengan admin pendamping (admin_id -> users role 2).
 *
 * 3. student_education_backgrounds:
 *    Riwayat latar belakang pendidikan siswa (jenjang, nama sekolah, tahun).
 *
 * 4. companions & companion_parent_company_backgrounds & companion_relations:
 *    Normalisasi data orang tua (Ayah & Ibu) yang semula disimpan flat di tabel `students`.
 *    - Ayah & Ibu dibuatkan record tersendiri di tabel `companions`.
 *    - Pekerjaan & kantor dimasukkan ke `companion_parent_company_backgrounds`.
 *    - Hubungan pasangan suami-istri dicatat di `companion_relations`.
 *
 * 5. student_file_types & student_files & file_type_tutorial & student_file_type_univ_program:
 *    Migrasi master tipe berkas persyaratan, berkas upload siswa, tutorial unggah,
 *    dan aturan persyaratan berkas per program studi/universitas.
 *
 * 6. student_favorites:
 *    Daftar program studi universitas yang difavoritkan/di-bookmark oleh siswa.
 */

require_once __DIR__ . '/db.php';

$sourceDb = MigrationDB::SOURCE_DB;
$targetDb = MigrationDB::TARGET_DB;
$pdo      = MigrationDB::getConnection();

MigrationDB::logHeader("MODUL 3: SISWA, PENDAMPING, BERKAS & FAVORIT");
$startTime = microtime(true);

try {
    MigrationDB::disableForeignKeyChecks($pdo);

    // =========================================================================
    // TAHAP 1: MASTER STUDENTS & RELASI AGENT
    // =========================================================================
    MigrationDB::logSection("1/6", "Memigrasi Master Students & Relasi Agents (Korwil, Koordinator, Consultant)");
    MigrationDB::truncate($pdo, 'students');

    $sqlStudents = "
        INSERT INTO `$targetDb`.`students` (
            `id`, `user_id`, `chinese_name`, `nama_ayah`, `ayah_phone_code`, `ayah_phone`,
            `email_ayah`, `pekerjaan_ayah`, `kantor_ayah`, `nama_ibu`, `ibu_phone_code`,
            `ibu_phone`, `email_ibu`, `pekerjaan_ibu`, `kantor_ibu`, `jenjang`, `level`,
            `school_major`, `city_id_origin`, `address_origin`, `postal_code_origin`,
            `city_id_current`, `address_current`, `postal_code_current`, `note`, `gender`,
            `religion`, `tanggal_berangkat`, `tanggal_keberangkatan`, `pass_id_number`,
            `jenis_identitas`, `expired_passport`, `tempat_lahir`, `tanggal_lahir`,
            `graduation_time`, `average_score`, `test_selesai`, `test_detail`,
            `sponsor_status`, `nama_sponsor`, `perusahaan_sponsor`, `jabatan_sponsor`,
            `bidang_usaha_sponsor`, `alamat_usaha_sponsor`, `email_sponsor`, `hubungan_sponsor`,
            `status_siswa`, `keterangan_status`, `payment_completion_status`, `is_new_student`,
            `korwil_id`, `koordinator_id`, `consultant_id`,
            `created_at`, `updated_at`, `deleted_at`
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
            -- Relasi agent baru:
            a_kw.`id` AS `korwil_id`,
            a_kd.`id` AS `koordinator_id`,
            a_cs.`id` AS `consultant_id`,
            s.`created_at`,
            s.`updated_at`,
            s.`deleted_at`
        FROM `$sourceDb`.`students` s
        INNER JOIN `$sourceDb`.`customers` c ON s.`customer_id` = c.`id`
        -- Resolusi Korwil agent
        LEFT JOIN `$sourceDb`.`korwils` kw ON c.`korwil_id` = kw.`id`
        LEFT JOIN `$targetDb`.`agents` a_kw ON kw.`user_id` = a_kw.`users_id`
        -- Resolusi Koordinator agent
        LEFT JOIN `$sourceDb`.`koordinators` kd ON c.`koordinator_id` = kd.`id`
        LEFT JOIN `$targetDb`.`agents` a_kd ON kd.`user_id` = a_kd.`users_id`
        -- Resolusi Consultant agent
        LEFT JOIN `$sourceDb`.`consultants` cs ON c.`consultant_id` = cs.`id`
        LEFT JOIN `$targetDb`.`agents` a_cs ON cs.`user_id` = a_cs.`users_id`
        WHERE c.`user_id` IS NOT NULL
    ";
    $affStudents = $pdo->exec($sqlStudents);
    MigrationDB::logResult('students', $affStudents);

    // =========================================================================
    // TAHAP 2: ADMIN STUDENTS & EDUCATION BACKGROUNDS
    // =========================================================================
    MigrationDB::logSection("2/6", "Memigrasi Relasi Admin Students");
    MigrationDB::truncate($pdo, 'admin_students');
    $sqlAdminStudents = "
        INSERT INTO `$targetDb`.`admin_students` (`id`, `student_id`, `admin_id`, `created_at`, `updated_at`, `deleted_at`)
        SELECT ast.`id`, ast.`student_id`, ast.`admin_id`,
               COALESCE(ast.`created_at`, NOW()), ast.`updated_at`, ast.`deleted_at`
        FROM `$sourceDb`.`admin_students` ast
        INNER JOIN `$targetDb`.`students` st ON ast.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`users` u ON ast.`admin_id` = u.`id`
    ";
    $affAdminSt = $pdo->exec($sqlAdminStudents);
    MigrationDB::logResult('admin_students', $affAdminSt);

    MigrationDB::logSection("3/6", "Memigrasi Riwayat Pendidikan (student_education_backgrounds)");
    MigrationDB::truncate($pdo, 'student_education_backgrounds');
    $sqlEdu = "
        INSERT INTO `$targetDb`.`student_education_backgrounds` (
            `id`, `student_id`, `jenjang`, `nama_sekolah`, `masuk`, `keluar`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT `id`, `student_id`, `jenjang`, `nama_sekolah`, `masuk`, `keluar`,
               `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`student_education_backgrounds`
        WHERE `student_id` IS NOT NULL
    ";
    $affEdu = $pdo->exec($sqlEdu);
    MigrationDB::logResult('student_education_backgrounds', $affEdu);

    // =========================================================================
    // TAHAP 3: COMPANIONS & RELASI ORANG TUA
    // =========================================================================
    MigrationDB::logSection("4/6", "Memigrasi Pendamping / Orang Tua (companions & relasinya)");
    MigrationDB::truncate($pdo, 'companion_travel_historys');
    MigrationDB::truncate($pdo, 'companion_relations');
    MigrationDB::truncate($pdo, 'companion_parent_company_backgrounds');
    MigrationDB::truncate($pdo, 'companions');

    $sqlParentStudents = "
        SELECT s.id AS student_id, s.nama_ayah, s.ayah_phone_code, s.ayah_phone, s.email_ayah,
               s.pekerjaan_ayah, s.kantor_ayah, s.nama_ibu, s.ibu_phone_code, s.ibu_phone,
               s.email_ibu, s.pekerjaan_ibu, s.kantor_ibu, s.created_at, s.updated_at, s.deleted_at
        FROM `$sourceDb`.`students` s
        INNER JOIN `$targetDb`.`students` new_s ON new_s.id = s.id
        WHERE (NULLIF(TRIM(s.nama_ayah), '') IS NOT NULL OR NULLIF(TRIM(s.nama_ibu), '') IS NOT NULL)
        ORDER BY s.id ASC
    ";
    $parentsData = $pdo->query($sqlParentStudents)->fetchAll();

    $stmtComp = $pdo->prepare("
        INSERT INTO `$targetDb`.`companions` (
            `student_id`, `relation`, `type`, `full_name`, `is_employed`,
            `phone_code`, `phone`, `email`, `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :student_id, :relation, :type, :full_name, :is_employed,
            :phone_code, :phone, :email, :created_at, :updated_at, :deleted_at
        )
    ");
    $stmtCompBg = $pdo->prepare("
        INSERT INTO `$targetDb`.`companion_parent_company_backgrounds` (
            `companion_id`, `name`, `phone`, `supervisor_name`, `supervisor_phone`,
            `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :companion_id, :name, :phone, :supervisor_name, :supervisor_phone,
            :created_at, :updated_at, :deleted_at
        )
    ");
    $stmtCompRel = $pdo->prepare("
        INSERT INTO `$targetDb`.`companion_relations` (
            `relation`, `companions_id`, `companions_2_id`, `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :relation, :companions_id, :companions_2_id, :created_at, :updated_at, :deleted_at
        )
    ");

    $countCompanions = 0;
    $countRelations = 0;
    foreach ($parentsData as $stRow) {
        $stId = $stRow['student_id'];
        $cAt  = !empty($stRow['created_at']) ? $stRow['created_at'] : date('Y-m-d H:i:s');
        $uAt  = !empty($stRow['updated_at']) ? $stRow['updated_at'] : date('Y-m-d H:i:s');
        $dAt  = !empty($stRow['deleted_at']) ? $stRow['deleted_at'] : null;

        $ayahId = null;
        $ibuId  = null;

        // Ayah
        $namaAyah = trim($stRow['nama_ayah'] ?? '');
        if (!empty($namaAyah)) {
            $stmtComp->execute([
                ':student_id'  => $stId,
                ':relation'    => 'ayah',
                ':type'        => 'ayah',
                ':full_name'   => $namaAyah,
                ':is_employed' => (!empty($stRow['kantor_ayah']) || !empty($stRow['pekerjaan_ayah'])) ? 1 : 0,
                ':phone_code'  => $stRow['ayah_phone_code'],
                ':phone'       => $stRow['ayah_phone'],
                ':email'       => $stRow['email_ayah'],
                ':created_at'  => $cAt,
                ':updated_at'  => $uAt,
                ':deleted_at'  => $dAt,
            ]);
            $ayahId = $pdo->lastInsertId();
            $countCompanions++;

            if (!empty($stRow['kantor_ayah']) || !empty($stRow['pekerjaan_ayah'])) {
                $compName = !empty($stRow['kantor_ayah']) ? $stRow['kantor_ayah'] : $stRow['pekerjaan_ayah'];
                $stmtCompBg->execute([
                    ':companion_id'     => $ayahId,
                    ':name'             => mb_strimwidth($compName, 0, 255),
                    ':phone'            => !empty($stRow['ayah_phone']) ? trim($stRow['ayah_phone']) : '-',
                    ':supervisor_name'  => '-',
                    ':supervisor_phone' => '-',
                    ':created_at'       => $cAt,
                    ':updated_at'       => $uAt,
                    ':deleted_at'       => $dAt,
                ]);
            }
        }

        // Ibu
        $namaIbu = trim($stRow['nama_ibu'] ?? '');
        if (!empty($namaIbu)) {
            $stmtComp->execute([
                ':student_id'  => $stId,
                ':relation'    => 'ibu',
                ':type'        => 'Kebutuhan Data Visa',
                ':full_name'   => $namaIbu,
                ':is_employed' => (!empty($stRow['kantor_ibu']) || !empty($stRow['pekerjaan_ibu'])) ? 1 : 0,
                ':phone_code'  => !empty($stRow['ibu_phone_code']) ? substr(trim($stRow['ibu_phone_code']), 0, 6) : '+62',
                ':phone'       => !empty($stRow['ibu_phone']) ? trim($stRow['ibu_phone']) : null,
                ':email'       => !empty($stRow['email_ibu']) ? trim($stRow['email_ibu']) : null,
                ':created_at'  => $cAt,
                ':updated_at'  => $uAt,
                ':deleted_at'  => $dAt,
            ]);
            $ibuId = $pdo->lastInsertId();
            $countCompanions++;

            if (!empty($stRow['kantor_ibu']) || !empty($stRow['pekerjaan_ibu'])) {
                $compNameIbu = !empty($stRow['kantor_ibu']) ? $stRow['kantor_ibu'] : $stRow['pekerjaan_ibu'];
                $stmtCompBg->execute([
                    ':companion_id'     => $ibuId,
                    ':name'             => mb_strimwidth($compNameIbu, 0, 255),
                    ':phone'            => !empty($stRow['ibu_phone']) ? trim($stRow['ibu_phone']) : '-',
                    ':supervisor_name'  => '-',
                    ':supervisor_phone' => '-',
                    ':created_at'       => $cAt,
                    ':updated_at'       => $uAt,
                    ':deleted_at'       => $dAt,
                ]);
            }
        }

        // Hubungkan relasi pasangan jika ayah & ibu ada
        if ($ayahId && $ibuId) {
            $stmtCompRel->execute([
                ':relation'        => 'pasangan',
                ':companions_id'   => $ayahId,
                ':companions_2_id' => $ibuId,
                ':created_at'      => $cAt,
                ':updated_at'      => $uAt,
                ':deleted_at'      => $dAt,
            ]);
            $countRelations++;
        }
    }
    echo "   -> Sukses: $countCompanions data pendamping dan $countRelations relasi orang tua berhasil dimigrasi.\n\n";

    // =========================================================================
    // TAHAP 4: BERKAS DOKUMEN SISWA
    // =========================================================================
    MigrationDB::logSection("5/6", "Memigrasi Dokumen Siswa (student_file_types, files, tutorials, univ_programs)");
    MigrationDB::truncate($pdo, 'student_file_type_univ_program');
    MigrationDB::truncate($pdo, 'file_type_tutorial');
    MigrationDB::truncate($pdo, 'student_files');
    MigrationDB::truncate($pdo, 'student_file_types');

    $sqlTypes = "
        INSERT INTO `$targetDb`.`student_file_types` (
            `id`, `name`, `is_additional`, `order`, `mime_type`,
            `min_file_count`, `max_file_count`, `created_at`, `updated_at`
        )
        SELECT `id`, `name`, `is_additional`, `order`, `mime_type`,
               `min_file_count`, `max_file_count`, `created_at`, `updated_at`
        FROM `$sourceDb`.`student_file_types`
    ";
    $affTypes = $pdo->exec($sqlTypes);

    $sqlFiles = "
        INSERT INTO `$targetDb`.`student_files` (
            `id`, `student_id`, `filename`, `type`, `status`,
            `verified_note`, `verified_by`, `verified_at`,
            `created_at`, `updated_at`, `deleted_at`
        )
        SELECT sf.`id`, sf.`student_id`, sf.`filename`, sf.`type`, sf.`status`,
               sf.`verified_note`, sf.`verified_by`, sf.`verified_at`,
               sf.`created_at`, sf.`updated_at`, sf.`deleted_at`
        FROM `$sourceDb`.`student_files` sf
        INNER JOIN `$targetDb`.`students` st ON sf.`student_id` = st.`id`
    ";
    $affFiles = $pdo->exec($sqlFiles);

    $sqlTut = "
        INSERT INTO `$targetDb`.`file_type_tutorial` (`id`, `file_type_id`, `content`, `created_at`, `updated_at`)
        SELECT `id`, `file_type_id`, `content`, `created_at`, `updated_at`
        FROM `$sourceDb`.`file_type_tutorial`
    ";
    $affTut = $pdo->exec($sqlTut);

    // Drop index unique jika ada & modify program_id nullable
    try {
        $pdo->exec("ALTER TABLE `$targetDb`.`student_file_type_univ_program` ADD INDEX `idx_sft_id` (`student_file_type_id`)");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `$targetDb`.`student_file_type_univ_program` DROP INDEX `unique_filetype_univ_program`");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `$targetDb`.`student_file_type_univ_program` MODIFY COLUMN `program_id` BIGINT UNSIGNED NULL");
    } catch (Exception $e) {}

    $sqlProgFiles = "
        INSERT INTO `$targetDb`.`student_file_type_univ_program` (
            `id`, `student_file_type_id`, `univ_id`, `program_id`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT sftup.`id`, sftup.`student_file_type_id`, sftup.`univ_id`, sftup.`program_id`,
               COALESCE(sftup.`created_at`, NOW()), COALESCE(sftup.`updated_at`, NOW()), NULL
        FROM `$sourceDb`.`student_file_type_univ_program` sftup
        INNER JOIN `$targetDb`.`student_file_types` sft ON sftup.`student_file_type_id` = sft.`id`
        INNER JOIN `$targetDb`.`universities` u ON sftup.`univ_id` = u.`id`
    ";
    $affProgFiles = $pdo->exec($sqlProgFiles);
    echo "   -> Sukses: types=$affTypes, files=$affFiles, tutorials=$affTut, univ_program_reqs=$affProgFiles baris.\n\n";

    // =========================================================================
    // TAHAP 5: FAVORIT SISWA
    // =========================================================================
    MigrationDB::logSection("6/6", "Memigrasi Program Favorit Siswa (student_favorites)");
    MigrationDB::truncate($pdo, 'student_favorites');
    $sqlFav = "
        INSERT INTO `$targetDb`.`student_favorites` (`id`, `student_id`, `program_id`, `created_at`, `updated_at`, `deleted_at`)
        SELECT sf.`id`, sf.`student_id`, sf.`program_id`, sf.`created_at`, sf.`updated_at`, NULL
        FROM `$sourceDb`.`student_favorites` sf
        INNER JOIN `$targetDb`.`students` st ON sf.`student_id` = st.`id`
        INNER JOIN `$targetDb`.`univ_programs` up ON sf.`program_id` = up.`id`
    ";
    $affFav = $pdo->exec($sqlFav);
    MigrationDB::logResult('student_favorites', $affFav);

    MigrationDB::enableForeignKeyChecks($pdo);

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "====================================================================\n";
    echo "  MODUL 3 SELESAI DENGAN SUKSES! Waktu eksekusi: $elapsed detik.\n";
    echo "====================================================================\n\n";

} catch (Exception $e) {
    MigrationDB::enableForeignKeyChecks($pdo);
    echo "\n[ERROR MODUL 3] " . $e->getMessage() . "\n";
    exit(1);
}
