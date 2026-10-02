@echo off
setlocal enabledelayedexpansion
title Laravel Migration Generator - yBaik Redesigned

:: Pastikan dijalankan di root project Laravel
if not exist artisan (
    echo [ERROR] File 'artisan' tidak ditemukan!
    echo Pastikan file .bat ini diletakkan di root folder project Laravel Anda.
    echo.
    pause
    exit /b
)

:menu
cls
echo ===================================================================
echo       GENERATOR MIGRATION LARAVEL - YBAIK REDESIGNED (61 TABEL)
echo ===================================================================
echo.
echo Pilih Tier yang ingin dibuat (Maksimal 5 tabel per bagian):
echo.
echo   [1] TIER 1: Core System ^& Pengguna (5 Tabel)
echo   [2] TIER 2: Master Wilayah ^& Lokasi (5 Tabel)
echo   [3] TIER 3: SDM, Agen ^& Komisi (Total 6 Tabel - 2 Bagian)
echo   [4] TIER 4: Master Kampus ^& Sekolah (Total 15 Tabel - 3 Bagian)
echo   [5] TIER 5: Mahasiswa ^& Pendamping (Total 9 Tabel - 2 Bagian)
echo   [6] TIER 6: Dokumen ^& Finansial (Total 9 Tabel - 2 Bagian)
echo   [7] TIER 7: Program Pendaftaran / Enrollments (Total 8 Tabel - 2 Bagian)
echo   [8] TIER 8: Keberangkatan ^& Chat (4 Tabel)
echo.
echo   [A] JALANKAN SEMUA TIER BERTAHAP
echo   [X] Keluar
echo ===================================================================
set /p pilihan="Pilih menu [1-8 / A / X]: "

if /i "%pilihan%"=="1" goto tier1
if /i "%pilihan%"=="2" goto tier2
if /i "%pilihan%"=="3" goto tier3
if /i "%pilihan%"=="4" goto tier4
if /i "%pilihan%"=="5" goto tier5
if /i "%pilihan%"=="6" goto tier6
if /i "%pilihan%"=="7" goto tier7
if /i "%pilihan%"=="8" goto tier8
if /i "%pilihan%"=="A" goto run_all
if /i "%pilihan%"=="X" exit /b
goto menu

:: ==========================================
:: TIER 1
:: ==========================================
:tier1
echo.
echo --- Menjalankan TIER 1: Core System ^& Pengguna (5 Tabel) ---
for %%t in (roles privileges privileges_has_roles bank_accounts users) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 1 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: TIER 2
:: ==========================================
:tier2
echo.
echo --- Menjalankan TIER 2: Master Wilayah ^& Lokasi (5 Tabel) ---
for %%t in (regions subregions countries states cities) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 2 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: TIER 3
:: ==========================================
:tier3
echo.
echo --- Menjalankan TIER 3 Bagian 1: Karyawan (3 Tabel) ---
for %%t in (employees employee_kinerjas employee_warnings) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo.
echo --- Menjalankan TIER 3 Bagian 2: Agen ^& Komisi (3 Tabel) ---
for %%t in (agents commissions commission_details) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 3 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: TIER 4
:: ==========================================
:tier4
echo.
echo --- Menjalankan TIER 4 Bagian 1: Master Kampus ^& Fasilitas (5 Tabel) ---
for %%t in (univ_categories univ_facilities univ_facilities_details universities univ_has_categories) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo.
echo --- Menjalankan TIER 4 Bagian 2: Fasilitas, Prodi ^& Beasiswa (5 Tabel) ---
for %%t in (univ_has_facilites univ_programs univ_fee_structures univ_entry_requirements univ_scholarships) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo.
echo --- Menjalankan TIER 4 Bagian 3: Beasiswa Prodi, Akomodasi ^& Sekolah (5 Tabel) ---
for %%t in (scholarship univ_accomodations univ_accomodation_details univ_accomodation_photos sekolah) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 4 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: TIER 5
:: ==========================================
:tier5
echo.
echo --- Menjalankan TIER 5 Bagian 1: Student, Edu ^& Ortu (5 Tabel) ---
for %%t in (students student_education_backgrounds companions companion_relations companion_parent_company_backgrounds) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo.
echo --- Menjalankan TIER 5 Bagian 2: History Ortu, Tes ^& Favorit (4 Tabel) ---
for %%t in (companion_travel_historys tests student_tests student_favorites) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 5 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: TIER 6
:: ==========================================
:tier6
echo.
echo --- Menjalankan TIER 6 Bagian 1: Dokumen / Files (4 Tabel) ---
for %%t in (student_file_types file_type_tutorial student_files student_file_type_univ_program) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo.
echo --- Menjalankan TIER 6 Bagian 2: Tagihan ^& Pembayaran (5 Tabel) ---
for %%t in (student_payment_types payments payment_details payment_receipts students_has_payments) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 6 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: TIER 7
:: ==========================================
:tier7
echo.
echo --- Menjalankan TIER 7 Bagian 1: Pengajuan ^& Program (4 Tabel) ---
for %%t in (student_enrollment_documents enrollments enrollment_programs student_enrollment_document_programs) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo.
echo --- Menjalankan TIER 7 Bagian 2: Ujian, Beasiswa ^& Timeline (4 Tabel) ---
for %%t in (enrollment_examinations enrollment_scholarships enrollment_timelines enrollment_timeline_media) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 7 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: TIER 8
:: ==========================================
:tier8
echo.
echo --- Menjalankan TIER 8: Keberangkatan ^& Chat (4 Tabel) ---
for %%t in (departure chats chat_users chat_messages) do (
    echo Membuat migration: create_%%t_table...
    php artisan make:migration create_%%t_table --create=%%t
    timeout /t 1 /nobreak >nul
)
echo [SELESAI] Tier 8 berhasil dibuat!
echo.
if "%run_all_mode%"=="1" goto :eof
pause
goto menu

:: ==========================================
:: RUN ALL (BERTAHAP PER TIER DENGAN JEDA)
:: ==========================================
:run_all
set run_all_mode=1
call :tier1
call :tier2
call :tier3
call :tier4
call :tier5
call :tier6
call :tier7
call :tier8
set run_all_mode=0
echo.
echo ===================================================================
echo   SELURUH 61 MIGRATION BERHASIL DIBUAT DENGAN TIMESTAMP TERURUT!
echo ===================================================================
pause
goto menu
