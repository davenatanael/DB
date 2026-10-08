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
 */

require_once __DIR__ . '/../php_consolidated/verify_migration_validity.php';
