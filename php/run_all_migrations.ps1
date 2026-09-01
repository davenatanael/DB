Write-Host '========================================================' -ForegroundColor Cyan
Write-Host '       MEMULAI SELURUH PROSES MIGRASI DATA' -ForegroundColor Cyan
Write-Host '========================================================' -ForegroundColor Cyan
Write-Host ''
Set-Location -Path $PSScriptRoot

$scripts = @(
    @{ Title = '[1/24] Migrasi Roles'; Script = 'migrate_role.php' },
    @{ Title = '[2/24] Migrasi Privileges has Roles'; Script = 'migrate_privileges_has_roles.php' },
    @{ Title = '[3/24] Migrasi Users'; Script = 'migrate_user.php' },
    @{ Title = '[4/24] Migrasi Bank Accounts'; Script = 'migrate_bank_accounts.php' },
    @{ Title = '[5/24] Integrasi Bank Accounts ke Users'; Script = 'integrate_bankAccounts_users.php' },
    @{ Title = '[6/24] Migrasi Geografis (Regions, Countries, States, Cities)'; Script = 'migrate_geo.php' },
    @{ Title = '[7/24] Migrasi Agents (Korwil, Koordinator, Consultant, School)'; Script = 'migrate_agents.php' },
    @{ Title = '[8/24] Migrasi Universitas (Master)'; Script = 'migrate_univ.php' },
    @{ Title = '[9/24] Migrasi Relasi & Program Universitas'; Script = 'migrate_univ_relation.php' },
    @{ Title = '[10/24] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)'; Script = 'migrate_univ_accomodations.php' },
    @{ Title = '[11/24] Migrasi Fasilitas Universitas (Master Kategori & Has Facilities)'; Script = 'migrate_univ_facilities.php' },
    @{ Title = '[12/24] Migrasi Students'; Script = 'migrate_student.php' },
    @{ Title = '[13/24] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)'; Script = 'migrate_student_agents.php' },
    @{ Title = '[14/24] Migrasi Education Backgrounds'; Script = 'migrate_student_edu.php' },
    @{ Title = '[15/24] Migrasi Companions & Relasi Orang Tua'; Script = 'migrate_companions.php' },
    @{ Title = '[16/24] Migrasi Student Files & Types'; Script = 'migrate_student_files.php' },
    @{ Title = '[17/24] Migrasi Enrollments'; Script = 'migrate_enrollments.php' },
    @{ Title = '[18/24] Migrasi Enrollment Scholarships'; Script = 'migrate_enrollment_scholarships.php' },
    @{ Title = '[19/24] Migrasi Enrollment Timelines & Media'; Script = 'migrate_enrollment_timeline.php' },
    @{ Title = '[20/24] Migrasi Student Enrollment Documents & Programs'; Script = 'migrate_enrollment_document.php' },
    @{ Title = '[21/24] Migrasi Departure'; Script = 'migrate_departure.php' },
    @{ Title = '[22/24] Migrasi Payments'; Script = 'migrate_payments.php' },
    @{ Title = '[23/24] Migrasi Commissions'; Script = 'migrate_commission.php' },
    @{ Title = '[24/24] Migrasi Commission Details'; Script = 'migrate_commission_details.php' }
)

foreach ($s in $scripts) {
    Write-Host "`n$($s.Title)..." -ForegroundColor Yellow
    php $s.Script
}

Write-Host "`n========================================================" -ForegroundColor Green
Write-Host '       SEMUA MIGRASI SELESAI DIJALANKAN!' -ForegroundColor Green
Write-Host '========================================================' -ForegroundColor Green
Read-Host -Prompt 'Tekan Enter untuk keluar...'
