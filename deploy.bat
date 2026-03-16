@echo off
REM ═══════════════════════════════════════════════════════════════════════════
REM  LARAVEL DEPLOY SCRIPT - Hostinger (Multi-Account)
REM  
REM  Voraussetzung: WinSCP installiert (https://winscp.net)
REM                 deploy-passwords.env mit Passwoertern (siehe Vorlage)
REM  
REM  Verwendung: deploy.bat
REM ═══════════════════════════════════════════════════════════════════════════

setlocal EnableDelayedExpansion

REM ─────────────────────────────────────────────────────────────────────────────
REM  KONFIGURATION
REM ─────────────────────────────────────────────────────────────────────────────

REM Dynamischer lokaler Pfad - basierend auf Skript-Standort
set LOCAL_PATH=%~dp0
REM Trailing Backslash entfernen
if "%LOCAL_PATH:~-1%"=="\" set LOCAL_PATH=%LOCAL_PATH:~0,-1%

REM WinSCP Pfad (Standard-Installation)
set WINSCP_PATH="C:\Program Files (x86)\WinSCP\WinSCP.com"

REM ─────────────────────────────────────────────────────────────────────────────
REM  PASSWOERTER AUS EXTERNER DATEI LADEN
REM ─────────────────────────────────────────────────────────────────────────────
if not exist "%LOCAL_PATH%\deploy-passwords.env" (
    echo [FEHLER] Passwort-Datei nicht gefunden: %LOCAL_PATH%\deploy-passwords.env
    echo.
    echo Erstelle die Datei mit folgendem Inhalt:
    echo   set PW_u192633638=DEIN_PASSWORT
    echo   set PW_u854179217=DEIN_PASSWORT
    echo.
    pause
    exit /b 1
)
call "%LOCAL_PATH%\deploy-passwords.env"

REM ─────────────────────────────────────────────────────────────────────────────
REM  SCRIPT START
REM ─────────────────────────────────────────────────────────────────────────────

cls
echo.
echo ╔══════════════════════════════════════════════════════════════════════════╗
echo ║                    LARAVEL DEPLOYMENT - HOSTINGER                        ║
echo ╚══════════════════════════════════════════════════════════════════════════╝
echo.
echo  Lokaler Pfad: %LOCAL_PATH%
echo.

REM Prüfen ob WinSCP existiert
if not exist %WINSCP_PATH% (
    echo [FEHLER] WinSCP nicht gefunden: %WINSCP_PATH%
    echo.
    echo Bitte WinSCP installieren: https://winscp.net/eng/download.php
    pause
    exit /b 1
)

REM ─────────────────────────────────────────────────────────────────────────────
REM  KONTO-AUSWAHL
REM ─────────────────────────────────────────────────────────────────────────────

echo  Waehle das Ziel-Konto:
echo.
echo  === PRODUKTION ===
echo    [1] Resch GmbH    (reschc.space)
echo    [2] Resch KG      (christianresch.esy.es/martin)
echo.
echo  === TEST ===
echo    [3] Sandbox       (christianresch.esy.es/sandbox)
echo.
echo    [0] Abbrechen
echo.

set /p CHOICE="  Auswahl (0-3): "

if "%CHOICE%"=="0" (
    echo Abgebrochen.
    exit /b 0
)

if "%CHOICE%"=="1" (
    set SFTP_HOST=212.1.209.26
    set SFTP_USER=u192633638
    set SFTP_PORT=65002
    set SFTP_PW=!PW_u192633638!
    set REMOTE_PATH=/home/u192633638/domains/reschc.space/public_html
    set WEBSITE_URL=https://reschc.space
    set ACCOUNT_NAME=Resch GmbH
    goto :START_DEPLOY
)

if "%CHOICE%"=="2" (
    set SFTP_HOST=212.1.209.26
    set SFTP_USER=u854179217
    set SFTP_PORT=65002
    set SFTP_PW=!PW_u854179217!
    set REMOTE_PATH=/home/u854179217/domains/christianresch.esy.es/public_html/martin
    set WEBSITE_URL=https://christianresch.esy.es/martin
    set ACCOUNT_NAME=Resch KG
    goto :START_DEPLOY
)

if "%CHOICE%"=="3" (
    set SFTP_HOST=212.1.209.26
    set SFTP_USER=u854179217
    set SFTP_PORT=65002
    set SFTP_PW=!PW_u854179217!
    set REMOTE_PATH=/home/u854179217/domains/christianresch.esy.es/public_html/sandbox
    set WEBSITE_URL=https://christianresch.esy.es/sandbox
    set ACCOUNT_NAME=Sandbox
    goto :START_DEPLOY
)

echo Ungueltige Auswahl!
pause
exit /b 1

:START_DEPLOY
echo.
echo ══════════════════════════════════════════════════════════════════════════
echo  DEPLOYMENT: %ACCOUNT_NAME%
echo ══════════════════════════════════════════════════════════════════════════
echo.
echo  Server:  %SFTP_HOST%
echo  User:    %SFTP_USER%
echo  Remote:  %REMOTE_PATH%
echo  Website: %WEBSITE_URL%
echo.

REM Temporäres WinSCP-Script erstellen
set WINSCP_SCRIPT=%TEMP%\deploy_winscp.txt

echo [1/3] Erstelle Upload-Script...
(
    echo option batch abort
    echo option confirm off
    echo open sftp://%SFTP_USER%:!SFTP_PW!@%SFTP_HOST%:%SFTP_PORT% -hostkey=*
    echo.
    echo # Wartungsmodus aktivieren
    echo call php %REMOTE_PATH%/artisan down --quiet 2^>^&1 ^|^| true
    echo.
    echo # Ordner synchronisieren
    echo echo Synchronisiere app/...
    echo synchronize remote -delete "%LOCAL_PATH%\app" "%REMOTE_PATH%/app"
    echo.
    echo echo Synchronisiere bootstrap/...
    echo synchronize remote "%LOCAL_PATH%\bootstrap" "%REMOTE_PATH%/bootstrap"
    echo.
    echo echo Synchronisiere config/...
    echo synchronize remote -delete "%LOCAL_PATH%\config" "%REMOTE_PATH%/config"
    echo.
    echo echo Synchronisiere database/migrations/...
    echo synchronize remote "%LOCAL_PATH%\database\migrations" "%REMOTE_PATH%/database/migrations"
    echo.
    echo echo Synchronisiere database/seeders/...
    echo synchronize remote "%LOCAL_PATH%\database\seeders" "%REMOTE_PATH%/database/seeders"
    echo.
    echo echo Synchronisiere resources/...
    echo synchronize remote -delete "%LOCAL_PATH%\resources" "%REMOTE_PATH%/resources"
    echo.
    echo echo Synchronisiere routes/...
    echo synchronize remote -delete "%LOCAL_PATH%\routes" "%REMOTE_PATH%/routes"
    echo.
    echo echo Synchronisiere public/css/...
    echo synchronize remote -delete "%LOCAL_PATH%\public\css" "%REMOTE_PATH%/public/css"
    echo.
    echo echo Synchronisiere public/js/...
    echo synchronize remote -delete "%LOCAL_PATH%\public\js" "%REMOTE_PATH%/public/js"
    echo.
    echo echo Synchronisiere public/build/...
    echo synchronize remote -delete "%LOCAL_PATH%\public\build" "%REMOTE_PATH%/public/build"
    echo.
    echo echo Lade composer.json...
    echo put "%LOCAL_PATH%\composer.json" "%REMOTE_PATH%/"
    echo.
    echo echo Lade composer.lock...
    echo put "%LOCAL_PATH%\composer.lock" "%REMOTE_PATH%/"
    echo.
    echo # Storage/import Ordner erstellen und sync
    echo call mkdir -p %REMOTE_PATH%/storage/import 2^>^&1 ^|^| true
    echo synchronize remote "%LOCAL_PATH%\storage\import" "%REMOTE_PATH%/storage/import"
    echo.
    echo close
    echo exit
) > "%WINSCP_SCRIPT%"

echo [2/3] Lade Dateien hoch (SFTP)...
echo.

%WINSCP_PATH% /script="%WINSCP_SCRIPT%" /log="%TEMP%\winscp_deploy.log"

if %ERRORLEVEL% neq 0 (
    echo.
    echo [FEHLER] Upload fehlgeschlagen! Siehe Log: %TEMP%\winscp_deploy.log
    pause
    exit /b 1
)

echo.
echo  Dateien hochgeladen!
echo.

REM ─────────────────────────────────────────────────────────────────────────────
REM  MIGRATION (automatisch, ohne Nachfrage)
REM ─────────────────────────────────────────────────────────────────────────────

echo [3/3] Fuehre Migration und Server-Befehle aus...
echo.

set WINSCP_MIGRATION=%TEMP%\deploy_migration.txt

(
    echo option batch abort
    echo option confirm off
    echo open sftp://%SFTP_USER%:!SFTP_PW!@%SFTP_HOST%:%SFTP_PORT% -hostkey=*
    echo.
    echo call cd %REMOTE_PATH% ^&^& echo '=== Composer Install ===' ^&^& composer install --no-dev --optimize-autoloader --no-interaction ^&^& echo '' ^&^& echo '=== Migrationen ===' ^&^& php artisan migrate --force ^&^& echo '' ^&^& echo '=== Optimize ===' ^&^& php artisan optimize:clear ^&^& php artisan optimize ^&^& echo '' ^&^& echo '=== Berechtigungen ===' ^&^& chmod -R 775 storage bootstrap/cache ^&^& echo '' ^&^& echo '=== Wartungsmodus aus ===' ^&^& php artisan up ^&^& echo '' ^&^& echo '=== FERTIG ==='
    echo.
    echo close
    echo exit
) > "%WINSCP_MIGRATION%"

%WINSCP_PATH% /script="%WINSCP_MIGRATION%" /log="%TEMP%\winscp_migration.log"

if %ERRORLEVEL% neq 0 (
    echo.
    echo [WARNUNG] Migration evtl. fehlgeschlagen! Siehe Log: %TEMP%\winscp_migration.log
)

REM Aufräumen
del "%WINSCP_SCRIPT%" 2>nul
del "%WINSCP_MIGRATION%" 2>nul

echo.
echo ══════════════════════════════════════════════════════════════════════════
echo  Deployment abgeschlossen!
echo ══════════════════════════════════════════════════════════════════════════
echo.
echo  Website testen: %WEBSITE_URL%
echo.
pause
