@echo off
title Menjalankan Migrasi Database db_ybaik_new
echo ========================================================
echo        MEMULAI SELURUH PROSES MIGRASI DATA
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/27] Migrasi Roles...
call php migrate_role.php
echo.

echo [2/27] Migrasi Privileges has Roles...
call php migrate_privileges_has_roles.php
echo.

echo [3/27] Migrasi Users...
call php migrate_user.php
echo.

echo [4/27] Migrasi Bank Accounts...
call php migrate_bank_accounts.php
echo.

echo [5/27] Integrasi Bank Accounts ke Users...
call php integrate_bankAccounts_users.php
echo.

echo [6/27] Migrasi Geografis (Regions, Countries, States, Cities)...
call php migrate_geo.php
echo.

echo [7/27] Migrasi Agents (Korwil, Koordinator, Consultant, School)...
call php migrate_agents.php
echo.

echo [8/27] Migrasi Universitas (Master)...
call php migrate_univ.php
echo.

echo [9/27] Migrasi Relasi ^& Program Universitas...
call php migrate_univ_relation.php
echo.

echo [10/27] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)...
call php migrate_univ_accomodations.php
echo.

echo [11/27] Migrasi Fasilitas Universitas (Master Kategori ^& Has Facilities)...
call php migrate_univ_facilities.php
echo.

echo [12/27] Migrasi Students...
call php migrate_student.php
echo.

echo [13/27] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)...
call php migrate_student_agents.php
echo.

echo [14/27] Migrasi Relasi Admin Students...
call php migrate_admin_students.php
echo.

echo [15/27] Migrasi Education Backgrounds...
call php migrate_student_edu.php
echo.

echo [16/27] Migrasi Companions ^& Relasi Orang Tua...
call php migrate_companions.php
echo.

echo [17/27] Migrasi Student Files ^& Types...
call php migrate_student_files.php
echo.

echo [18/27] Migrasi Enrollments...
call php migrate_enrollments.php
echo.

echo [19/27] Migrasi Enrollment Scholarships...
call php migrate_enrollment_scholarships.php
echo.

echo [20/27] Migrasi Enrollment Timelines ^& Media...
call php migrate_enrollment_timeline.php
echo.

echo [21/27] Migrasi Student Enrollment Documents ^& Programs...
call php migrate_enrollment_document.php
echo.

echo [22/27] Migrasi Enrollment Examinations...
call php migrate_enrollment_examinations.php
echo.

echo [23/27] Migrasi Departure...
call php migrate_departure.php
echo.

echo [24/27] Migrasi Payments...
call php migrate_payments.php
echo.

echo [25/27] Migrasi Commissions...
call php migrate_commission.php
echo.

echo [26/27] Migrasi Commission Details...
call php migrate_commission_details.php
echo.

echo [27/27] Migrasi Chat (Chats, Chat Users, Chat Messages)...
call php migrate_chat.php
echo.

echo ========================================================
echo        SEMUA MIGRASI SELESAI DIJALANKAN!
echo ========================================================
