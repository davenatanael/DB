@echo off
title Menjalankan Migrasi Database db_ybaik_new
echo ========================================================
echo        MEMULAI SELURUH PROSES MIGRASI DATA
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/13] Migrasi Roles...
php migrate_role.php
echo.

echo [2/13] Migrasi Users...
php migrate_user.php
echo.

echo [3/13] Migrasi Bank Accounts...
php migrate_bank_accounts.php
echo.

echo [4/13] Integrasi Bank Accounts ke Users...
php integrate_bankAccounts_users.php
echo.

echo [5/13] Migrasi Geografis (Regions, Countries, States, Cities)...
php migrate_geo.php
echo.

echo [6/13] Migrasi Agents (Korwil, Koordinator, Consultant, School)...
php migrate_agents.php
echo.

echo [7/13] Migrasi Universitas (Master)...
php migrate_univ.php
echo.

echo [8/13] Migrasi Relasi & Program Universitas...
php migrate_univ_relation.php
echo.

echo [9/13] Migrasi Students...
php migrate_student.php
echo.

echo [10/13] Migrasi Education Backgrounds...
php migrate_student_edu.php
echo.

echo [11/13] Migrasi Student Files & Types...
php migrate_student_files.php
echo.

echo [12/13] Migrasi Payments...
php migrate_payments.php
echo.

echo [13/13] Migrasi Commissions...
php migrate_commission.php
echo.

echo ========================================================
echo        SEMUA MIGRASI SELESAI DIJALANKAN!
echo ========================================================
pause
