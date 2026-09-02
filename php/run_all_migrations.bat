@echo off
title Menjalankan Migrasi Database db_ybaik_new
echo ========================================================
echo        MEMULAI SELURUH PROSES MIGRASI DATA
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/32] Migrasi Roles...
call php migrate_role.php
echo.

echo [2/32] Migrasi Privileges has Roles...
call php migrate_privileges_has_roles.php
echo.

echo [3/32] Migrasi Users...
call php migrate_user.php
echo.

echo [4/32] Migrasi Bank Accounts...
call php migrate_bank_accounts.php
echo.

echo [5/32] Integrasi Bank Accounts ke Users...
call php integrate_bankAccounts_users.php
echo.

echo [6/32] Migrasi Geografis (Regions, Countries, States, Cities)...
call php migrate_geo.php
echo.

echo [7/32] Migrasi Agents (Korwil, Koordinator, Consultant, School)...
call php migrate_agents.php
echo.

echo [8/32] Migrasi Sekolah (Master ^& Relasi Agents)...
call php migrate_sekolah.php
echo.

echo [9/32] Migrasi Universitas (Master)...
call php migrate_univ.php
echo.

echo [10/32] Migrasi Relasi ^& Program Universitas...
call php migrate_univ_relation.php
echo.

echo [11/32] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)...
call php migrate_univ_accomodations.php
echo.

echo [12/32] Migrasi Fasilitas Universitas (Master Kategori ^& Has Facilities)...
call php migrate_univ_facilities.php
echo.

echo [13/32] Migrasi Students...
call php migrate_student.php
echo.

echo [14/32] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)...
call php migrate_student_agents.php
echo.

echo [15/32] Migrasi Relasi Admin Students...
call php migrate_admin_students.php
echo.

echo [16/32] Migrasi Education Backgrounds...
call php migrate_student_edu.php
echo.

echo [17/32] Migrasi Companions ^& Relasi Orang Tua...
call php migrate_companions.php
echo.

echo [18/32] Migrasi Student Files ^& Types...
call php migrate_student_files.php
echo.

echo [19/32] Migrasi Enrollments...
call php migrate_enrollments.php
echo.

echo [20/32] Migrasi Enrollment Scholarships...
call php migrate_enrollment_scholarships.php
echo.

echo [21/32] Migrasi Enrollment Timelines ^& Media...
call php migrate_enrollment_timeline.php
echo.

echo [22/32] Migrasi Student Enrollment Documents ^& Programs...
call php migrate_enrollment_document.php
echo.

echo [23/32] Migrasi Enrollment Examinations...
call php migrate_enrollment_examinations.php
echo.

echo [24/32] Migrasi Departure...
call php migrate_departure.php
echo.

echo [25/32] Migrasi Payments...
call php migrate_payments.php
echo.

echo [26/32] Migrasi Student Relations (Favorites, File Types Program, Discounts, Student Payments)...
call php migrate_student_relations.php
echo.

echo [27/32] Migrasi Commissions...
call php migrate_commission.php
echo.

echo [28/32] Migrasi Commission Details...
call php migrate_commission_details.php
echo.

echo [29/32] Migrasi Chat (Chats, Chat Users, Chat Messages)...
call php migrate_chat.php
echo.

echo [30/32] Migrasi Employees (Employees, Kinerjas, Warnings)...
call php migrate_employees.php
echo.

echo [31/32] Migrasi Guests...
call php migrate_guests.php
echo.

echo [32/32] Migrasi Consultations (bergantung pada Guests)...
call php migrate_consultations.php
echo.

echo ========================================================
echo        SEMUA MIGRASI SELESAI DIJALANKAN!
echo ========================================================
