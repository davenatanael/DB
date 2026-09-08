@echo off
title Menjalankan Konsolidasi Migrasi db_ybaik_new
echo ========================================================
echo   MENJALANKAN KONSOLIDASI SELURUH MIGRASI (5 MODUL)
echo ========================================================
echo.
cd /d "%~dp0"
call php run_all.php
pause
