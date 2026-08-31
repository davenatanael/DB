@echo off
title Menjalankan Migrasi Database db_ybaik_new
echo ========================================================
echo        MEMULAI SELURUH PROSES MIGRASI DATA
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/20] Migrasi Roles...
call php migrate_role.php
echo.

echo [2/20] Migrasi Privileges has Roles...
call php migrate_privileges_has_roles.php
echo.

echo [3/20] Migrasi Users...
call php migrate_user.php
echo.

echo [4/20] Migrasi Bank Accounts...
call php migrate_bank_accounts.php
echo.

echo [5/20] Integrasi Bank Accounts ke Users...
call php integrate_bankAccounts_users.php
echo.

echo [6/20] Migrasi Geografis (Regions, Countries, States, Cities)...
call php migrate_geo.php
echo.

echo [7/20] Migrasi Agents (Korwil, Koordinator, Consultant, School)...
call php migrate_agents.php
echo.

echo [8/20] Migrasi Universitas (Master)...
call php migrate_univ.php
echo.

echo [9/20] Migrasi Relasi & Program Universitas...
call php migrate_univ_relation.php
echo.

echo [10/20] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)...
call php migrate_univ_accomodations.php
echo.

echo [11/20] Migrasi Students...
call php migrate_student.php
echo.

echo [12/20] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)...
call php migrate_student_agents.php
echo.

echo [13/20] Migrasi Education Backgrounds...
call php migrate_student_edu.php
echo.

echo [14/20] Migrasi Companions ^& Relasi Orang Tua...
call php migrate_companions.php
echo.

echo [15/20] Migrasi Student Files ^& Types...
call php migrate_student_files.php
echo.

echo [16/20] Migrasi Enrollments...
call php migrate_enrollments.php
echo.

echo [17/20] Migrasi Enrollment Scholarships...
call php migrate_enrollment_scholarships.php
echo.

echo [18/20] Migrasi Payments...
call php migrate_payments.php
echo.

echo [19/20] Migrasi Commissions...
call php migrate_commission.php
echo.

echo [20/20] Migrasi Commission Details...
call php migrate_commission_details.php
echo.

echo ========================================================
echo        SEMUA MIGRASI SELESAI DIJALANKAN!
echo ========================================================
pause
