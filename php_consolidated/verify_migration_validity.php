<?php

/**
 * verify_migration_validity.php
 *
 * SKRIP VERIFIKASI VALIDITAS SEMANTIK DATA HASIL MIGRASI
 * =============================================================================
 *
 * Memvalidasi apakah data-data di database baru (db_ybaik_new) yang mengalami
 * perubahan ID / penyatuan tabel (seperti tabel agents, bank_accounts, dsb)
 * secara isi dan nama entitas TETAP SAMA PERSIS dengan data di database lama
 * (outclassco_marketing).
 *
 * Cakupan Pengecekan:
 * 1. Sekolah -> Agent (Consultant): Apakah konsultan sekolah di data baru sama dengan konsultan di data lama?
 * 2. Siswa -> Agent (Consultant, Korwil, Koordinator): Apakah agen yang menangani siswa sama antara lama vs baru?
 * 3. User -> Rekening Bank: Apakah rekening bank yang ter-link ke user sama dengan rekening asalnya?
 * 4. Rincian Komisi -> User: Apakah user_id penerima komisi sesuai dengan penerima di data lama?
 * 5. Pendamping (Companions): Apakah data Ayah & Ibu siswa termigrasi sesuai nama di data lama?
 * 6. Pendaftaran (Enrollments): Apakah siswa mendaftar ke universitas & jurusan yang sama?
 * 7. Pembayaran (Payments): Apakah tagihan pembayaran terhubung ke siswa yang sama?
 */

$host     = '127.0.0.1';
$user     = 'root';
$pass     = '';
$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    echo "\n=========================================================================================\n";
    echo "       UJI VALIDITAS DATA BARU (db_ybaik_new) VS DATA LAMA (outclassco_marketing)        \n";
    echo "=========================================================================================\n\n";

    $stats = [
        'tests_run'  => 0,
        'total_rows' => 0,
        'matches'    => 0,
        'mismatches' => 0,
    ];

    // =========================================================================
    // UJI 1: SEKOLAH -> AGENT (CONSULTANT)
    // =========================================================================
    echo "-----------------------------------------------------------------------------------------\n";
    echo "1. VALIDASI RELASI SEKOLAH -> AGENT (CONSULTANT)\n";
    echo "   Memeriksa apakah agent_id baru di tabel sekolah merujuk ke konsultan lama yang sama.\n";
    echo "-----------------------------------------------------------------------------------------\n";

    $sqlSekolah = "
        SELECT 
            s_old.id AS sekolah_id,
            s_old.sekolah,
            s_old.consultant_id AS old_consultant_id,
            cs_old.name AS old_consultant_name,
            s_new.agent_id AS new_agent_id,
            u_new.name AS new_consultant_name
        FROM `$sourceDb`.`sekolah` s_old
        JOIN `$targetDb`.`sekolah` s_new ON s_old.id = s_new.id
        LEFT JOIN `$sourceDb`.`consultants` cs_old ON s_old.consultant_id = cs_old.id
        LEFT JOIN `$targetDb`.`agents` a_new ON s_new.agent_id = a_new.id
        LEFT JOIN `$targetDb`.`users` u_new ON a_new.users_id = u_new.id
        WHERE s_old.consultant_id IS NOT NULL
        ORDER BY s_old.id ASC
    ";
    $rowsSekolah = $pdo->query($sqlSekolah)->fetchAll();

    printf("%-7s | %-28s | %-25s | %-25s | %-8s\n", "ID SEK", "NAMA SEKOLAH", "KONSULTAN LAMA (OLD ID)", "AGEN BARU (NEW AGENT ID)", "STATUS");
    echo str_repeat("-", 100) . "\n";

    $matchSekolah = 0;
    $mismatchSekolah = 0;
    foreach ($rowsSekolah as $r) {
        $oldName = trim($r['old_consultant_name'] ?? '');
        $newName = trim($r['new_consultant_name'] ?? '');
        $isMatch = ($oldName !== '' && $oldName === $newName);

        if ($isMatch) {
            $matchSekolah++;
            $status = "[OK] VALID";
        } else {
            $mismatchSekolah++;
            $status = "[X] BEDA";
        }

        printf(
            "%-7d | %-28s | %-25s | %-25s | %-8s\n",
            $r['sekolah_id'],
            mb_strimwidth($r['sekolah'], 0, 28, '..'),
            mb_strimwidth("[ID: {$r['old_consultant_id']}] $oldName", 0, 25, '..'),
            mb_strimwidth("[ID: {$r['new_agent_id']}] $newName", 0, 25, '..'),
            $status
        );
    }
    echo "-> Hasil: Total " . count($rowsSekolah) . " sekolah | Match: $matchSekolah | Mismatch: $mismatchSekolah\n\n";
    $stats['tests_run']++;
    $stats['total_rows'] += count($rowsSekolah);
    $stats['matches'] += $matchSekolah;
    $stats['mismatches'] += $mismatchSekolah;

    // =========================================================================
    // UJI 2: STUDENTS -> AGENTS (CONSULTANT, KORWIL, KOORDINATOR)
    // =========================================================================
    echo "-----------------------------------------------------------------------------------------\n";
    echo "2. VALIDASI RELASI SISWA -> AGENTS (CONSULTANT, KORWIL, KOORDINATOR)\n";
    echo "   Memeriksa apakah siswa ditangani oleh orang (nama agen) yang sama setelah perubahan ID.\n";
    echo "-----------------------------------------------------------------------------------------\n";

    $sqlStudents = "
        SELECT 
            st_old.id AS student_id,
            cust_old.name AS student_name,
            -- Consultant
            cust_old.consultant_id AS old_cs_id,
            cs_old.name AS old_cs_name,
            st_new.consultant_id AS new_cs_agent_id,
            u_cs.name AS new_cs_name,
            -- Korwil
            cust_old.korwil_id AS old_kw_id,
            kw_old.name AS old_kw_name,
            st_new.korwil_id AS new_kw_agent_id,
            u_kw.name AS new_kw_name,
            -- Koordinator
            cust_old.koordinator_id AS old_kd_id,
            kd_old.name AS old_kd_name,
            st_new.koordinator_id AS new_kd_agent_id,
            u_kd.name AS new_kd_name
        FROM `$sourceDb`.`students` st_old
        JOIN `$sourceDb`.`customers` cust_old ON st_old.customer_id = cust_old.id
        JOIN `$targetDb`.`students` st_new ON st_old.id = st_new.id
        LEFT JOIN `$sourceDb`.`consultants` cs_old ON cust_old.consultant_id = cs_old.id
        LEFT JOIN `$sourceDb`.`korwils` kw_old ON cust_old.korwil_id = kw_old.id
        LEFT JOIN `$sourceDb`.`koordinators` kd_old ON cust_old.koordinator_id = kd_old.id
        LEFT JOIN `$targetDb`.`agents` a_cs ON st_new.consultant_id = a_cs.id
        LEFT JOIN `$targetDb`.`users` u_cs ON a_cs.users_id = u_cs.id
        LEFT JOIN `$targetDb`.`agents` a_kw ON st_new.korwil_id = a_kw.id
        LEFT JOIN `$targetDb`.`users` u_kw ON a_kw.users_id = u_kw.id
        LEFT JOIN `$targetDb`.`agents` a_kd ON st_new.koordinator_id = a_kd.id
        LEFT JOIN `$targetDb`.`users` u_kd ON a_kd.users_id = u_kd.id
        WHERE cust_old.consultant_id IS NOT NULL OR cust_old.korwil_id IS NOT NULL OR cust_old.koordinator_id IS NOT NULL
        ORDER BY st_old.id ASC
    ";
    $rowsStudents = $pdo->query($sqlStudents)->fetchAll();

    $csMatch = 0; $csMismatch = 0;
    $kwMatch = 0; $kwMismatch = 0;
    $kdMatch = 0; $kdMismatch = 0;

    foreach ($rowsStudents as $st) {
        if (!empty($st['old_cs_id'])) {
            if (trim($st['old_cs_name']) === trim($st['new_cs_name'])) $csMatch++; else $csMismatch++;
        }
        if (!empty($st['old_kw_id'])) {
            if (trim($st['old_kw_name']) === trim($st['new_kw_name'])) $kwMatch++; else $kwMismatch++;
        }
        if (!empty($st['old_kd_id'])) {
            if (trim($st['old_kd_name']) === trim($st['new_kd_name'])) $kdMatch++; else $kdMismatch++;
        }
    }

    echo "Sampel 5 Siswa Pertama dan Pemetaan Agennya:\n";
    printf("%-7s | %-22s | %-28s | %-28s\n", "ID ST", "NAMA SISWA", "CONSULTANT (OLD ID -> NEW ID)", "KORWIL (OLD ID -> NEW ID)");
    echo str_repeat("-", 95) . "\n";
    for ($i = 0; $i < min(5, count($rowsStudents)); $i++) {
        $r = $rowsStudents[$i];
        printf(
            "%-7d | %-22s | %-28s | %-28s\n",
            $r['student_id'],
            mb_strimwidth($r['student_name'], 0, 22, '..'),
            mb_strimwidth("[ID {$r['old_cs_id']}->{$r['new_cs_agent_id']}] {$r['new_cs_name']}", 0, 28, '..'),
            mb_strimwidth("[ID {$r['old_kw_id']}->{$r['new_kw_agent_id']}] {$r['new_kw_name']}", 0, 28, '..')
        );
    }
    echo str_repeat("-", 95) . "\n";
    echo "-> Status Relasi Consultant  : Match = $csMatch | Mismatch = $csMismatch\n";
    echo "-> Status Relasi Korwil      : Match = $kwMatch | Mismatch = $kwMismatch\n";
    echo "-> Status Relasi Koordinator : Match = $kdMatch | Mismatch = $kdMismatch\n\n";

    $stats['tests_run']++;
    $stats['total_rows'] += ($csMatch + $csMismatch + $kwMatch + $kwMismatch + $kdMatch + $kdMismatch);
    $stats['matches'] += ($csMatch + $kwMatch + $kdMatch);
    $stats['mismatches'] += ($csMismatch + $kwMismatch + $kdMismatch);

    // =========================================================================
    // UJI 3: REKENING BANK PENGGUNA (USERS -> BANK_ACCOUNTS)
    // =========================================================================
    echo "-----------------------------------------------------------------------------------------\n";
    echo "3. VALIDASI REKENING BANK PENGGUNA (USERS -> BANK_ACCOUNTS)\n";
    echo "   Memeriksa apakah nomor rekening & nama bank yang terhubung ke user sama persis dengan asalnya.\n";
    echo "-----------------------------------------------------------------------------------------\n";

    $sqlBank = "
        SELECT 
            u.id AS user_id,
            u.name AS user_name,
            ba.id AS new_bank_id,
            ba.nama_bank AS new_bank_name,
            ba.nomor_rekening AS new_account_no,
            cs.nama_bank AS old_cs_bank, cs.nomor_rekening AS old_cs_acc,
            kd.nama_bank AS old_kd_bank, kd.nomor_rekening AS old_kd_acc,
            kw.nama_bank AS old_kw_bank, kw.nomor_rekening AS old_kw_acc,
            st.nama_bank AS old_st_bank, st.nomor_rekening AS old_st_acc
        FROM `$targetDb`.`users` u
        JOIN `$targetDb`.`bank_accounts` ba ON u.bank_accounts_id = ba.id
        LEFT JOIN `$sourceDb`.`consultants` cs ON cs.user_id = u.id
        LEFT JOIN `$sourceDb`.`koordinators` kd ON kd.user_id = u.id
        LEFT JOIN `$sourceDb`.`korwils` kw ON kw.user_id = u.id
        LEFT JOIN `$sourceDb`.`customers` cust ON cust.user_id = u.id
        LEFT JOIN `$sourceDb`.`students` st ON st.customer_id = cust.id
        ORDER BY u.id ASC
    ";
    $rowsBank = $pdo->query($sqlBank)->fetchAll();
    $bankMatch = 0;
    $bankMismatch = 0;

    foreach ($rowsBank as $b) {
        $matched = false;
        $tBank = trim($b['new_bank_name']);
        $tAcc  = trim($b['new_account_no']);

        if (!empty($b['old_cs_bank']) && $tBank === trim(substr($b['old_cs_bank'], 0, 45)) && $tAcc === trim(substr($b['old_cs_acc'], 0, 45))) $matched = true;
        elseif (!empty($b['old_kd_bank']) && $tBank === trim(substr($b['old_kd_bank'], 0, 45)) && $tAcc === trim(substr($b['old_kd_acc'], 0, 45))) $matched = true;
        elseif (!empty($b['old_kw_bank']) && $tBank === trim(substr($b['old_kw_bank'], 0, 45)) && $tAcc === trim(substr($b['old_kw_acc'], 0, 45))) $matched = true;
        elseif (!empty($b['old_st_bank']) && $tBank === trim(substr($b['old_st_bank'], 0, 45)) && $tAcc === trim(substr($b['old_st_acc'], 0, 45))) $matched = true;

        if ($matched) $bankMatch++; else $bankMismatch++;
    }
    echo "-> Hasil: Total " . count($rowsBank) . " user dengan rekening bank | Match: $bankMatch | Mismatch: $bankMismatch\n\n";

    $stats['tests_run']++;
    $stats['total_rows'] += count($rowsBank);
    $stats['matches'] += $bankMatch;
    $stats['mismatches'] += $bankMismatch;

    // =========================================================================
    // UJI 4: RINCIAN KOMISI (COMMISSION_DETAILS -> USERS)
    // =========================================================================
    echo "-----------------------------------------------------------------------------------------\n";
    echo "4. VALIDASI PENERIMA KOMISI (COMMISSION_DETAILS -> USERS)\n";
    echo "   Memeriksa apakah user_id penerima komisi sesuai dengan entitas penerima di database lama.\n";
    echo "-----------------------------------------------------------------------------------------\n";

    $sqlComm = "
        SELECT 
            cd_old.id,
            cd_old.recipient_type,
            cd_old.recipient_id,
            cd_old.name AS old_comm_name,
            CASE cd_old.recipient_type
                WHEN 'consultant' THEN COALESCE(cs.user_id, cust.user_id)
                WHEN 'senior_consultant' THEN COALESCE(cs.user_id, cust.user_id)
                WHEN 'referral' THEN COALESCE(cs.user_id, cust.user_id)
                WHEN 'koordinator' THEN kd.user_id
                WHEN 'korwil' THEN kw.user_id
                WHEN 'student' THEN st_cust.user_id
                WHEN 'school' THEN sch_cs.user_id
                ELSE NULL
            END AS expected_user_id,
            cd_new.user_id AS actual_user_id,
            u.name AS user_name
        FROM `$sourceDb`.`commission_details` cd_old
        JOIN `$targetDb`.`commission_details` cd_new ON cd_old.id = cd_new.id
        LEFT JOIN `$sourceDb`.`consultants` cs ON cs.id = cd_old.recipient_id
        LEFT JOIN `$sourceDb`.`customers` cust ON cust.id = cd_old.recipient_id
        LEFT JOIN `$sourceDb`.`koordinators` kd ON kd.id = cd_old.recipient_id
        LEFT JOIN `$sourceDb`.`korwils` kw ON kw.id = cd_old.recipient_id
        LEFT JOIN `$sourceDb`.`students` st ON st.id = cd_old.recipient_id
        LEFT JOIN `$sourceDb`.`customers` st_cust ON st_cust.id = st.customer_id
        LEFT JOIN `$sourceDb`.`sekolah` sch ON sch.id = cd_old.recipient_id
        LEFT JOIN `$sourceDb`.`consultants` sch_cs ON sch_cs.id = sch.consultant_id
        LEFT JOIN `$targetDb`.`users` u ON cd_new.user_id = u.id
    ";
    $rowsComm = $pdo->query($sqlComm)->fetchAll();
    $commMatch = 0;
    $commMismatch = 0;

    foreach ($rowsComm as $c) {
        if ($c['expected_user_id'] !== null && $c['expected_user_id'] == $c['actual_user_id']) {
            $commMatch++;
        } else {
            $commMismatch++;
        }
    }
    echo "-> Hasil: Total " . count($rowsComm) . " baris rincian komisi | Match: $commMatch | Mismatch: $commMismatch\n\n";

    $stats['tests_run']++;
    $stats['total_rows'] += count($rowsComm);
    $stats['matches'] += $commMatch;
    $stats['mismatches'] += $commMismatch;

    // =========================================================================
    // UJI 5: PENDAMPING (COMPANIONS - AYAH & IBU)
    // =========================================================================
    echo "-----------------------------------------------------------------------------------------\n";
    echo "5. VALIDASI PENDAMPING (COMPANIONS - AYAH & IBU)\n";
    echo "   Memeriksa apakah data Ayah & Ibu yang diekstrak dari tabel students lama cocok 100%.\n";
    echo "-----------------------------------------------------------------------------------------\n";

    $sqlAyah = "
        SELECT s.id, s.nama_ayah AS old_name, c.full_name AS new_name
        FROM `$sourceDb`.`students` s
        JOIN `$targetDb`.`students` new_s ON s.id = new_s.id
        LEFT JOIN `$targetDb`.`companions` c ON c.student_id = s.id AND c.relation = 'ayah'
        WHERE NULLIF(TRIM(s.nama_ayah), '') IS NOT NULL
    ";
    $rowsAyah = $pdo->query($sqlAyah)->fetchAll();
    $ayahMatch = 0; $ayahMismatch = 0;
    foreach ($rowsAyah as $a) {
        if (trim($a['old_name']) === trim($a['new_name'] ?? '')) $ayahMatch++; else $ayahMismatch++;
    }

    $sqlIbu = "
        SELECT s.id, s.nama_ibu AS old_name, c.full_name AS new_name
        FROM `$sourceDb`.`students` s
        JOIN `$targetDb`.`students` new_s ON s.id = new_s.id
        LEFT JOIN `$targetDb`.`companions` c ON c.student_id = s.id AND c.relation = 'ibu'
        WHERE NULLIF(TRIM(s.nama_ibu), '') IS NOT NULL
    ";
    $rowsIbu = $pdo->query($sqlIbu)->fetchAll();
    $ibuMatch = 0; $ibuMismatch = 0;
    foreach ($rowsIbu as $ib) {
        if (trim($ib['old_name']) === trim($ib['new_name'] ?? '')) $ibuMatch++; else $ibuMismatch++;
    }

    echo "-> Data Ayah : Total " . count($rowsAyah) . " | Match: $ayahMatch | Mismatch: $ayahMismatch\n";
    echo "-> Data Ibu  : Total " . count($rowsIbu) . " | Match: $ibuMatch | Mismatch: $ibuMismatch\n\n";

    $stats['tests_run']++;
    $stats['total_rows'] += (count($rowsAyah) + count($rowsIbu));
    $stats['matches'] += ($ayahMatch + $ibuMatch);
    $stats['mismatches'] += ($ayahMismatch + $ibuMismatch);

    // =========================================================================
    // UJI 6: PENDAFTARAN (ENROLLMENTS & PROGRAMS)
    // =========================================================================
    echo "-----------------------------------------------------------------------------------------\n";
    echo "6. VALIDASI PENDAFTARAN SISWA (ENROLLMENTS & ENROLLMENT PROGRAMS)\n";
    echo "   Memeriksa apakah relasi siswa ke universitas dan jurusan tetap sama persis.\n";
    echo "-----------------------------------------------------------------------------------------\n";

    $sqlEnr = "
        SELECT 
            sp_old.id,
            cust_old.name AS old_student_name, u_new.name AS new_student_name,
            univ_old.nama_univ_international AS old_univ, univ_new.nama_univ_international AS new_univ
        FROM `$sourceDb`.`student_programs` sp_old
        JOIN `$targetDb`.`enrollments` e_new ON sp_old.id = e_new.id
        JOIN `$sourceDb`.`students` st_old ON sp_old.student_id = st_old.id
        JOIN `$sourceDb`.`customers` cust_old ON st_old.customer_id = cust_old.id
        JOIN `$targetDb`.`students` st_new ON e_new.students_id = st_new.id
        JOIN `$targetDb`.`users` u_new ON st_new.user_id = u_new.id
        JOIN `$sourceDb`.`universities` univ_old ON sp_old.university_id = univ_old.id
        JOIN `$targetDb`.`universities` univ_new ON e_new.university_id = univ_new.id
    ";
    $rowsEnr = $pdo->query($sqlEnr)->fetchAll();
    $enrMatch = 0; $enrMismatch = 0;
    foreach ($rowsEnr as $en) {
        if (trim($en['old_student_name']) === trim($en['new_student_name']) && trim($en['old_univ']) === trim($en['new_univ'])) {
            $enrMatch++;
        } else {
            $enrMismatch++;
        }
    }

    $sqlEnrProg = "
        SELECT spd_old.id, prog_old.course_name AS old_course, prog_new.course_name AS new_course
        FROM `$sourceDb`.`student_program_details` spd_old
        JOIN `$targetDb`.`enrollment_programs` ep_new ON spd_old.id = ep_new.id
        JOIN `$sourceDb`.`univ_programs` prog_old ON spd_old.program_id = prog_old.id
        JOIN `$targetDb`.`univ_programs` prog_new ON ep_new.program_id = prog_new.id
    ";
    $rowsEnrProg = $pdo->query($sqlEnrProg)->fetchAll();
    $progMatch = 0; $progMismatch = 0;
    foreach ($rowsEnrProg as $ep) {
        if (trim($ep['old_course']) === trim($ep['new_course'])) $progMatch++; else $progMismatch++;
    }

    echo "-> Enrollments (Siswa & Univ)     : Total " . count($rowsEnr) . " | Match: $enrMatch | Mismatch: $enrMismatch\n";
    echo "-> Enrollment Programs (Jurusan)  : Total " . count($rowsEnrProg) . " | Match: $progMatch | Mismatch: $progMismatch\n\n";

    $stats['tests_run']++;
    $stats['total_rows'] += (count($rowsEnr) + count($rowsEnrProg));
    $stats['matches'] += ($enrMatch + $progMatch);
    $stats['mismatches'] += ($enrMismatch + $progMismatch);

    // =========================================================================
    // UJI 7: PEMBAYARAN SISWA (STUDENTS_HAS_PAYMENTS)
    // =========================================================================
    echo "-----------------------------------------------------------------------------------------\n";
    echo "7. VALIDASI HUBUNGAN TAGIHAN KE SISWA (STUDENTS_HAS_PAYMENTS)\n";
    echo "   Memeriksa apakah invoice tagihan tetap terhubung ke siswa yang sama.\n";
    echo "-----------------------------------------------------------------------------------------\n";

    $sqlPay = "
        SELECT 
            ssp_old.id,
            cust_old.name AS old_student_name,
            u_new.name AS new_student_name
        FROM `$sourceDb`.`student_student_payment` ssp_old
        JOIN `$sourceDb`.`students` st_old ON ssp_old.student_id = st_old.id
        JOIN `$sourceDb`.`customers` cust_old ON st_old.customer_id = cust_old.id
        JOIN `$targetDb`.`students_has_payments` shp_new ON ssp_old.id = shp_new.id
        JOIN `$targetDb`.`students` st_new ON shp_new.student_id = st_new.id
        JOIN `$targetDb`.`users` u_new ON st_new.user_id = u_new.id
    ";
    $rowsPay = $pdo->query($sqlPay)->fetchAll();
    $payMatch = 0; $payMismatch = 0;
    foreach ($rowsPay as $py) {
        if (trim($py['old_student_name']) === trim($py['new_student_name'])) $payMatch++; else $payMismatch++;
    }
    echo "-> Relasi Tagihan Siswa : Total " . count($rowsPay) . " | Match: $payMatch | Mismatch: $payMismatch\n\n";

    $stats['tests_run']++;
    $stats['total_rows'] += count($rowsPay);
    $stats['matches'] += $payMatch;
    $stats['mismatches'] += $payMismatch;

    // =========================================================================
    // REKAPITULASI AKHIR & SKOR VALIDITAS
    // =========================================================================
    echo "=========================================================================================\n";
    echo "                             KESIMPULAN VALIDITAS DATA                                   \n";
    echo "=========================================================================================\n";
    $percent = ($stats['total_rows'] > 0) ? round(($stats['matches'] / $stats['total_rows']) * 100, 2) : 0;

    echo " - Total Pengujian Dijalankan   : {$stats['tests_run']} kelompok relasi\n";
    echo " - Total Record Relasi Diuji    : " . number_format($stats['total_rows'], 0, ',', '.') . " baris relasi\n";
    echo " - Record Sesuai (MATCH)        : " . number_format($stats['matches'], 0, ',', '.') . " baris (" . $percent . "%)\n";
    echo " - Record Tidak Sesuai (MISMATCH): " . number_format($stats['mismatches'], 0, ',', '.') . " baris\n";
    echo "=========================================================================================\n";

    if ($stats['mismatches'] === 0) {
        echo " [HASIL AKHIR: 100% VALID]\n";
        echo " Seluruh data relasi pada database baru terbukti cocok secara semantik (nama & identitas)\n";
        echo " dengan data lama, meskipun ID tabelnya telah berubah dan disatukan!\n";
    } else {
        echo " [HASIL AKHIR: DITEMUKAN PERBEDAAN]\n";
        echo " Silakan cek detail mismatch di atas untuk penelusuran lebih lanjut.\n";
    }
    echo "=========================================================================================\n\n";

} catch (PDOException $e) {
    echo "\nError validasi: " . $e->getMessage() . "\n";
    exit(1);
}
