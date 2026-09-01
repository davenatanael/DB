Write-Host '========================================================' -ForegroundColor Cyan
Write-Host '       MEMULAI SELURUH PROSES MIGRASI DATA' -ForegroundColor Cyan
Write-Host '========================================================' -ForegroundColor Cyan
Write-Host ''
Set-Location -Path $PSScriptRoot

$scripts = @(
    @{ Title = '[1/27] Migrasi Roles'; Script = 'migrate_role.php' },
    @{ Title = '[2/27] Migrasi Privileges has Roles'; Script = 'migrate_privileges_has_roles.php' },
    @{ Title = '[3/27] Migrasi Users'; Script = 'migrate_user.php' },
    @{ Title = '[4/27] Migrasi Bank Accounts'; Script = 'migrate_bank_accounts.php' },
    @{ Title = '[5/27] Integrasi Bank Accounts ke Users'; Script = 'integrate_bankAccounts_users.php' },
    @{ Title = '[6/27] Migrasi Geografis (Regions, Countries, States, Cities)'; Script = 'migrate_geo.php' },
    @{ Title = '[7/27] Migrasi Agents (Korwil, Koordinator, Consultant, School)'; Script = 'migrate_agents.php' },
    @{ Title = '[8/27] Migrasi Universitas (Master)'; Script = 'migrate_univ.php' },
    @{ Title = '[9/27] Migrasi Relasi & Program Universitas'; Script = 'migrate_univ_relation.php' },
    @{ Title = '[10/27] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)'; Script = 'migrate_univ_accomodations.php' },
    @{ Title = '[11/27] Migrasi Fasilitas Universitas (Master Kategori & Has Facilities)'; Script = 'migrate_univ_facilities.php' },
    @{ Title = '[12/27] Migrasi Students'; Script = 'migrate_student.php' },
    @{ Title = '[13/27] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)'; Script = 'migrate_student_agents.php' },
    @{ Title = '[14/27] Migrasi Relasi Admin Students'; Script = 'migrate_admin_students.php' },
    @{ Title = '[15/27] Migrasi Education Backgrounds'; Script = 'migrate_student_edu.php' },
    @{ Title = '[16/27] Migrasi Companions & Relasi Orang Tua'; Script = 'migrate_companions.php' },
    @{ Title = '[17/27] Migrasi Student Files & Types'; Script = 'migrate_student_files.php' },
    @{ Title = '[18/27] Migrasi Enrollments'; Script = 'migrate_enrollments.php' },
    @{ Title = '[19/27] Migrasi Enrollment Scholarships'; Script = 'migrate_enrollment_scholarships.php' },
    @{ Title = '[20/27] Migrasi Enrollment Timelines & Media'; Script = 'migrate_enrollment_timeline.php' },
    @{ Title = '[21/27] Migrasi Student Enrollment Documents & Programs'; Script = 'migrate_enrollment_document.php' },
    @{ Title = '[22/27] Migrasi Enrollment Examinations'; Script = 'migrate_enrollment_examinations.php' },
    @{ Title = '[23/27] Migrasi Departure'; Script = 'migrate_departure.php' },
    @{ Title = '[24/27] Migrasi Payments'; Script = 'migrate_payments.php' },
    @{ Title = '[25/27] Migrasi Commissions'; Script = 'migrate_commission.php' },
    @{ Title = '[26/27] Migrasi Commission Details'; Script = 'migrate_commission_details.php' },
    @{ Title = '[27/27] Migrasi Chat (Chats, Chat Users, Chat Messages)'; Script = 'migrate_chat.php' }
)

foreach ($s in $scripts) {
    Write-Host "`n$($s.Title)..." -ForegroundColor Yellow
    php $s.Script
}

Write-Host "`n========================================================" -ForegroundColor Green
Write-Host '       SEMUA MIGRASI SELESAI DIJALANKAN!' -ForegroundColor Green
Write-Host '========================================================' -ForegroundColor Green
