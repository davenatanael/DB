@echo off
title Menjalankan Migrasi Database db_ybaik_new
echo ========================================================
echo        MEMULAI SELURUH PROSES MIGRASI DATA
echo ========================================================
echo.

cd /d "%~dp0"

echo Initialize Migration : drop data di tabel baru
call php init_migration.php
echo.

echo [1/34] Migrasi Roles ^& Privileges...
call php migrate_role.php
echo.

echo [2/34] Migrasi Privileges has Roles...
call php migrate_privileges_has_roles.php
echo.

echo [3/34] Migrasi Users...
call php migrate_user.php
echo.

echo [4/34] Migrasi Bank Accounts...
call php migrate_bank_accounts.php
echo.

echo [5/34] Integrasi Bank Accounts ke Users...
call php integrate_bankAccounts_users.php
echo.

echo [6/34] Migrasi Geografis (Regions, Countries, States, Cities)...
call php migrate_geo.php
echo.

echo [7/34] Migrasi Agents (Korwil, Koordinator, Consultant, School)...
call php migrate_agents.php
echo.

echo [8/34] Migrasi Sekolah (Master ^& Relasi Agents)...
call php migrate_sekolah.php
echo.

echo [9/34] Migrasi Universitas (Master)...
call php migrate_univ.php
echo.

echo [10/34] Migrasi Relasi ^& Program Universitas...
call php migrate_univ_relation.php
echo.

echo [11/34] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)...
call php migrate_univ_accomodations.php
echo.

echo [12/34] Migrasi Fasilitas Universitas (Master Kategori ^& Has Facilities)...
call php migrate_univ_facilities.php
echo.

echo [13/34] Migrasi Students...
call php migrate_student.php
echo.

echo [14/34] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)...
call php migrate_student_agents.php
echo.

echo [15/34] Migrasi Relasi Admin Students...
call php migrate_admin_students.php
echo.

echo [16/34] Migrasi Education Backgrounds...
call php migrate_student_edu.php
echo.

echo [17/34] Migrasi Companions ^& Relasi Orang Tua...
call php migrate_companions.php
echo.

echo [18/34] Migrasi Student Files ^& Types...
call php migrate_student_files.php
echo.

echo [19/34] Migrasi Enrollments...
call php migrate_enrollments.php
echo.

echo [20/34] Migrasi Enrollment Scholarships...
call php migrate_enrollment_scholarships.php
echo.

echo [21/34] Migrasi Enrollment Timelines ^& Media...
call php migrate_enrollment_timeline.php
echo.

echo [22/34] Migrasi Student Enrollment Documents ^& Programs...
call php migrate_enrollment_document.php
echo.

echo [23/34] Migrasi Enrollment Examinations...
call php migrate_enrollment_examinations.php
echo.

echo [24/34] Migrasi Departure...
call php migrate_departure.php
echo.

echo [25/34] Migrasi Payments...
call php migrate_payments.php
echo.

echo [26/34] Migrasi Student Relations (Favorites, File Types Program, Discounts, Student Payments)...
call php migrate_student_relations.php
echo.

echo [27/34] Migrasi Commissions...
call php migrate_commission.php
echo.

echo [28/34] Migrasi Commission Details...
call php migrate_commission_details.php
echo.

echo [29/34] Migrasi Chat (Chats, Chat Users, Chat Messages)...
call php migrate_chat.php
echo.

echo [30/34] Migrasi Employees (Employees, Kinerjas, Warnings)...
call php migrate_employees.php
echo.

echo [31/34] Migrasi Guests...
call php migrate_guests.php
echo.

echo [32/34] Migrasi Consultations (bergantung pada Guests)...
call php migrate_consultations.php
echo.

echo [33/34] Migrasi Locations...
call php migrate_locations.php
echo.

echo [34/34] Migrasi Notifications...
call php migrate_notifications.php
echo.

echo ========================================================
echo        SEMUA MIGRASI SELESAI DIJALANKAN!
echo ========================================================
