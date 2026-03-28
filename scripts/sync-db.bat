@echo off
setlocal

:: ============================================================
:: Canlı DB'den Lokal DB'ye Sync (HTTP dump yöntemi)
:: ============================================================

set MYSQL="C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"
set DUMP_URL=https://gruptalepleri.com/db-export.php?t=grt2026dbx
set DUMP_FILE=%TEMP%\gruptalepleri_sync.sql

:: Lokal DB
set LOCAL_HOST=127.0.0.1
set LOCAL_PORT=3306
set LOCAL_DB=gruptalepleri
set LOCAL_USER=root
set LOCAL_PASS=root123

echo [%TIME%] Canlidan dump indiriliyor...
curl -s -k -o "%DUMP_FILE%" "%DUMP_URL%"

if %ERRORLEVEL% NEQ 0 (
    echo [%TIME%] HATA: Dump indirilemedi!
    pause
    exit /b 1
)

:: Dump dosyası geçerli mi kontrol et
for %%A in ("%DUMP_FILE%") do set DUMP_SIZE=%%~zA
if %DUMP_SIZE% LSS 10000 (
    echo [%TIME%] HATA: Dump dosyasi cok kucuk ^(%DUMP_SIZE% byte^). Sunucu hatasi:
    type "%DUMP_FILE%"
    pause
    exit /b 1
)

echo [%TIME%] Dump alindi ^(%DUMP_SIZE% byte^). Lokal DB yukleniyor...

%MYSQL% -h %LOCAL_HOST% -P %LOCAL_PORT% -u %LOCAL_USER% -p%LOCAL_PASS% ^
  -e "DROP DATABASE IF EXISTS %LOCAL_DB%; CREATE DATABASE %LOCAL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

%MYSQL% -h %LOCAL_HOST% -P %LOCAL_PORT% -u %LOCAL_USER% -p%LOCAL_PASS% ^
  %LOCAL_DB% < "%DUMP_FILE%"

if %ERRORLEVEL% NEQ 0 (
    echo [%TIME%] HATA: Lokal DB yuklenemedi!
    pause
    exit /b 1
)

del "%DUMP_FILE%"
echo [%TIME%] TAMAMLANDI! Lokal DB guncellendi.
pause
