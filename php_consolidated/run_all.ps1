Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "  MENJALANKAN KONSOLIDASI SELURUH MIGRASI (5 MODUL)" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host ""

Set-Location -Path $PSScriptRoot
php run_all.php
