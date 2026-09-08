<?php

/**
 * run_all.php
 *
 * MASTER RUNNER: MENJALANKAN SELURUH MODUL MIGRASI SECARA BERURUTAN
 * =============================================================================
 *
 * Mengonsolidasikan 34 script migrasi lama menjadi 5 file terstruktur:
 *   1. 01_core_users_geo_agents.php (Auth, Users, Geografi, Agents, Sekolah)
 *   2. 02_universities.php          (Universitas, Program, Akomodasi, Fasilitas)
 *   3. 03_students.php              (Siswa, Pendamping, Dokumen, Favorit)
 *   4. 04_enrollments_finance.php   (Pendaftaran, Keberangkatan, Pembayaran, Komisi)
 *   5. 05_operations.php            (Chat, Karyawan, Buku Tamu, Konsultasi, Notifikasi)
 *
 * Fitur Tambahan:
 *   - Auto-reset / truncate seluruh tabel target di awal.
 *   - Menghitung waktu eksekusi total.
 *   - Menampilkan tabel rekapitulasi data hasil migrasi.
 *   - Melakukan verifikasi integritas Foreign Key otomatis di akhir proses.
 */

require_once __DIR__ . '/db.php';

$totalStartTime = microtime(true);
$pdo = MigrationDB::getConnection();
$targetDb = MigrationDB::TARGET_DB;

echo "===========================================================================\n";
echo "       MEMULAI PROSES KONSOLIDASI SELURUH MIGRASI (db_ybaik_new)           \n";
echo "===========================================================================\n";

// -----------------------------------------------------------------------------
// LANGKAH 0: RESET (TRUNCATE) SELURUH TABEL TARGET
// -----------------------------------------------------------------------------
echo "\n-> Mereset (TRUNCATE) seluruh tabel di database `$targetDb`...\n";
MigrationDB::disableForeignKeyChecks($pdo);

$stmt = $pdo->prepare("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = :db 
      AND table_type = 'BASE TABLE'
    ORDER BY table_name ASC
");
$stmt->execute([':db' => $targetDb]);
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$truncateCount = 0;
foreach ($tables as $t) {
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`$t`");
    $truncateCount++;
}
echo "-> Berhasil mengosongkan $truncateCount tabel di `$targetDb`.\n";
MigrationDB::enableForeignKeyChecks($pdo);

// -----------------------------------------------------------------------------
// EKSEKUSI 5 MODUL SECARA BERURUTAN
// -----------------------------------------------------------------------------
$modules = [
    '01_core_users_geo_agents.php' => 'Modul 1: Auth, Users, Geo, Agents & Sekolah',
    '02_universities.php'          => 'Modul 2: Universitas, Program, Akomodasi & Fasilitas',
    '03_students.php'              => 'Modul 3: Siswa, Pendamping, Berkas & Favorit',
    '04_enrollments_finance.php'   => 'Modul 4: Pendaftaran, Keberangkatan, Pembayaran & Komisi',
    '05_operations.php'            => 'Modul 5: Chat, Karyawan, Buku Tamu, Konsultasi & Notifikasi',
];

$moduleResults = [];

foreach ($modules as $file => $label) {
    $modStart = microtime(true);
    require __DIR__ . '/' . $file;
    $modDuration = round(microtime(true) - $modStart, 2);
    $moduleResults[$file] = [
        'label'    => $label,
        'duration' => $modDuration
    ];
}

// -----------------------------------------------------------------------------
// RINGKASAN HASIL MIGRASI PER TABEL
// -----------------------------------------------------------------------------
echo "\n" . str_repeat("=", 75) . "\n";
echo "                     REKAPITULASI DATA HASIL MIGRASI                       \n";
echo str_repeat("=", 75) . "\n";

printf("%-5s | %-45s | %-15s\n", "NO", "NAMA TABEL", "JUMLAH BARIS");
echo str_repeat("-", 75) . "\n";

$no = 1;
$grandTotalRows = 0;

// Ambil daftar tabel yang terisi
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM `$targetDb`.`%s`");
foreach ($tables as $tbl) {
    $rowCount = (int)$pdo->query("SELECT COUNT(*) FROM `$targetDb`.`$tbl`")->fetchColumn();
    if ($rowCount > 0) {
        printf("%-5d | %-45s | %-15s\n", $no++, $tbl, number_format($rowCount, 0, ',', '.') . " baris");
        $grandTotalRows += $rowCount;
    }
}
echo str_repeat("=", 75) . "\n";
echo "Total Keseluruhan Data Termigrasi: " . number_format($grandTotalRows, 0, ',', '.') . " baris.\n";

// -----------------------------------------------------------------------------
// VERIFIKASI FOREIGN KEY INTEGRITY
// -----------------------------------------------------------------------------
echo "\n" . str_repeat("=", 75) . "\n";
echo "                 VERIFIKASI INTEGRITAS FOREIGN KEY                         \n";
echo str_repeat("=", 75) . "\n";

$fkChecks = [
    'users -> bank_accounts' => "
        SELECT COUNT(*) FROM `$targetDb`.`users` u
        LEFT JOIN `$targetDb`.`bank_accounts` ba ON u.bank_accounts_id = ba.id
        WHERE u.bank_accounts_id IS NOT NULL AND ba.id IS NULL
    ",
    'sekolah -> agents (consultant)' => "
        SELECT COUNT(*) FROM `$targetDb`.`sekolah` s
        LEFT JOIN `$targetDb`.`agents` a ON s.agent_id = a.id
        WHERE s.agent_id IS NOT NULL AND a.id IS NULL
    ",
    'sekolah -> countries' => "
        SELECT COUNT(*) FROM `$targetDb`.`sekolah` s
        LEFT JOIN `$targetDb`.`countries` c ON s.country_id = c.id
        WHERE s.country_id IS NOT NULL AND c.id IS NULL
    ",
    'students -> users' => "
        SELECT COUNT(*) FROM `$targetDb`.`students` s
        LEFT JOIN `$targetDb`.`users` u ON s.user_id = u.id
        WHERE s.user_id IS NOT NULL AND u.id IS NULL
    ",
    'enrollments -> students' => "
        SELECT COUNT(*) FROM `$targetDb`.`enrollments` e
        LEFT JOIN `$targetDb`.`students` s ON e.students_id = s.id
        WHERE e.students_id IS NOT NULL AND s.id IS NULL
    ",
    'enrollment_programs -> enrollments' => "
        SELECT COUNT(*) FROM `$targetDb`.`enrollment_programs` ep
        LEFT JOIN `$targetDb`.`enrollments` e ON ep.student_program_id = e.id
        WHERE ep.student_program_id IS NOT NULL AND e.id IS NULL
    ",
    'payments -> student_payment_types' => "
        SELECT COUNT(*) FROM `$targetDb`.`payments` p
        LEFT JOIN `$targetDb`.`student_payment_types` spt ON p.student_payment_type_id = spt.id
        WHERE p.student_payment_type_id IS NOT NULL AND spt.id IS NULL
    ",
    'commissions -> users' => "
        SELECT COUNT(*) FROM `$targetDb`.`commissions` c
        LEFT JOIN `$targetDb`.`users` u ON c.user_id = u.id
        WHERE c.user_id IS NOT NULL AND u.id IS NULL
    ",
    'commission_details -> commissions' => "
        SELECT COUNT(*) FROM `$targetDb`.`commission_details` cd
        LEFT JOIN `$targetDb`.`commissions` c ON cd.commission_id = c.id
        WHERE cd.commission_id IS NOT NULL AND c.id IS NULL
    ",
    'notifications -> admin_students' => "
        SELECT COUNT(*) FROM `$targetDb`.`notifications` n
        LEFT JOIN `$targetDb`.`admin_students` ast ON n.admin_student_id = ast.id
        WHERE n.admin_student_id IS NOT NULL AND ast.id IS NULL
    ",
];

$allValid = true;
foreach ($fkChecks as $fkName => $fkSql) {
    $invalidCount = (int)$pdo->query($fkSql)->fetchColumn();
    if ($invalidCount === 0) {
        echo " [OK] Relasi $fkName : VALID (0 pelanggaran FK)\n";
    } else {
        echo " [FAIL] Relasi $fkName : DITEMUKAN $invalidCount PELANGGARAN FK!\n";
        $allValid = false;
    }
}

$totalDuration = round(microtime(true) - $totalStartTime, 2);

echo "\n" . str_repeat("=", 75) . "\n";
if ($allValid) {
    echo "  SELURUH MIGRASI BERHASIL 100% TANPA KESALAHAN! (Durasi: $totalDuration detik)\n";
} else {
    echo "  MIGRASI SELESAI DENGAN BEBERAPA CATATAN FK! (Durasi: $totalDuration detik)\n";
}
echo str_repeat("=", 75) . "\n";
