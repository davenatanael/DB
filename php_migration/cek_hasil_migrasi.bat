@echo off
title Audit dan Pengecekan Validitas Data Migrasi Database
cls

cd /d "%~dp0"

:MENU
echo ===============================================================================
echo                SISTEM PENGECEKAN & AUDIT VALIDASI MIGRASI DATABASE
echo                Database Sumber : outclassco_marketing
echo                Database Target : db_ybaik_new
echo ===============================================================================
echo.
echo Silakan pilih opsi pengecekan yang diinginkan:
echo.
echo  [1] Jalankan SEMUA Pengecekan (Validasi 100%% Migrasi + Daftar Seluruh Tabel Target)
echo  [2] Validasi 100%% Data Migrasi (Detail per tabel dari run_all_migrations.bat)
echo  [3] Cek Jumlah Seluruh Tabel & Data di db_ybaik_new
echo  [4] Bersihkan / Hapus 14 Tabel Legacy (DROP TABLE Unused)
echo  [0] Keluar
echo.
echo ===============================================================================
set /p opt="Pilihan Anda (1/2/3/4/0): "

if "%opt%"=="1" goto RUN_ALL
if "%opt%"=="2" goto RUN_MIGRATION
if "%opt%"=="3" goto RUN_TARGET
if "%opt%"=="4" goto RUN_DROP
if "%opt%"=="0" goto EXIT_PROG

echo.
echo Pilihan tidak valid, silakan coba lagi.
timeout /t 2 >nul
cls
goto MENU

:RUN_ALL
cls
echo Menjalankan seluruh proses pengecekan dan validasi...
echo.
call php cek_validasi_migrasi.php all
goto FINISH

:RUN_MIGRATION
cls
echo Menjalankan validasi 100%% data migrasi berdasarkan run_all_migrations.bat...
echo.
call php cek_validasi_migrasi.php migration
goto FINISH

:RUN_TARGET
cls
echo Menampilkan seluruh daftar tabel dan jumlah data di db_ybaik_new...
echo.
call php cek_validasi_migrasi.php target_all
goto FINISH

:RUN_DROP
cls
echo Menjalankan penghapusan 14 tabel legacy / tidak terpakai...
echo.
call php drop_unused_tables.php
goto FINISH

:FINISH
echo.
echo ===============================================================================
echo Pengecekan selesai!
echo ===============================================================================
echo.
pause
cls
goto MENU

:EXIT_PROG
exit /b
