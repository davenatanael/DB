<?php

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

    // Nonaktifkan Foreign Key Checks dan relaksasi sql_mode untuk menangani zero-date ('0000-00-00 00:00:00')
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("SET sql_mode = ''");

    // ==========================================
    // 1. MIGRASI DATA regions
    // ==========================================
    echo "Memulai migrasi tabel regions...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`regions`");

    $migrateRegionsSql = "
        INSERT INTO `$targetDb`.`regions` (
            `id`, `name`, `translations`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        )
        SELECT
            `id`, `name`, `translations`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        FROM `$sourceDb`.`regions`
    ";
    $affectedRegions = $pdo->exec($migrateRegionsSql);
    echo "-> Sukses: $affectedRegions data pada tabel regions berhasil dimigrasi.\n\n";

    // ==========================================
    // 2. MIGRASI DATA subregions (FK -> regions)
    // ==========================================
    echo "Memulai migrasi tabel subregions...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`subregions`");

    $migrateSubregionsSql = "
        INSERT INTO `$targetDb`.`subregions` (
            `id`, `name`, `translations`, `region_id`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        )
        SELECT
            `id`, `name`, `translations`, `region_id`, `flag`, `wikiDataId`, `created_at`, `updated_at`
        FROM `$sourceDb`.`subregions`
    ";
    $affectedSubregions = $pdo->exec($migrateSubregionsSql);
    echo "-> Sukses: $affectedSubregions data pada tabel subregions berhasil dimigrasi.\n\n";

    // ==========================================
    // 3. MIGRASI DATA countries (FK -> regions, subregions)
    // ==========================================
    echo "Memulai migrasi tabel countries...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`countries`");

    $migrateCountriesSql = "
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
    $affectedCountries = $pdo->exec($migrateCountriesSql);
    echo "-> Sukses: $affectedCountries data pada tabel countries berhasil dimigrasi.\n\n";

    // ==========================================
    // 4. MIGRASI DATA states (FK -> countries)
    // ==========================================
    echo "Memulai migrasi tabel states...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`states`");

    $migrateStatesSql = "
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
    $affectedStates = $pdo->exec($migrateStatesSql);
    echo "-> Sukses: $affectedStates data pada tabel states berhasil dimigrasi.\n\n";

    // ==========================================
    // 5. MIGRASI DATA cities (FK -> states, countries)
    // ==========================================
    echo "Memulai migrasi tabel cities (data besar, ~152rb baris, mohon tunggu)...\n";
    $pdo->exec("TRUNCATE TABLE `$targetDb`.`cities`");

    $migrateCitiesSql = "
        INSERT INTO `$targetDb`.`cities` (
            `id`, `name`, `state_id`, `state_code`, `country_id`, `country_code`,
            `latitude`, `longitude`, `wikiDataId`, `flag`, `created_at`, `updated_at`, `deleted_at`
        )
        SELECT
            `id`, `name`, `state_id`, `state_code`, `country_id`, `country_code`,
            `latitude`, `longitude`, `wikiDataId`, `flag`, `created_at`, `updated_at`, `deleted_at`
        FROM `$sourceDb`.`cities`
    ";
    $affectedCities = $pdo->exec($migrateCitiesSql);
    echo "-> Sukses: $affectedCities data pada tabel cities berhasil dimigrasi.\n\n";

    // Aktifkan kembali Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "=== Migrasi regions, subregions, countries, states, dan cities Selesai ===\n";
    echo "Total: regions=$affectedRegions, subregions=$affectedSubregions, countries=$affectedCountries, states=$affectedStates, cities=$affectedCities\n";

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error migrasi: " . $e->getMessage() . "\n";
}