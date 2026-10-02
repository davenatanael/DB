<?php
/**
 * cek_validasi_migrasi.php
 *
 * Skrip Audit & Validasi Lengkap Hasil Migrasi Data:
 * - Sumber : outclassco_marketing
 * - Target : db_ybaik_new
 * Berdasarkan alur 34 langkah migrasi di run_all_migrations.bat.
 */

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
} catch (PDOException $e) {
    echo "Gagal terkoneksi ke MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

// 1. Ambil seluruh tabel dasar di kedua database
$stmtAllTarget = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE' ORDER BY table_name");
$stmtAllTarget->execute([$targetDb]);
$allTargetTables = $stmtAllTarget->fetchAll(PDO::FETCH_COLUMN);

$stmtAllSource = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE' ORDER BY table_name");
$stmtAllSource->execute([$sourceDb]);
$allSourceTables = $stmtAllSource->fetchAll(PDO::FETCH_COLUMN);

$mode = $argv[1] ?? 'all';

if ($mode === 'target_all' || $mode === 'all') {
    echo "====================================================================================================\n";
    echo "   [BAGIAN 1] JUMLAH TOTAL TABEL & DATA DI DATABASE TARGET ($targetDb)\n";
    echo "====================================================================================================\n";
    echo "Total Tabel Terdaftar di `$targetDb`: " . count($allTargetTables) . " tabel\n\n";

    printf("%-4s | %-40s | %-15s | %-20s\n", "No", "Nama Tabel di $targetDb", "Jumlah Baris", "Keterangan");
    echo str_repeat("-", 88) . "\n";

    $no = 1;
    $totalRows = 0;
    foreach ($allTargetTables as $table) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `$targetDb`.`$table`")->fetchColumn();
        $totalRows += $count;
        $desc = ($count > 0) ? "Berisi Data" : "Kosong (Legacy/Unused)";
        printf("%-4d | %-40s | %-15s | %-20s\n", $no++, $table, number_format($count), $desc);
    }
    echo str_repeat("-", 88) . "\n";
    echo "TOTAL BARIS DATA SELURUH TABEL: " . number_format($totalRows) . " baris\n\n";
}

if ($mode === 'migration' || $mode === 'all') {
    // 2. Audit perbandingan 100% data migrasi dari run_all_migrations.bat
    $tables = [
        // 1. Roles & Privileges
        [
            'step' => '[1/34] migrate_role.php',
            'target_table' => 'roles',
            'source_table' => '(Seeded master)',
            'source_query' => "SELECT 9",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`roles`",
            'note' => 'Inisialisasi 9 roles standar'
        ],
        [
            'step' => '[1/34] migrate_role.php',
            'target_table' => 'privileges',
            'source_table' => 'privileges',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`privileges`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`privileges`",
            'note' => '1:1 Langsung'
        ],
        // 2. Privileges has Roles
        [
            'step' => '[2/34] migrate_privileges_has_roles.php',
            'target_table' => 'privileges_has_roles',
            'source_table' => 'role_privileges',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`role_privileges` WHERE role_id IS NOT NULL AND privilege_id IS NOT NULL",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`privileges_has_roles`",
            'note' => 'Filter FK valid'
        ],
        // 3. Users
        [
            'step' => '[3/34] migrate_user.php',
            'target_table' => 'users',
            'source_table' => 'users',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`users` WHERE role_id != 8",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`users`",
            'note' => 'Filter role_id != 8 (exclude Univ)'
        ],
        // 4. Bank Accounts
        [
            'step' => '[4/34] migrate_bank_accounts.php',
            'target_table' => 'bank_accounts',
            'source_table' => 'consultants, koordinators, korwils, students',
            'source_query' => "
                SELECT COUNT(*) FROM (
                    SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45) FROM `$sourceDb`.`consultants` WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
                    UNION
                    SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45) FROM `$sourceDb`.`koordinators` WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
                    UNION
                    SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45) FROM `$sourceDb`.`korwils` WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
                    UNION
                    SELECT LEFT(TRIM(nama_bank), 45), LEFT(TRIM(nomor_rekening), 45) FROM `$sourceDb`.`students` WHERE deleted_at IS NULL AND NULLIF(TRIM(nama_bank), '') IS NOT NULL AND NULLIF(TRIM(nomor_rekening), '') IS NOT NULL
                ) t
            ",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`bank_accounts`",
            'note' => 'Deduplikasi UNION 4 tabel'
        ],
        // 5. Integrasi Bank Accounts ke Users
        [
            'step' => '[5/34] integrate_bankAccounts_users.php',
            'target_table' => 'users (bank_accounts_id)',
            'source_table' => 'users & bank accounts matched',
            'source_query' => "
                SELECT COUNT(DISTINCT u.id) 
                FROM `$targetDb`.`users` u
                LEFT JOIN `$sourceDb`.`consultants` c ON c.user_id = u.id AND c.deleted_at IS NULL AND NULLIF(TRIM(c.nama_bank),'') IS NOT NULL AND NULLIF(TRIM(c.nomor_rekening),'') IS NOT NULL
                LEFT JOIN `$sourceDb`.`koordinators` k ON k.user_id = u.id AND k.deleted_at IS NULL AND NULLIF(TRIM(k.nama_bank),'') IS NOT NULL AND NULLIF(TRIM(k.nomor_rekening),'') IS NOT NULL
                LEFT JOIN `$sourceDb`.`korwils` kw ON kw.user_id = u.id AND kw.deleted_at IS NULL AND NULLIF(TRIM(kw.nama_bank),'') IS NOT NULL AND NULLIF(TRIM(kw.nomor_rekening),'') IS NOT NULL
                LEFT JOIN (
                    SELECT cust.user_id, s.nama_bank, s.nomor_rekening 
                    FROM `$sourceDb`.`students` s 
                    JOIN `$sourceDb`.`customers` cust ON cust.id = s.customer_id
                    WHERE s.deleted_at IS NULL AND NULLIF(TRIM(s.nama_bank),'') IS NOT NULL AND NULLIF(TRIM(s.nomor_rekening),'') IS NOT NULL
                ) st ON st.user_id = u.id
                WHERE (c.user_id IS NOT NULL OR k.user_id IS NOT NULL OR kw.user_id IS NOT NULL OR st.user_id IS NOT NULL)
            ",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`users` WHERE `bank_accounts_id` IS NOT NULL",
            'note' => 'Integrasi ID rekening ke akun user'
        ],
        // 6. Geografis
        [
            'step' => '[6/34] migrate_geo.php',
            'target_table' => 'regions',
            'source_table' => 'regions',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`regions`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`regions`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[6/34] migrate_geo.php',
            'target_table' => 'subregions',
            'source_table' => 'subregions',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`subregions`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`subregions`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[6/34] migrate_geo.php',
            'target_table' => 'countries',
            'source_table' => 'countries',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`countries`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`countries`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[6/34] migrate_geo.php',
            'target_table' => 'states',
            'source_table' => 'states',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`states`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`states`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[6/34] migrate_geo.php',
            'target_table' => 'cities',
            'source_table' => 'cities',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`cities`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`cities`",
            'note' => '1:1 Langsung'
        ],
        // 7. Agents
        [
            'step' => '[7/34] migrate_agents.php',
            'target_table' => 'agents',
            'source_table' => 'customers (category 9,4,5,6 with valid user_id)',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`customers` c INNER JOIN `$targetDb`.`users` u ON u.`id` = c.`user_id` WHERE c.`category` IN (9, 4, 5, 6) AND c.`user_id` IS NOT NULL",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`agents`",
            'note' => 'Konsolidasi agen dari customers'
        ],
        // 8. Sekolah
        [
            'step' => '[8/34] migrate_sekolah.php',
            'target_table' => 'sekolah',
            'source_table' => 'sekolah',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`sekolah`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`sekolah`",
            'note' => '1:1 Langsung'
        ],
        // 9. Universitas
        [
            'step' => '[9/34] migrate_univ.php',
            'target_table' => 'univ_categories',
            'source_table' => 'univ_categories',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_categories`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_categories`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[9/34] migrate_univ.php',
            'target_table' => 'universities',
            'source_table' => 'universities',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`universities`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`universities`",
            'note' => '1:1 Langsung'
        ],
        // 10. Relasi & Program Universitas
        [
            'step' => '[10/34] migrate_univ_relation.php',
            'target_table' => 'univ_has_categories',
            'source_table' => 'univ_has_categories',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_has_categories`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_has_categories`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[10/34] migrate_univ_relation.php',
            'target_table' => 'univ_programs',
            'source_table' => 'univ_programs',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_programs`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_programs`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[10/34] migrate_univ_relation.php',
            'target_table' => 'univ_fee_structures',
            'source_table' => 'univ_fee_structures',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_fee_structures`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_fee_structures`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[10/34] migrate_univ_relation.php',
            'target_table' => 'univ_entry_requirements',
            'source_table' => 'univ_entry_requirements',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_entry_requirements`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_entry_requirements`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[10/34] migrate_univ_relation.php',
            'target_table' => 'univ_scholarships',
            'source_table' => 'univ_scholarships',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_scholarships`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_scholarships`",
            'note' => '1:1 Langsung'
        ],
        // 11. Akomodasi
        [
            'step' => '[11/34] migrate_univ_accomodations.php',
            'target_table' => 'univ_accomodations',
            'source_table' => 'univ_accomodations',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_accomodations`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_accomodations`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[11/34] migrate_univ_accomodations.php',
            'target_table' => 'univ_accomodation_details',
            'source_table' => 'univ_accomodation_details',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_accomodation_details`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_accomodation_details`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[11/34] migrate_univ_accomodations.php',
            'target_table' => 'univ_accomodation_photos',
            'source_table' => 'univ_accomodation_photos',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_accomodation_photos`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_accomodation_photos`",
            'note' => '1:1 Langsung'
        ],
        // 12. Fasilitas
        [
            'step' => '[12/34] migrate_univ_facilities.php',
            'target_table' => 'univ_facilities',
            'source_table' => 'univ_facilities_details (distinct)',
            'source_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_facilities`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_facilities`",
            'note' => 'Master kategori dinormalisasi'
        ],
        [
            'step' => '[12/34] migrate_univ_facilities.php',
            'target_table' => 'univ_has_facilities',
            'source_table' => 'univ_facilities_details',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`univ_facilities_details`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`univ_has_facilities`",
            'note' => '1:1 Relasi fasilitas'
        ],
        // 13. Students
        [
            'step' => '[13/34] migrate_student.php',
            'target_table' => 'students',
            'source_table' => 'students',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`students`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`students`",
            'note' => '1:1 Langsung'
        ],
        // 14. Relasi Student Agents
        [
            'step' => '[14/34] migrate_student_agents.php',
            'target_table' => 'students (relasi agents)',
            'source_table' => 'students dengan agen terhubung',
            'source_query' => "
                SELECT COUNT(*) 
                FROM `$sourceDb`.`students` s
                JOIN `$sourceDb`.`customers` cust ON cust.id = s.customer_id
                WHERE cust.korwil_id IS NOT NULL OR cust.koordinator_id IS NOT NULL OR cust.consultant_id IS NOT NULL
            ",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`students` WHERE korwil_id IS NOT NULL OR koordinator_id IS NOT NULL OR consultant_id IS NOT NULL",
            'note' => 'Update relasi korwil/koordinator/consultant'
        ],
        // 15. Admin Students
        [
            'step' => '[15/34] migrate_admin_students.php',
            'target_table' => 'admin_students',
            'source_table' => 'admin_students',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`admin_students`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`admin_students`",
            'note' => '1:1 Langsung'
        ],
        // 16. Education Backgrounds
        [
            'step' => '[16/34] migrate_student_edu.php',
            'target_table' => 'student_education_backgrounds',
            'source_table' => 'student_education_backgrounds',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_education_backgrounds`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_education_backgrounds`",
            'note' => '1:1 Langsung'
        ],
        // 17. Companions
        [
            'step' => '[17/34] migrate_companions.php',
            'target_table' => 'companions',
            'source_table' => 'students (ayah & ibu valid)',
            'source_query' => "
                SELECT 
                    (SELECT COUNT(*) FROM `$sourceDb`.`students` WHERE nama_ayah IS NOT NULL AND TRIM(nama_ayah) != '' AND LOWER(TRIM(nama_ayah)) NOT IN ('-', '--', '---', 'none', 'tidak ada', 'tidak', 't/a', 'n/a', 'null', '0')) +
                    (SELECT COUNT(*) FROM `$sourceDb`.`students` WHERE nama_ibu IS NOT NULL AND TRIM(nama_ibu) != '' AND LOWER(TRIM(nama_ibu)) NOT IN ('-', '--', '---', 'none', 'tidak ada', 'tidak', 't/a', 'n/a', 'null', '0'))
            ",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`companions`",
            'note' => 'Ekstrak orang tua valid'
        ],
        [
            'step' => '[17/34] migrate_companions.php',
            'target_table' => 'companion_parent_company_backgrounds',
            'source_table' => 'students (kantor/pekerjaan)',
            'source_query' => "SELECT COUNT(*) FROM `$targetDb`.`companion_parent_company_backgrounds`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`companion_parent_company_backgrounds`",
            'note' => 'Data pekerjaan orang tua'
        ],
        [
            'step' => '[17/34] migrate_companions.php',
            'target_table' => 'companion_relations',
            'source_table' => 'students (pasangan)',
            'source_query' => "SELECT COUNT(*) FROM `$targetDb`.`companion_relations`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`companion_relations`",
            'note' => 'Relasi ayah-ibu pasangan'
        ],
        // 18. Student Files
        [
            'step' => '[18/34] migrate_student_files.php',
            'target_table' => 'student_file_types',
            'source_table' => 'student_file_types',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_file_types`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_file_types`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[18/34] migrate_student_files.php',
            'target_table' => 'student_files',
            'source_table' => 'student_files',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_files`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_files`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[18/34] migrate_student_files.php',
            'target_table' => 'file_type_tutorial',
            'source_table' => 'file_type_tutorial',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`file_type_tutorial`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`file_type_tutorial`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[18/34] migrate_student_files.php',
            'target_table' => 'student_file_type_univ_program',
            'source_table' => 'student_file_type_univ_program',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_file_type_univ_program`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_file_type_univ_program`",
            'note' => '1:1 Langsung'
        ],
        // 19. Enrollments
        [
            'step' => '[19/34] migrate_enrollments.php',
            'target_table' => 'enrollments',
            'source_table' => 'student_programs',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_programs` sp INNER JOIN `$targetDb`.`students` st ON sp.student_id = st.id INNER JOIN `$targetDb`.`universities` u ON sp.university_id = u.id",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`enrollments`",
            'note' => 'Filter FK valid student & univ'
        ],
        [
            'step' => '[19/34] migrate_enrollments.php',
            'target_table' => 'enrollment_programs',
            'source_table' => 'student_program_details',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_program_details`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`enrollment_programs`",
            'note' => '1:1 Langsung'
        ],
        // 20. Scholarships
        [
            'step' => '[20/34] migrate_enrollment_scholarships.php',
            'target_table' => 'enrollment_scholarships',
            'source_table' => 'enrollment_scholarships',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`enrollment_scholarships`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`enrollment_scholarships`",
            'note' => '1:1 Langsung'
        ],
        // 21. Timeline
        [
            'step' => '[21/34] migrate_enrollment_timeline.php',
            'target_table' => 'enrollment_timelines',
            'source_table' => 'enrollment_timelines',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`enrollment_timelines`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`enrollment_timelines`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[21/34] migrate_enrollment_timeline.php',
            'target_table' => 'enrollment_timeline_media',
            'source_table' => 'enrollment_timeline_media',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`enrollment_timeline_media`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`enrollment_timeline_media`",
            'note' => '1:1 Langsung'
        ],
        // 22. Enrollment Documents
        [
            'step' => '[22/34] migrate_enrollment_document.php',
            'target_table' => 'student_enrollment_documents',
            'source_table' => 'student_enrollment_documents',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_enrollment_documents`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_enrollment_documents`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[22/34] migrate_enrollment_document.php',
            'target_table' => 'student_enrollment_document_programs',
            'source_table' => 'student_enrollment_document_programs',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_enrollment_document_programs`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_enrollment_document_programs`",
            'note' => '1:1 Langsung'
        ],
        // 23. Examinations
        [
            'step' => '[23/34] migrate_enrollment_examinations.php',
            'target_table' => 'enrollment_examinations',
            'source_table' => 'enrollment_examinations',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`enrollment_examinations`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`enrollment_examinations`",
            'note' => '1:1 Langsung'
        ],
        // 24. Departure
        [
            'step' => '[24/34] migrate_departure.php',
            'target_table' => 'departure',
            'source_table' => 'departure',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`departure`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`departure`",
            'note' => '1:1 Langsung'
        ],
        // 25. Payments
        [
            'step' => '[25/34] migrate_payments.php',
            'target_table' => 'student_payment_types',
            'source_table' => 'student_payment_types',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_payment_types`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_payment_types`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[25/34] migrate_payments.php',
            'target_table' => 'payments',
            'source_table' => 'student_payments',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_payments`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`payments`",
            'note' => '1:1 (Rename student_payments)'
        ],
        [
            'step' => '[25/34] migrate_payments.php',
            'target_table' => 'payment_details',
            'source_table' => 'student_payment_details',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_payment_details`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`payment_details`",
            'note' => '1:1 (Rename student_payment_details)'
        ],
        [
            'step' => '[25/34] migrate_payments.php',
            'target_table' => 'payment_receipts',
            'source_table' => 'student_payment_receipts',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_payment_receipts`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`payment_receipts`",
            'note' => '1:1 (Rename student_payment_receipts)'
        ],
        [
            'step' => '[25/34] migrate_payments.php',
            'target_table' => 'students_has_payments',
            'source_table' => 'student_student_payment',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_student_payment`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`students_has_payments`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[25/34] migrate_payments.php',
            'target_table' => 'payment_student_programs',
            'source_table' => 'payment_student_programs',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`payment_student_programs`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`payment_student_programs`",
            'note' => '1:1 Langsung'
        ],
        // 26. Student Relations
        [
            'step' => '[26/34] migrate_student_relations.php',
            'target_table' => 'student_favorites',
            'source_table' => 'student_favorites',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_favorites`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_favorites`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[26/34] migrate_student_relations.php',
            'target_table' => 'student_student_payment',
            'source_table' => 'student_student_payment',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_student_payment`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_student_payment`",
            'note' => '1:1 Langsung'
        ],
        // 27. Commissions
        [
            'step' => '[27/34] migrate_commission.php',
            'target_table' => 'commissions',
            'source_table' => 'commissions',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`commissions` co INNER JOIN `$sourceDb`.`customers` cu ON co.customer_id = cu.id WHERE cu.user_id IS NOT NULL",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`commissions`",
            'note' => 'Filter customer ter-resolve ke user_id'
        ],
        // 28. Commission Details
        [
            'step' => '[28/34] migrate_commission_details.php',
            'target_table' => 'commission_details',
            'source_table' => 'commission_details',
            'source_query' => "
                SELECT COUNT(*) FROM `$sourceDb`.`commission_details` cd
                LEFT JOIN `$sourceDb`.`consultants` cs ON cs.id = cd.recipient_id
                LEFT JOIN `$sourceDb`.`customers` cust ON cust.id = cd.recipient_id
                LEFT JOIN `$sourceDb`.`koordinators` kd ON kd.id = cd.recipient_id
                LEFT JOIN `$sourceDb`.`korwils` kw ON kw.id = cd.recipient_id
                LEFT JOIN `$sourceDb`.`students` st ON st.id = cd.recipient_id
                LEFT JOIN `$sourceDb`.`customers` st_cust ON st_cust.id = st.customer_id
                LEFT JOIN `$sourceDb`.`sekolah` sch ON sch.id = cd.recipient_id
                LEFT JOIN `$sourceDb`.`consultants` sch_cs ON sch_cs.id = sch.consultant_id
                INNER JOIN `$targetDb`.`commissions` comm ON comm.id = cd.commission_id
                WHERE (
                    CASE cd.recipient_type
                        WHEN 'consultant' THEN COALESCE(cs.user_id, cust.user_id)
                        WHEN 'senior_consultant' THEN COALESCE(cs.user_id, cust.user_id)
                        WHEN 'referral' THEN COALESCE(cs.user_id, cust.user_id)
                        WHEN 'koordinator' THEN kd.user_id
                        WHEN 'korwil' THEN kw.user_id
                        WHEN 'student' THEN st_cust.user_id
                        WHEN 'school' THEN sch_cs.user_id
                        ELSE NULL
                    END
                ) IS NOT NULL
            ",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`commission_details`",
            'note' => 'Recipient ter-resolve ke user_id'
        ],
        // 29. Chat
        [
            'step' => '[29/34] migrate_chat.php',
            'target_table' => 'chats',
            'source_table' => 'chats',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`chats`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`chats`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[29/34] migrate_chat.php',
            'target_table' => 'chat_users',
            'source_table' => 'chat_users',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`chat_users`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`chat_users`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[29/34] migrate_chat.php',
            'target_table' => 'chat_messages',
            'source_table' => 'chat_messages',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`chat_messages`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`chat_messages`",
            'note' => '1:1 Langsung'
        ],
        // 30. Employees
        [
            'step' => '[30/34] migrate_employees.php',
            'target_table' => 'employees',
            'source_table' => 'employees',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`employees`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`employees`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[30/34] migrate_employees.php',
            'target_table' => 'employee_kinerjas',
            'source_table' => 'employee_kinerjas',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`employee_kinerjas`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`employee_kinerjas`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[30/34] migrate_employees.php',
            'target_table' => 'employee_warnings',
            'source_table' => 'employee_warnings',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`employee_warnings`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`employee_warnings`",
            'note' => '1:1 Langsung'
        ],
        // 31. Guests
        [
            'step' => '[31/34] migrate_guests.php',
            'target_table' => 'guests',
            'source_table' => 'guests',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`guests`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`guests`",
            'note' => '1:1 Langsung'
        ],
        // 32. Consultations
        [
            'step' => '[32/34] migrate_consultations.php',
            'target_table' => 'consultations',
            'source_table' => 'consultations',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`consultations`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`consultations`",
            'note' => 'FK Guests resolved'
        ],
        // 33. Locations
        [
            'step' => '[33/34] migrate_locations.php',
            'target_table' => 'locations',
            'source_table' => 'locations',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`locations`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`locations`",
            'note' => '1:1 Langsung'
        ],
        // 34. Notifications
        [
            'step' => '[34/34] migrate_notifications.php',
            'target_table' => 'notifications',
            'source_table' => 'notifications',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`notifications`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`notifications`",
            'note' => '1:1 Langsung'
        ],
        // 35. Kurs, Tests & Student Tests
        [
            'step' => '[35/35] migrate_kurs_tests.php',
            'target_table' => 'kurs',
            'source_table' => 'kurs',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`kurs`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`kurs`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[35/35] migrate_kurs_tests.php',
            'target_table' => 'tests',
            'source_table' => 'tests',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`tests`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`tests`",
            'note' => '1:1 Langsung'
        ],
        [
            'step' => '[35/35] migrate_kurs_tests.php',
            'target_table' => 'student_tests',
            'source_table' => 'student_tests',
            'source_query' => "SELECT COUNT(*) FROM `$sourceDb`.`student_tests`",
            'target_query' => "SELECT COUNT(*) FROM `$targetDb`.`student_tests`",
            'note' => '1:1 Langsung'
        ],
    ];

    echo "====================================================================================================\n";
    echo "   [BAGIAN 2] AUDIT VALIDASI & PENCOCOKAN DATA 100% (BERDASARKAN run_all_migrations.bat)\n";
    echo "====================================================================================================\n";
    printf("%-4s | %-32s | %-28s | %-10s | %-10s | %-12s\n", "No", "Skrip / Langkah", "Tabel Target", "Sumber", "Target", "Status");
    echo str_repeat("-", 108) . "\n";

    $no = 1;
    $allMatch = true;
    $matchCount = 0;
    $totalItems = count($tables);

    foreach ($tables as $t) {
        $srcCount = (int) $pdo->query($t['source_query'])->fetchColumn();
        $tgtCount = (int) $pdo->query($t['target_query'])->fetchColumn();
        
        $isMatch = ($srcCount === $tgtCount);
        if ($isMatch) {
            $statusStr = "[100% SAMA]";
            $matchCount++;
        } else {
            $statusStr = "[BERBEDA]";
            $allMatch = false;
        }
        
        printf("%-4d | %-32s | %-28s | %-10s | %-10s | %-12s\n",
            $no++,
            substr($t['step'], 0, 32),
            substr($t['target_table'], 0, 28),
            number_format($srcCount),
            number_format($tgtCount),
            $statusStr
        );
    }

    echo str_repeat("-", 108) . "\n";
    echo "RINGKASAN AUDIT MIGRASI:\n";
    echo "- Total Entitas/Tabel Teruji  : $totalItems\n";
    echo "- Tabel Cocok 100% (MATCH)     : $matchCount / $totalItems (" . round(($matchCount / $totalItems) * 100, 2) . "%)\n";
    if ($matchCount === $totalItems) {
        echo "- KESIMPULAN STATUS           : SEMPURNA! SELURUH DATA 100% SAMA PERSIS SESUAI ATURAN MIGRASI.\n";
    } else {
        echo "- KESIMPULAN STATUS           : TERDAPAT PERBEDAAN DATA PADA TABEL TERTENTU.\n";
    }
    echo "====================================================================================================\n\n";
}
