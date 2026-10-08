<?php
// cek_bank_accounts.php

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

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    echo "========================================================================================================\n";
    echo "                   PENGECEKAN DATA REKENING DARI 4 TABEL SUMBER ($sourceDb)\n";
    echo "========================================================================================================\n";

    // Query UNION dengan urutan yang sama persis seperti di migrate_bankaccount.php
    $sql = "
        SELECT 
            'consultants' AS asal_tabel,
            c.id AS id_asal,
            c.user_id,
            c.name AS nama_pemilik,
            LEFT(TRIM(c.nama_bank), 45) AS nama_bank,
            LEFT(TRIM(c.nomor_rekening), 45) AS nomor_rekening
        FROM `$sourceDb`.`consultants` c
        WHERE c.deleted_at IS NULL
          AND NULLIF(TRIM(c.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(c.nomor_rekening), '') IS NOT NULL

        UNION ALL

        SELECT 
            'koordinators' AS asal_tabel,
            k.id AS id_asal,
            k.user_id,
            k.name AS nama_pemilik,
            LEFT(TRIM(k.nama_bank), 45) AS nama_bank,
            LEFT(TRIM(k.nomor_rekening), 45) AS nomor_rekening
        FROM `$sourceDb`.`koordinators` k
        WHERE k.deleted_at IS NULL
          AND NULLIF(TRIM(k.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(k.nomor_rekening), '') IS NOT NULL

        UNION ALL

        SELECT 
            'korwils' AS asal_tabel,
            kw.id AS id_asal,
            kw.user_id,
            kw.name AS nama_pemilik,
            LEFT(TRIM(kw.nama_bank), 45) AS nama_bank,
            LEFT(TRIM(kw.nomor_rekening), 45) AS nomor_rekening
        FROM `$sourceDb`.`korwils` kw
        WHERE kw.deleted_at IS NULL
          AND NULLIF(TRIM(kw.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(kw.nomor_rekening), '') IS NOT NULL

        UNION ALL

        SELECT 
            'students' AS asal_tabel,
            s.id AS id_asal,
            cust.user_id,
            COALESCE(cust.name, '-') AS nama_pemilik,
            LEFT(TRIM(s.nama_bank), 45) AS nama_bank,
            LEFT(TRIM(s.nomor_rekening), 45) AS nomor_rekening
        FROM `$sourceDb`.`students` s
        LEFT JOIN `$sourceDb`.`customers` cust ON cust.id = s.customer_id
        WHERE s.deleted_at IS NULL
          AND NULLIF(TRIM(s.nama_bank), '') IS NOT NULL
          AND NULLIF(TRIM(s.nomor_rekening), '') IS NOT NULL
    ";

    $rows = $pdo->query($sql)->fetchAll();

    // Siapkan statement untuk mengecek akun di db_ybaik_new.users
    $stmtCheckUser = $pdo->prepare("SELECT id, name, email, bank_accounts_id FROM `$targetDb`.`users` WHERE id = :id");

    printf(
        "%-4s | %-13s | %-7s | %-7s | %-25s | %-8s | %-16s | %-20s\n",
        "NO", "ASAL TABEL", "ID ASAL", "USER ID", "NAMA PEMILIK", "BANK", "NO REKENING", "STATUS DI TARGET USERS"
    );
    echo str_repeat("-", 120) . "\n";

    $no = 1;
    $summary = [
        'consultants'  => 0,
        'koordinators' => 0,
        'korwils'      => 0,
        'students'     => 0,
        'linked'       => 0,
        'unlinked'     => 0,
    ];

    foreach ($rows as $r) {
        $userId = $r['user_id'];
        $statusTarget = "User ID Kosong/NULL";

        if (!empty($userId)) {
            $stmtCheckUser->execute([':id' => $userId]);
            $targetUser = $stmtCheckUser->fetch();

            if ($targetUser) {
                if (!empty($targetUser['bank_accounts_id'])) {
                    $statusTarget = "Linked (Bank ID: {$targetUser['bank_accounts_id']})";
                    $summary['linked']++;
                } else {
                    $statusTarget = "User Ada (Belum Link)";
                    $summary['unlinked']++;
                }
            } else {
                $statusTarget = "User ID Tdk Ditemukan";
                $summary['unlinked']++;
            }
        } else {
            $summary['unlinked']++;
        }

        $summary[$r['asal_tabel']]++;

        printf(
            "%-4d | %-13s | %-7s | %-7s | %-25s | %-8s | %-16s | %-20s\n",
            $no++,
            $r['asal_tabel'],
            $r['id_asal'],
            $userId ?? 'NULL',
            mb_strimwidth($r['nama_pemilik'], 0, 25, '..'),
            $r['nama_bank'],
            $r['nomor_rekening'],
            $statusTarget
        );
    }

    echo str_repeat("=", 120) . "\n";
    echo "RANGKUMAN HASIL PENGECEKAN:\n";
    echo " - Total baris data diambil  : " . count($rows) . " baris\n";
    echo "   * Dari consultants         : {$summary['consultants']} data\n";
    echo "   * Dari koordinators        : {$summary['koordinators']} data\n";
    echo "   * Dari korwils             : {$summary['korwils']} data\n";
    echo "   * Dari students            : {$summary['students']} data\n";
    echo " - Status di $targetDb.users:\n";
    echo "   * Berhasil ter-link        : {$summary['linked']} users\n";
    echo "   * Belum ter-link / Tanpa ID: {$summary['unlinked']} users\n";
    echo "========================================================================================================\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}