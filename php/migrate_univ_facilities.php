<?php

/**
 * migrate_univ_facilities.php
 *
 * Migrasi data fasilitas universitas ke skema baru (db_ybaik_new).
 *
 * TAHAP 1: univ_facilities (Master Kategori)
 *   Target: db_ybaik_new.univ_facilities
 *   Mengisi 19 kategori fasilitas standar (Library, Sport Facilities, Canteen, dsb.)
 *
 * TAHAP 2: univ_has_facilities (Data Fasilitas Universitas Lengkap)
 *   Sumber: outclassco_marketing.univ_facilities_details JOIN outclassco_marketing.univ_facilities
 *   Target: db_ybaik_new.univ_has_facilities
 *   Menghubungkan univ_id dengan univ_facilities_id (kategori 1..19 atau NULL jika tidak yakin),
 *   nama detail fasilitas, foto/image, dan timestamps.
 *
 * PRASYARAT: universities HARUS sudah dimigrasikan duluan.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$sourceDb = 'outclassco_marketing';
$targetDb = 'db_ybaik_new';

$categories = [
    1 => 'Library',
    2 => 'Sport Facilities',
    3 => 'Canteen',
    4 => 'Classroom',
    5 => 'Study Facilities',
    6 => 'Museum',
    7 => 'Gymnasium',
    8 => 'Campus Building',
    9 => 'Theater/Studio',
    10 => 'Swimming Pool',
    11 => 'Laboratory',
    12 => 'Other',
    13 => 'Campus Services',
    14 => 'Outdoor Area',
    15 => 'Lake',
    16 => 'Hospital',
    17 => 'Dormitory/Accommodation',
    18 => 'Bank',
    19 => 'Gate',
];

$categoryMap = array_flip($categories);

function mapFacilityNameToCategoryId($name, $categoryMap) {
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

    // Jika tidak yakin atau tidak ada di kategori yang ada, kembalikan NULL
    return null;
}

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "====================================================================\n";
    echo "    MEMULAI MIGRASI DATA FASILITAS UNIVERSITAS LENGKAP              \n";
    echo "====================================================================\n\n";

    // Pastikan struktur tabel univ_has_facilities mendukung id AUTO_INCREMENT dan univ_facilities_id NULL
    $checkId = $pdo->query("SHOW COLUMNS FROM `$targetDb`.`univ_has_facilities` LIKE 'id'")->fetch();
    if (!$checkId) {
        echo "Menyesuaikan struktur tabel `univ_has_facilities`...\n";
        $pdo->exec("
            ALTER TABLE `$targetDb`.`univ_has_facilities`
            MODIFY COLUMN `univ_facilities_id` BIGINT(20) NULL,
            MODIFY COLUMN `name` VARCHAR(255) NULL,
            DROP PRIMARY KEY,
            ADD COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST
        ");
        echo "-> Struktur tabel `univ_has_facilities` berhasil disesuaikan.\n\n";
    }

    // Kosongkan tabel target
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_has_facilities`");
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`univ_facilities`");
    echo "-> Tabel `univ_has_facilities` dan `univ_facilities` berhasil dikosongkan.\n\n";

    // =========================================================================
    // TAHAP 1 : MEMASUKKAN 19 MASTER KATEGORI FASILITAS (univ_facilities)
    // =========================================================================
    echo "1. Memasukkan 19 master kategori fasilitas ke univ_facilities...\n";
    $insertCatStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`univ_facilities` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`)
        VALUES (:id, :name, NOW(), NOW(), NULL)
    ");

    $countCat = 0;
    foreach ($categories as $id => $name) {
        $insertCatStmt->execute([
            ':id'   => $id,
            ':name' => $name,
        ]);
        $countCat++;
    }
    echo "   -> Berhasil memasukkan $countCat master kategori fasilitas.\n\n";

    // =========================================================================
    // TAHAP 2 : MEMIGRASI SELURUH DATA FASILITAS KE univ_has_facilities
    // =========================================================================
    echo "2. Memigrasi seluruh data fasilitas ke univ_has_facilities...\n";

    // Ambil seluruh data detail fasilitas dari DB lama bersama nama fasilitas induknya
    $sourceQuery = "
        SELECT 
            ufd.id AS detail_id,
            uf.univ_id,
            uf.name AS fac_name,
            ufd.name AS detail_name,
            ufd.image,
            ufd.created_at,
            ufd.updated_at,
            ufd.deleted_at
        FROM `$sourceDb`.`univ_facilities_details` ufd
        JOIN `$sourceDb`.`univ_facilities` uf ON ufd.`univ_facilities_id` = uf.`id`
        ORDER BY ufd.`id` ASC
    ";
    $sourceRows = $pdo->query($sourceQuery)->fetchAll();
    $totalSource = count($sourceRows);

    $insertStmt = $pdo->prepare("
        INSERT INTO `$targetDb`.`univ_has_facilities` (
            `id`, `univ_id`, `univ_facilities_id`, `name`, `image`,
            `created_at`, `updated_at`, `deleted_at`
        ) VALUES (
            :id, :univ_id, :univ_facilities_id, :name, :image,
            :created_at, :updated_at, :deleted_at
        )
    ");

    $inserted = 0;
    $nullCategories = 0;
    foreach ($sourceRows as $row) {
        $categoryId = mapFacilityNameToCategoryId($row['fac_name'], $categoryMap);
        if ($categoryId === null) {
            $nullCategories++;
        }

        $name = !empty($row['detail_name']) ? trim($row['detail_name']) : trim($row['fac_name']);
        $image = !empty($row['image']) ? trim($row['image']) : null;
        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
        $updatedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
        $deletedAt = !empty($row['deleted_at']) ? $row['deleted_at'] : null;

        $insertStmt->execute([
            ':id'                 => $row['detail_id'],
            ':univ_id'            => $row['univ_id'],
            ':univ_facilities_id' => $categoryId,
            ':name'               => $name,
            ':image'              => $image,
            ':created_at'         => $createdAt,
            ':updated_at'         => $updatedAt,
            ':deleted_at'         => $deletedAt,
        ]);
        $inserted++;
    }

    $totalInTarget = (int) $pdo->query("SELECT COUNT(*) c FROM `$targetDb`.`univ_has_facilities`")->fetch()['c'];

    echo "   -> Total data detail di sumber       : $totalSource\n";
    echo "   -> Total berhasil dimasukkan ke target: $inserted\n";
    echo "   -> Data dengan kategori NULL (tidak yakin/lainnya): $nullCategories\n";
    echo "   -> Total baris di tabel target       : $totalInTarget\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n====================================================================\n";
    echo "    MIGRASI DATA FASILITAS UNIVERSITAS SELESAI LENGKAP!             \n";
    echo "====================================================================\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
