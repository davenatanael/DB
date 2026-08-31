<?php

/**
 * migrate_companions.php
 * 
 * Skrip migrasi data pendamping / orang tua (Companions) dari database lama (outclassco_marketing) ke skema baru (db_ybaik_new).
 * 
 * Tabel yang dimigrasikan:
 * 1. `db_ybaik_new`.`companions`
 *    - Diambil dari data ayah & ibu pada tabel `outclassco_marketing`.`students` (nama_ayah, nama_ibu, kontak, dll).
 * 2. `db_ybaik_new`.`companion_parent_company_backgrounds`
 *    - Diambil dari data pekerjaan dan kantor tempat kerja orang tua (kantor_ayah, pekerjaan_ayah, kantor_ibu, pekerjaan_ibu).
 * 3. `db_ybaik_new`.`companion_relations`
 *    - Menghubungkan relasi pasangan antara data Ayah dan Ibu untuk student yang sama.
 * 4. `db_ybaik_new`.`companion_travel_historys` (Disiapkan jika ada data riwayat perjalanan).
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
    echo "         MEMULAI MIGRASI DATA COMPANIONS DAN RELASINYA              \n";
    echo "====================================================================\n\n";

    // 1. Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`companion_travel_historys`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`companion_relations`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`companion_parent_company_backgrounds`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`companions`");
    echo "-> Tabel `companions`, `companion_parent_company_backgrounds`, dan `companion_relations` berhasil dikosongkan (TRUNCATE).\n\n";

    // 2. Ambil data students dari database lama yang valid ada di tabel students database baru
    $sqlStudents = "
        SELECT 
            s.id AS student_id,
            s.nama_ayah,
            s.ayah_phone_code,
            s.ayah_phone,
            s.email_ayah,
            s.pekerjaan_ayah,
            s.kantor_ayah,
            s.nama_ibu,
            s.ibu_phone_code,
            s.ibu_phone,
            s.email_ibu,
            s.pekerjaan_ibu,
            s.kantor_ibu,
            s.created_at,
            s.updated_at,
            s.deleted_at
        FROM `$sourceDb`.`students` s
        INNER JOIN `$targetDb`.`students` new_s ON new_s.id = s.id
        WHERE (NULLIF(TRIM(s.nama_ayah), '') IS NOT NULL OR NULLIF(TRIM(s.nama_ibu), '') IS NOT NULL)
        ORDER BY s.id ASC
    ";
    $students = $pdo->query($sqlStudents)->fetchAll();
    $totalSourceStudents = count($students);

    echo "1. Memproses $totalSourceStudents data student yang memiliki informasi orang tua...\n";

    // Siapkan prepared statements
    $stmtInsertCompanion = $pdo->prepare("
        INSERT INTO `$targetDb`.`companions` (
            `student_id`, `relation`, `type`, `full_name`,
            `is_employed`, `phone_code`, `phone`, `email`,
            `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :student_id, :relation, :type, :full_name,
            :is_employed, :phone_code, :phone, :email,
            :created_at, :updated_at, :deleted_at
        )
    ");

    $stmtInsertBackground = $pdo->prepare("
        INSERT INTO `$targetDb`.`companion_parent_company_backgrounds` (
            `companion_id`, `name`, `phone`, `supervisor_name`, `supervisor_phone`,
            `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :companion_id, :name, :phone, :supervisor_name, :supervisor_phone,
            :created_at, :updated_at, :deleted_at
        )
    ");

    $stmtInsertRelation = $pdo->prepare("
        INSERT INTO `$targetDb`.`companion_relations` (
            `relation`, `companions_id`, `companions_2_id`,
            `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :relation, :companions_id, :companions_2_id,
            :created_at, :updated_at, :deleted_at
        )
    ");

    $countAyah = 0;
    $countIbu = 0;
    $countBackgrounds = 0;
    $countRelations = 0;

    $invalidWorkStrings = ['-', '--', '---', 'none', 'tidak ada', 'tidak', 't/a', 'n/a', 'null', '0'];

    foreach ($students as $st) {
        $studentId = $st['student_id'];
        $ayahId = null;
        $ibuId = null;

        // -------------------------------------------------------------
        // A. DATA AYAH
        // -------------------------------------------------------------
        $namaAyah = trim($st['nama_ayah'] ?? '');
        if (!empty($namaAyah) && !in_array(strtolower($namaAyah), $invalidWorkStrings)) {
            $pekerjaanAyah = trim($st['pekerjaan_ayah'] ?? '');
            $kantorAyah = trim($st['kantor_ayah'] ?? '');

            $isEmployedAyah = 0;
            if (!empty($pekerjaanAyah) && !in_array(strtolower($pekerjaanAyah), $invalidWorkStrings)) {
                $isEmployedAyah = 1;
            }

            $stmtInsertCompanion->execute([
                ':student_id'   => $studentId,
                ':relation'     => 'ayah',
                ':type'         => 'Kebutuhan Data Visa',
                ':full_name'    => $namaAyah,
                ':is_employed'  => $isEmployedAyah,
                ':phone_code'   => !empty($st['ayah_phone_code']) ? substr(trim($st['ayah_phone_code']), 0, 6) : '+62',
                ':phone'        => !empty($st['ayah_phone']) ? trim($st['ayah_phone']) : null,
                ':email'        => !empty($st['email_ayah']) ? trim($st['email_ayah']) : null,
                ':created_at'   => $st['created_at'],
                ':updated_at'   => $st['updated_at'],
                ':deleted_at'   => $st['deleted_at']
            ]);
            $ayahId = (int) $pdo->lastInsertId();
            $countAyah++;

            // Company Background Ayah
            $companyNameAyah = '';
            if (!empty($kantorAyah) && !in_array(strtolower($kantorAyah), $invalidWorkStrings)) {
                $companyNameAyah = $kantorAyah;
            } elseif (!empty($pekerjaanAyah) && !in_array(strtolower($pekerjaanAyah), $invalidWorkStrings)) {
                $companyNameAyah = $pekerjaanAyah;
            }

            if (!empty($companyNameAyah)) {
                $stmtInsertBackground->execute([
                    ':companion_id'     => $ayahId,
                    ':name'             => mb_strimwidth($companyNameAyah, 0, 255),
                    ':phone'            => !empty($st['ayah_phone']) ? trim($st['ayah_phone']) : '-',
                    ':supervisor_name'  => '-',
                    ':supervisor_phone' => '-',
                    ':created_at'       => $st['created_at'],
                    ':updated_at'       => $st['updated_at'],
                    ':deleted_at'       => $st['deleted_at']
                ]);
                $countBackgrounds++;
            }
        }

        // -------------------------------------------------------------
        // B. DATA IBU
        // -------------------------------------------------------------
        $namaIbu = trim($st['nama_ibu'] ?? '');
        if (!empty($namaIbu) && !in_array(strtolower($namaIbu), $invalidWorkStrings)) {
            $pekerjaanIbu = trim($st['pekerjaan_ibu'] ?? '');
            $kantorIbu = trim($st['kantor_ibu'] ?? '');

            $isEmployedIbu = 0;
            $nonWorkingIbu = ['ibu rumah tangga', 'irt', 'housewife', 'tidak bekerja', 'tidak ada', '-', '--'];
            if (!empty($pekerjaanIbu) && !in_array(strtolower($pekerjaanIbu), $nonWorkingIbu)) {
                $isEmployedIbu = 1;
            }

            $stmtInsertCompanion->execute([
                ':student_id'   => $studentId,
                ':relation'     => 'ibu',
                ':type'         => 'Kebutuhan Data Visa',
                ':full_name'    => $namaIbu,
                ':is_employed'  => $isEmployedIbu,
                ':phone_code'   => !empty($st['ibu_phone_code']) ? substr(trim($st['ibu_phone_code']), 0, 6) : '+62',
                ':phone'        => !empty($st['ibu_phone']) ? trim($st['ibu_phone']) : null,
                ':email'        => !empty($st['email_ibu']) ? trim($st['email_ibu']) : null,
                ':created_at'   => $st['created_at'],
                ':updated_at'   => $st['updated_at'],
                ':deleted_at'   => $st['deleted_at']
            ]);
            $ibuId = (int) $pdo->lastInsertId();
            $countIbu++;

            // Company Background Ibu
            $companyNameIbu = '';
            if (!empty($kantorIbu) && !in_array(strtolower($kantorIbu), $invalidWorkStrings)) {
                $companyNameIbu = $kantorIbu;
            } elseif ($isEmployedIbu && !empty($pekerjaanIbu) && !in_array(strtolower($pekerjaanIbu), $invalidWorkStrings)) {
                $companyNameIbu = $pekerjaanIbu;
            }

            if (!empty($companyNameIbu)) {
                $stmtInsertBackground->execute([
                    ':companion_id'     => $ibuId,
                    ':name'             => mb_strimwidth($companyNameIbu, 0, 255),
                    ':phone'            => !empty($st['ibu_phone']) ? trim($st['ibu_phone']) : '-',
                    ':supervisor_name'  => '-',
                    ':supervisor_phone' => '-',
                    ':created_at'       => $st['created_at'],
                    ':updated_at'       => $st['updated_at'],
                    ':deleted_at'       => $st['deleted_at']
                ]);
                $countBackgrounds++;
            }
        }

        // -------------------------------------------------------------
        // C. RELASI PASANGAN (Ayah <-> Ibu)
        // -------------------------------------------------------------
        if ($ayahId && $ibuId) {
            $stmtInsertRelation->execute([
                ':relation'         => 'pasangan',
                ':companions_id'    => $ayahId,
                ':companions_2_id'  => $ibuId,
                ':created_at'       => $st['created_at'],
                ':updated_at'       => $st['updated_at'],
                ':deleted_at'       => $st['deleted_at']
            ]);
            $countRelations++;
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "2. Hasil Rincian Migrasi Data Companions:\n";
    echo "   -> Data Ayah berhasil dimasukkan       : $countAyah data\n";
    echo "   -> Data Ibu berhasil dimasukkan        : $countIbu data\n";
    echo "   -> Total Companions dibuat             : " . ($countAyah + $countIbu) . " data\n";
    echo "   -> Data Company Backgrounds dibuat     : $countBackgrounds data\n";
    echo "   -> Data Relasi Pasangan (Ayah-Ibu)     : $countRelations relasi\n\n";

    // 3. Tampilkan sampel 5 baris hasil
    echo "3. Sampel Hasil Migrasi Companions (5 Data Teratas):\n";
    printf(
        "%-5s | %-10s | %-8s | %-25s | %-15s | %-25s\n",
        "ID", "STUDENT ID", "RELASI", "NAMA LENGKAP", "NO TELEPON", "EMAIL"
    );
    echo str_repeat("-", 95) . "\n";
    $samples = $pdo->query("
        SELECT id, student_id, relation, full_name, CONCAT(COALESCE(phone_code,''), ' ', COALESCE(phone,'-')) AS kontak, email 
        FROM `$targetDb`.`companions` 
        LIMIT 5
    ")->fetchAll();
    foreach ($samples as $s) {
        printf(
            "%-5d | %-10d | %-8s | %-25s | %-15s | %-25s\n",
            $s['id'],
            $s['student_id'],
            $s['relation'],
            mb_strimwidth($s['full_name'], 0, 25, '..'),
            mb_strimwidth($s['kontak'], 0, 15, '..'),
            mb_strimwidth($s['email'] ?? '-', 0, 25, '..')
        );
    }
    echo str_repeat("=", 95) . "\n\n";

    echo "====================================================================\n";
    echo "                 MIGRASI COMPANIONS SELESAI                         \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
}
