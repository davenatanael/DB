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

echo [1/37] Migrasi Roles ^& Privileges...
call php migrate_role.php
echo.

echo [2/37] Migrasi Privileges has Roles...
call php migrate_privileges_has_roles.php
echo.

echo [3/37] Migrasi Users...
call php migrate_user.php
echo.

echo [4/37] Migrasi Bank Accounts...
call php migrate_bank_accounts.php
echo.

echo [5/37] Integrasi Bank Accounts ke Users...
call php integrate_bankAccounts_users.php
echo.

echo [6/37] Migrasi Geografis (Regions, Countries, States, Cities)...
call php migrate_geo.php
echo.

echo [7/37] Migrasi Agents (Korwil, Koordinator, Consultant, School)...
call php migrate_agents.php
echo.

echo [8/37] Migrasi Sekolah (Master ^& Relasi Agents)...
call php migrate_sekolah.php
echo.

echo [9/37] Migrasi Universitas (Master)...
call php migrate_univ.php
echo.

echo [10/37] Migrasi Relasi ^& Program Universitas...
call php migrate_univ_relation.php
echo.

echo [11/37] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)...
call php migrate_univ_accomodations.php
echo.

echo [12/37] Migrasi Fasilitas Universitas (Master Kategori ^& Has Facilities)...
call php migrate_univ_facilities.php
echo.

echo [13/37] Migrasi Students...
call php migrate_student.php
echo.

echo [14/37] Update Relasi Sekolah ke Students (sekolah_id)...
call php migrate_school_id_table_students.php
echo.

echo [15/37] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)...
call php migrate_student_agents.php
echo.

echo [16/37] Migrasi Relasi Admin Students...
call php migrate_admin_students.php
echo.

echo [17/37] Migrasi Education Backgrounds...
call php migrate_student_edu.php
echo.

echo [18/37] Migrasi Companions ^& Relasi Orang Tua...
call php migrate_companions.php
echo.

echo [19/37] Migrasi Student Files ^& Types...
call php migrate_student_files.php
echo.

echo [20/37] Migrasi Enrollments...
call php migrate_enrollments.php
echo.

echo [21/37] Migrasi Enrollment Scholarships...
call php migrate_enrollment_scholarships.php
echo.

echo [22/37] Migrasi Enrollment Timelines ^& Media...
call php migrate_enrollment_timeline.php
echo.

echo [23/37] Migrasi Student Enrollment Documents ^& Programs...
call php migrate_enrollment_document.php
echo.

echo [24/37] Migrasi Enrollment Examinations...
call php migrate_enrollment_examinations.php
echo.

echo [25/37] Migrasi Departure...
call php migrate_departure.php
echo.

echo [26/37] Migrasi Payments...
call php migrate_payments.php
echo.

echo [27/37] Migrasi Student Relations (Favorites, File Types Program, Discounts, Student Payments)...
call php migrate_student_relations.php
echo.

echo [28/37] Migrasi Commissions...
call php migrate_commission.php
echo.

echo [29/37] Migrasi Commission Details...
call php migrate_commission_details.php
echo.

echo [30/37] Migrasi Chat (Chats, Chat Users, Chat Messages)...
call php migrate_chat.php
echo.

echo [31/37] Migrasi Employees (Employees, Kinerjas, Warnings)...
call php migrate_employees.php
echo.

echo [32/37] Migrasi Guests...
call php migrate_guests.php
echo.

echo [33/37] Migrasi Consultations (bergantung pada Guests)...
call php migrate_consultations.php
echo.

echo [34/37] Migrasi Locations...
call php migrate_locations.php
echo.

echo [35/37] Migrasi Notifications...
call php migrate_notifications.php
echo.

echo [36/37] Migrasi Kurs, Tests ^& Student Tests...
call php migrate_kurs_tests.php
echo.

echo [37/37] Migrasi Working Hours...
call php migrate_working_hours.php
echo.

echo ========================================================
echo        SEMUA MIGRASI SELESAI DIJALANKAN!
echo ========================================================
