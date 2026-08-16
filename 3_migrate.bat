@echo off
REM ═══════════════════════════════════════════════════════════════════════════
REM  LARAVEL MIGRATION SCRIPT - Hostinger (Multi-Account)
REM  
REM  Fuehrt Migrationen auf dem Server aus und zeigt Fehler an.
REM  
REM  Verwendung: migrate.bat
REM ═══════════════════════════════════════════════════════════════════════════

setlocal EnableDelayedExpansion

set LOCAL_PATH=%~dp0
if "%LOCAL_PATH:~-1%"=="\" set LOCAL_PATH=%LOCAL_PATH:~0,-1%

set WINSCP_PATH="C:\Program Files (x86)\WinSCP\WinSCP.com"

REM ─────────────────────────────────────────────────────────────────────────────
REM  PASSWOERTER LADEN
REM ─────────────────────────────────────────────────────────────────────────────
if not exist "%LOCAL_PATH%\deploy-passwords.env" (
    echo [FEHLER] Passwort-Datei nicht gefunden: %LOCAL_PATH%\deploy-passwords.env
    pause
    exit /b 1
)
call "%LOCAL_PATH%\deploy-passwords.env"

REM ─────────────────────────────────────────────────────────────────────────────
REM  START
REM ─────────────────────────────────────────────────────────────────────────────
cls
echo.
echo ======================================================================
echo                    LARAVEL MIGRATION - HOSTINGER
echo ======================================================================
echo.

if not exist %WINSCP_PATH% (
    echo [FEHLER] WinSCP nicht gefunden: %WINSCP_PATH%
    pause
    exit /b 1
)

REM ─────────────────────────────────────────────────────────────────────────────
REM  KONTO-AUSWAHL
REM ─────────────────────────────────────────────────────────────────────────────
echo  Waehle das Ziel-Konto:
echo.
echo    [1] Resch GmbH    (reschc.space)
echo    [2] Resch KG      (christianresch.esy.es/martin)
echo    [3] Sandbox       (christianresch.esy.es/sandbox)
echo.
echo    [0] Abbrechen
echo.

set /p CHOICE="  Auswahl (0-3): "

if "%CHOICE%"=="0" ( echo Abgebrochen. & exit /b 0 )

if "%CHOICE%"=="1" (
    set SFTP_HOST=212.1.209.26
    set SFTP_USER=u192633638
    set SFTP_PORT=65002
    set SFTP_PW=!PW_u192633638!
    set REMOTE_PATH=/home/u192633638/domains/reschc.space/public_html
    set ACCOUNT_NAME=Resch GmbH
    goto :RUN
)
if "%CHOICE%"=="2" (
    set SFTP_HOST=212.1.209.26
    set SFTP_USER=u854179217
    set SFTP_PORT=65002
    set SFTP_PW=!PW_u854179217!
    set REMOTE_PATH=/home/u854179217/domains/christianresch.esy.es/public_html/martin
    set ACCOUNT_NAME=Resch KG
    goto :RUN
)
if "%CHOICE%"=="3" (
    set SFTP_HOST=212.1.209.26
    set SFTP_USER=u854179217
    set SFTP_PORT=65002
    set SFTP_PW=!PW_u854179217!
    set REMOTE_PATH=/home/u854179217/domains/christianresch.esy.es/public_html/sandbox
    set ACCOUNT_NAME=Sandbox
    goto :RUN
)

echo Ungueltige Auswahl!
pause
exit /b 1

:RUN
echo.
echo ======================================================================
echo  MIGRATION: %ACCOUNT_NAME%
echo ======================================================================
echo.
echo  Server: %SFTP_HOST%
echo  User:   %SFTP_USER%
echo  Pfad:   %REMOTE_PATH%
echo.

REM ─────────────────────────────────────────────────────────────────────────────
REM  SCHRITT 1: Migration Status pruefen
REM ─────────────────────────────────────────────────────────────────────────────
echo [1/3] Pruefe Migration-Status...
echo.

set WINSCP_STATUS=%TEMP%\migrate_status.txt

(
    echo option batch abort
    echo option confirm off
    echo open sftp://%SFTP_USER%:!SFTP_PW!@%SFTP_HOST%:%SFTP_PORT% -hostkey=*
    echo.
    echo call cd %REMOTE_PATH% ^&^& echo '=== Migration Status ===' ^&^& php artisan migrate:status 2^>^&1 ^&^& echo '' ^&^& echo '=== Pending Migrations ===' ^&^& php artisan migrate:status --pending 2^>^&1 ^|^| echo 'Alle Migrationen aktuell'
    echo.
    echo close
    echo exit
) > "%WINSCP_STATUS%"

%WINSCP_PATH% /script="%WINSCP_STATUS%" /log="%TEMP%\winscp_migrate_status.log"
del "%WINSCP_STATUS%" 2>nul

echo.
echo ======================================================================
echo.

REM ─────────────────────────────────────────────────────────────────────────────
REM  SCHRITT 2: Migration ausfuehren
REM ─────────────────────────────────────────────────────────────────────────────
set /p CONFIRM="  Migration jetzt ausfuehren? (j/n): "
if /i not "%CONFIRM%"=="j" (
    echo Abgebrochen.
    pause
    exit /b 0
)

echo.
echo [2/3] Fuehre Migration aus...
echo.

set WINSCP_MIGRATE=%TEMP%\migrate_run.txt

(
    echo option batch abort
    echo option confirm off
    echo open sftp://%SFTP_USER%:!SFTP_PW!@%SFTP_HOST%:%SFTP_PORT% -hostkey=*
    echo.
    echo call cd %REMOTE_PATH% ^&^& echo '=== Migrationen ===' ^&^& php artisan migrate --force 2^>^&1; echo '' ^&^& echo '=== Route Cache ===' ^&^& php artisan route:clear 2^>^&1 ^&^& php artisan route:cache 2^>^&1; echo '' ^&^& echo '=== Optimize ===' ^&^& php artisan optimize:clear 2^>^&1 ^&^& php artisan optimize 2^>^&1; echo '' ^&^& echo '=== FERTIG ==='
    echo.
    echo close
    echo exit
) > "%WINSCP_MIGRATE%"

%WINSCP_PATH% /script="%WINSCP_MIGRATE%" /log="%TEMP%\winscp_migrate_run.log"

if %ERRORLEVEL% neq 0 (
    echo.
    echo [FEHLER] Migration fehlgeschlagen!
    echo.
    echo Log anzeigen:
    type "%TEMP%\winscp_migrate_run.log" | findstr /i "error\|fail\|exception"
    echo.
)

del "%WINSCP_MIGRATE%" 2>nul

REM ─────────────────────────────────────────────────────────────────────────────
REM  SCHRITT 3: Status nach Migration
REM ─────────────────────────────────────────────────────────────────────────────
echo.
echo [3/3] Pruefe Status nach Migration...
echo.

set WINSCP_VERIFY=%TEMP%\migrate_verify.txt

(
    echo option batch abort
    echo option confirm off
    echo open sftp://%SFTP_USER%:!SFTP_PW!@%SFTP_HOST%:%SFTP_PORT% -hostkey=*
    echo.
    echo call cd %REMOTE_PATH% ^&^& echo '=== Migration Status ===' ^&^& php artisan migrate:status 2^>^&1 ^&^& echo '' ^&^& echo '=== Server Status ===' ^&^& php artisan --version 2^>^&1 ^&^& echo '' ^&^& curl -s -o /dev/null -w 'HTTP Status: %%{http_code}' https://%SFTP_HOST% 2^>^&1 ^|^| true
    echo.
    echo close
    echo exit
) > "%WINSCP_VERIFY%"

%WINSCP_PATH% /script="%WINSCP_VERIFY%" /log="%TEMP%\winscp_migrate_verify.log"
del "%WINSCP_VERIFY%" 2>nul

REM ─────────────────────────────────────────────────────────────────────────────
REM  SSH-INFO
REM ─────────────────────────────────────────────────────────────────────────────
echo.
echo  -- SSH-Verbindung --
echo  ssh -p %SFTP_PORT% %SFTP_USER%@%SFTP_HOST%
echo  cd %REMOTE_PATH%
echo.
echo ssh -p %SFTP_PORT% %SFTP_USER%@%SFTP_HOST% | clip
echo  [INFO] SSH-Befehl in Zwischenablage kopiert
echo.

echo ======================================================================
echo  MIGRATION ABGESCHLOSSEN
echo ======================================================================
echo.
pause
