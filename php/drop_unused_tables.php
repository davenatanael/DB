<?php
/**
 * drop_unused_tables.php
 *
 * Menghapus (DROP) 14 tabel kosong / legacy yang sudah tidak digunakan di db_ybaik_new.
 * Tabel yang TIDAK dihapus (dipertahankan karena akan dipakai atau masih aktif):
 * - companion_travel_historys
 * - kurs
 * - student_payment_discounts
 * - student_tests
 * - tests
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$targetDb = 'db_ybaik_new';

$tablesToDrop = [
    'consultants'               => 'Digantikan tabel agents',
    'consultant_komisis'        => 'Digantikan tabel commissions',
    'consultant_komisi_details' => 'Digantikan tabel commission_details',
    'customers'                 => 'Digantikan tabel users & agents',
    'customer_comments'         => 'Fitur legacy tidak digunakan',
    'customer_ratings'          => 'Fitur legacy tidak digunakan',
    'glossaries'                => 'Tabel legacy tidak digunakan',
    'kabupatens'                => 'Digantikan tabel cities di master geo',
    'koordinators'              => 'Digantikan tabel agents',
    'log_admin_students'        => 'Sudah menggunakan tabel admin_students',
    'payrolls'                  => 'Fitur legacy tidak digunakan',
    'penawarans'                => 'Fitur legacy tidak digunakan',
    'scholarship'               => 'Digantikan tabel univ_scholarships & enrollment_scholarships',
    'univ_facilities_details'   => 'Digantikan tabel univ_has_facilities & univ_facilities'
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$targetDb;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    echo "====================================================================\n";
    echo "       MENGHAPUS TABEL LEGACY / TIDAK TERPAKAI DI $targetDb\n";
    echo "====================================================================\n\n";

    $droppedCount = 0;
    foreach ($tablesToDrop as $table => $reason) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($check) {
            $pdo->exec("DROP TABLE IF EXISTS `$targetDb`.`$table`");
            echo "-> Berhasil menghapus tabel: `$table` ($reason)\n";
            $droppedCount++;
        } else {
            echo "-> Tabel `$table` sudah tidak ada (skip).\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Hitung sisa tabel saat ini
    $remainingTables = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$targetDb' AND table_type = 'BASE TABLE'")->fetchColumn();

    echo "\n====================================================================\n";
    echo "Selesai! Berhasil menghapus $droppedCount tabel legacy.\n";
    echo "Total sisa tabel di database `$targetDb` saat ini: $remainingTables tabel.\n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
    echo "Error: " . $e->getMessage() . "\n";
}
