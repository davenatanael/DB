Write-Host '========================================================' -ForegroundColor Cyan
Write-Host '       MEMULAI SELURUH PROSES MIGRASI DATA' -ForegroundColor Cyan
Write-Host '========================================================' -ForegroundColor Cyan
Write-Host ''
Set-Location -Path $PSScriptRoot

Write-Host "Initialize Migration: Reset / Drop data di database target..." -ForegroundColor Cyan
php init_migration.php
Write-Host ''

$scripts = @(
    @{ Title = '[1/37] Migrasi Roles & Privileges'; Script = 'migrate_role.php' },
    @{ Title = '[2/37] Migrasi Privileges has Roles'; Script = 'migrate_privileges_has_roles.php' },
    @{ Title = '[3/37] Migrasi Users'; Script = 'migrate_user.php' },
    @{ Title = '[4/37] Migrasi Bank Accounts'; Script = 'migrate_bank_accounts.php' },
    @{ Title = '[5/37] Integrasi Bank Accounts ke Users'; Script = 'integrate_bankAccounts_users.php' },
    @{ Title = '[6/37] Migrasi Geografis (Regions, Countries, States, Cities)'; Script = 'migrate_geo.php' },
    @{ Title = '[7/37] Migrasi Agents (Korwil, Koordinator, Consultant, School)'; Script = 'migrate_agents.php' },
    @{ Title = '[8/37] Migrasi Sekolah (Master & Relasi Agents)'; Script = 'migrate_sekolah.php' },
    @{ Title = '[9/37] Migrasi Universitas (Master)'; Script = 'migrate_univ.php' },
    @{ Title = '[10/37] Migrasi Relasi & Program Universitas'; Script = 'migrate_univ_relation.php' },
    @{ Title = '[11/37] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)'; Script = 'migrate_univ_accomodations.php' },
    @{ Title = '[12/37] Migrasi Fasilitas Universitas (Master Kategori & Has Facilities)'; Script = 'migrate_univ_facilities.php' },
    @{ Title = '[13/37] Migrasi Students'; Script = 'migrate_student.php' },
    @{ Title = '[14/37] Update Relasi Sekolah ke Students (sekolah_id)'; Script = 'migrate_school_id_table_students.php' },
    @{ Title = '[15/37] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)'; Script = 'migrate_student_agents.php' },
    @{ Title = '[16/37] Migrasi Relasi Admin Students'; Script = 'migrate_admin_students.php' },
    @{ Title = '[17/37] Migrasi Education Backgrounds'; Script = 'migrate_student_edu.php' },
    @{ Title = '[18/37] Migrasi Companions & Relasi Orang Tua'; Script = 'migrate_companions.php' },
    @{ Title = '[19/37] Migrasi Student Files & Types'; Script = 'migrate_student_files.php' },
    @{ Title = '[20/37] Migrasi Enrollments'; Script = 'migrate_enrollments.php' },
    @{ Title = '[21/37] Migrasi Enrollment Scholarships'; Script = 'migrate_enrollment_scholarships.php' },
    @{ Title = '[22/37] Migrasi Enrollment Timelines & Media'; Script = 'migrate_enrollment_timeline.php' },
    @{ Title = '[23/37] Migrasi Student Enrollment Documents & Programs'; Script = 'migrate_enrollment_document.php' },
    @{ Title = '[24/37] Migrasi Enrollment Examinations'; Script = 'migrate_enrollment_examinations.php' },
    @{ Title = '[25/37] Migrasi Departure'; Script = 'migrate_departure.php' },
    @{ Title = '[26/37] Migrasi Payments'; Script = 'migrate_payments.php' },
    @{ Title = '[27/37] Migrasi Student Relations (Favorites, File Types Program, Discounts, Student Payments)'; Script = 'migrate_student_relations.php' },
    @{ Title = '[28/37] Migrasi Commissions'; Script = 'migrate_commission.php' },
    @{ Title = '[29/37] Migrasi Commission Details'; Script = 'migrate_commission_details.php' },
    @{ Title = '[30/37] Migrasi Chat (Chats, Chat Users, Chat Messages)'; Script = 'migrate_chat.php' },
    @{ Title = '[31/37] Migrasi Employees (Employees, Kinerjas, Warnings)'; Script = 'migrate_employees.php' },
    @{ Title = '[32/37] Migrasi Guests'; Script = 'migrate_guests.php' },
    @{ Title = '[33/37] Migrasi Consultations (bergantung pada Guests)'; Script = 'migrate_consultations.php' },
    @{ Title = '[34/37] Migrasi Locations'; Script = 'migrate_locations.php' },
    @{ Title = '[35/37] Migrasi Notifications'; Script = 'migrate_notifications.php' },
    @{ Title = '[36/37] Migrasi Kurs, Tests & Student Tests'; Script = 'migrate_kurs_tests.php' },
    @{ Title = '[37/37] Migrasi Working Hours'; Script = 'migrate_working_hours.php' }
)

foreach ($s in $scripts) {
    Write-Host "`n$($s.Title)..." -ForegroundColor Yellow
    php $s.Script
}

Write-Host "`n========================================================" -ForegroundColor Green
Write-Host '       SEMUA MIGRASI SELESAI DIJALANKAN!' -ForegroundColor Green
Write-Host '========================================================' -ForegroundColor Green
