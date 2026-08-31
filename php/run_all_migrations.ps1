Write-Host '========================================================' -ForegroundColor Cyan
Write-Host '       MEMULAI SELURUH PROSES MIGRASI DATA' -ForegroundColor Cyan
Write-Host '========================================================' -ForegroundColor Cyan
Write-Host ''
Set-Location -Path $PSScriptRoot

$scripts = @(
    @{ Title = '[1/20] Migrasi Roles'; Script = 'migrate_role.php' },
    @{ Title = '[2/20] Migrasi Privileges has Roles'; Script = 'migrate_privileges_has_roles.php' },
    @{ Title = '[3/20] Migrasi Users'; Script = 'migrate_user.php' },
    @{ Title = '[4/20] Migrasi Bank Accounts'; Script = 'migrate_bank_accounts.php' },
    @{ Title = '[5/20] Integrasi Bank Accounts ke Users'; Script = 'integrate_bankAccounts_users.php' },
    @{ Title = '[6/20] Migrasi Geografis (Regions, Countries, States, Cities)'; Script = 'migrate_geo.php' },
    @{ Title = '[7/20] Migrasi Agents (Korwil, Koordinator, Consultant, School)'; Script = 'migrate_agents.php' },
    @{ Title = '[8/20] Migrasi Universitas (Master)'; Script = 'migrate_univ.php' },
    @{ Title = '[9/20] Migrasi Relasi & Program Universitas'; Script = 'migrate_univ_relation.php' },
    @{ Title = '[10/20] Migrasi Akomodasi Universitas (Accomodations, Details, Photos)'; Script = 'migrate_univ_accomodations.php' },
    @{ Title = '[11/20] Migrasi Students'; Script = 'migrate_student.php' },
    @{ Title = '[12/20] Migrasi Relasi Student Agents (Korwil, Koordinator, Consultant)'; Script = 'migrate_student_agents.php' },
    @{ Title = '[13/20] Migrasi Education Backgrounds'; Script = 'migrate_student_edu.php' },
    @{ Title = '[14/20] Migrasi Companions & Relasi Orang Tua'; Script = 'migrate_companions.php' },
    @{ Title = '[15/20] Migrasi Student Files & Types'; Script = 'migrate_student_files.php' },
    @{ Title = '[16/20] Migrasi Enrollments'; Script = 'migrate_enrollments.php' },
    @{ Title = '[17/20] Migrasi Enrollment Scholarships'; Script = 'migrate_enrollment_scholarships.php' },
    @{ Title = '[18/20] Migrasi Payments'; Script = 'migrate_payments.php' },
    @{ Title = '[19/20] Migrasi Commissions'; Script = 'migrate_commission.php' },
    @{ Title = '[20/20] Migrasi Commission Details'; Script = 'migrate_commission_details.php' }
)

foreach ($s in $scripts) {
    Write-Host "`n$($s.Title)..." -ForegroundColor Yellow
    php $s.Script
}

Write-Host "`n========================================================" -ForegroundColor Green
Write-Host '       SEMUA MIGRASI SELESAI DIJALANKAN!' -ForegroundColor Green
Write-Host '========================================================' -ForegroundColor Green
Read-Host -Prompt 'Tekan Enter untuk keluar...'
