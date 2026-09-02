Write-Host '========================================================' -ForegroundColor Cyan
Write-Host '       MEMULAI SELURUH PROSES MIGRASI DATA' -ForegroundColor Cyan
Write-Host '========================================================' -ForegroundColor Cyan
Write-Host ''
Set-Location -Path $PSScriptRoot

$scripts = @(
    @{ Title = '[1/32] Migrasi Roles'; Script = 'migrate_role.php' },
    @{ Title = '[2/32] Migrasi Privileges has Roles'; Script = 'migrate_privileges_has_roles.php' },
    @{ Title = '[3/32] Migrasi Users'; Script = 'migrate_user.php' },
    @{ Title = '[4/32] Migrasi Bank Accounts'; Script = 'migrate_bank_accounts.php' },
    @{ Title = '[5/32] Integrasi Bank Accounts ke Users'; Script = 'integrate_bankAccounts_users.php' },
    @{ Title = '[6/32] Migrasi Geografis (Regions, Countries, States, Cities)'; Script = 'migrate_geo.php' },
    @{ Title = '[7/32] Migrasi Agents (Korwil, Koordinator, Consultant, School)'; Script = 'migrate_agents.php' },
    @{ Title = '[8/32] Migrasi Sekolah (Master & Relasi Agents)'; Script = 'migrate_sekolah.php' },
    @{ Title = '[9/32] Migrasi Universitas (Master)'; Script = 'migrate_univ.php' },
    @{ Title = '[10/32] Migrasi Relasi & Program Universitas'; Script = 'migrate_univ_relation.php' },
    @{ Title = '[11/32] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)'; Script = 'migrate_univ_accomodations.php' },
    @{ Title = '[12/32] Migrasi Fasilitas Universitas (Master Kategori & Has Facilities)'; Script = 'migrate_univ_facilities.php' },
    @{ Title = '[13/32] Migrasi Students'; Script = 'migrate_student.php' },
    @{ Title = '[14/32] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)'; Script = 'migrate_student_agents.php' },
    @{ Title = '[15/32] Migrasi Relasi Admin Students'; Script = 'migrate_admin_students.php' },
    @{ Title = '[16/32] Migrasi Education Backgrounds'; Script = 'migrate_student_edu.php' },
    @{ Title = '[17/32] Migrasi Companions & Relasi Orang Tua'; Script = 'migrate_companions.php' },
    @{ Title = '[18/32] Migrasi Student Files & Types'; Script = 'migrate_student_files.php' },
    @{ Title = '[19/32] Migrasi Enrollments'; Script = 'migrate_enrollments.php' },
    @{ Title = '[20/32] Migrasi Enrollment Scholarships'; Script = 'migrate_enrollment_scholarships.php' },
    @{ Title = '[21/32] Migrasi Enrollment Timelines & Media'; Script = 'migrate_enrollment_timeline.php' },
    @{ Title = '[22/32] Migrasi Student Enrollment Documents & Programs'; Script = 'migrate_enrollment_document.php' },
    @{ Title = '[23/32] Migrasi Enrollment Examinations'; Script = 'migrate_enrollment_examinations.php' },
    @{ Title = '[24/32] Migrasi Departure'; Script = 'migrate_departure.php' },
    @{ Title = '[25/32] Migrasi Payments'; Script = 'migrate_payments.php' },
    @{ Title = '[26/32] Migrasi Student Relations (Favorites, File Types Program, Discounts, Student Payments)'; Script = 'migrate_student_relations.php' },
    @{ Title = '[27/32] Migrasi Commissions'; Script = 'migrate_commission.php' },
    @{ Title = '[28/32] Migrasi Commission Details'; Script = 'migrate_commission_details.php' },
    @{ Title = '[29/32] Migrasi Chat (Chats, Chat Users, Chat Messages)'; Script = 'migrate_chat.php' },
    @{ Title = '[30/32] Migrasi Employees (Employees, Kinerjas, Warnings)'; Script = 'migrate_employees.php' },
    @{ Title = '[31/32] Migrasi Guests'; Script = 'migrate_guests.php' },
    @{ Title = '[32/32] Migrasi Consultations (bergantung pada Guests)'; Script = 'migrate_consultations.php' }
)

foreach ($s in $scripts) {
    Write-Host "`n$($s.Title)..." -ForegroundColor Yellow
    php $s.Script
}

Write-Host "`n========================================================" -ForegroundColor Green
Write-Host '       SEMUA MIGRASI SELESAI DIJALANKAN!' -ForegroundColor Green
Write-Host '========================================================' -ForegroundColor Green
