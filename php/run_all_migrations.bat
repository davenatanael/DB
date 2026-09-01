@echo off
title Menjalankan Migrasi Database db_ybaik_new
echo ========================================================
echo        MEMULAI SELURUH PROSES MIGRASI DATA
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/23] Migrasi Roles...
call php migrate_role.php
echo.

echo [2/23] Migrasi Privileges has Roles...
call php migrate_privileges_has_roles.php
echo.

echo [3/23] Migrasi Users...
call php migrate_user.php
echo.

echo [4/23] Migrasi Bank Accounts...
call php migrate_bank_accounts.php
echo.

echo [5/23] Integrasi Bank Accounts ke Users...
call php integrate_bankAccounts_users.php
echo.

echo [6/23] Migrasi Geografis (Regions, Countries, States, Cities)...
call php migrate_geo.php
echo.

echo [7/23] Migrasi Agents (Korwil, Koordinator, Consultant, School)...
call php migrate_agents.php
echo.

echo [8/23] Migrasi Universitas (Master)...
call php migrate_univ.php
echo.

echo [9/23] Migrasi Relasi ^& Program Universitas...
call php migrate_univ_relation.php
echo.

echo [10/23] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)...
call php migrate_univ_accomodations.php
echo.

echo [11/23] Migrasi Students...
call php migrate_student.php
echo.

echo [12/23] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)...
call php migrate_student_agents.php
echo.

echo [13/23] Migrasi Education Backgrounds...
call php migrate_student_edu.php
echo.

echo [14/23] Migrasi Companions ^& Relasi Orang Tua...
call php migrate_companions.php
echo.

echo [15/23] Migrasi Student Files ^& Types...
call php migrate_student_files.php
echo.

echo [16/23] Migrasi Enrollments...
call php migrate_enrollments.php
echo.

echo [17/23] Migrasi Enrollment Scholarships...
call php migrate_enrollment_scholarships.php
echo.

echo [18/23] Migrasi Enrollment Timelines ^& Media...
call php migrate_enrollment_timeline.php
echo.

echo [19/23] Migrasi Student Enrollment Documents ^& Programs...
call php migrate_enrollment_document.php
echo.

echo [20/23] Migrasi Departure...
call php migrate_departure.php
echo.

echo [21/23] Migrasi Payments...
call php migrate_payments.php
echo.

echo [22/23] Migrasi Commissions...
call php migrate_commission.php
echo.

echo [23/23] Migrasi Commission Details...
call php migrate_commission_details.php
echo.

echo ========================================================
echo        SEMUA MIGRASI SELESAI DIJALANKAN!
echo ========================================================
pause
