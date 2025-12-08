@echo off
REM ═══════════════════════════════════════════════════════════════════════════
REM  LARAVEL DEPLOY SCRIPT - Hostinger
REM  
REM  Voraussetzung: WinSCP installiert (https://winscp.net)
REM  
REM  Verwendung: deploy.bat
REM ═══════════════════════════════════════════════════════════════════════════

setlocal EnableDelayedExpansion

REM ─────────────────────────────────────────────────────────────────────────────
REM  KONFIGURATION - HIER ANPASSEN!
REM ─────────────────────────────────────────────────────────────────────────────

REM SFTP-Zugangsdaten (aus Hostinger hPanel)
set SFTP_HOST=deine-domain.de
set SFTP_USER=u123456789
set SFTP_PORT=22

REM Pfade
set LOCAL_PATH=C:\Users\Christian\Documents\entwicklung\laravel-tutorial
set REMOTE_PATH=/home/%SFTP_USER%/domains/%SFTP_HOST%/public_html

REM WinSCP Pfad (Standard-Installation)
set WINSCP_PATH="C:\Program Files (x86)\WinSCP\WinSCP.com"

REM ─────────────────────────────────────────────────────────────────────────────
REM  SCRIPT START
REM ─────────────────────────────────────────────────────────────────────────────

echo.
echo ╔══════════════════════════════════════════════════════════════════════════╗
echo ║                    LARAVEL DEPLOYMENT - HOSTINGER                        ║
echo ╚══════════════════════════════════════════════════════════════════════════╝
echo.
echo  Server:  %SFTP_HOST%
echo  User:    %SFTP_USER%
echo  Local:   %LOCAL_PATH%
echo  Remote:  %REMOTE_PATH%
echo.

REM Prüfen ob WinSCP existiert
if not exist %WINSCP_PATH% (
    echo [FEHLER] WinSCP nicht gefunden: %WINSCP_PATH%
    echo.
    echo Bitte WinSCP installieren: https://winscp.net/eng/download.php
    echo Oder den Pfad in diesem Script anpassen.
    pause
    exit /b 1
)

REM Temporäres WinSCP-Script erstellen
set WINSCP_SCRIPT=%TEMP%\deploy_winscp.txt

echo [1/4] Erstelle Upload-Script...
(
    echo option batch abort
    echo option confirm off
    echo open sftp://%SFTP_USER%@%SFTP_HOST%:%SFTP_PORT% -hostkey=*
    echo.
    echo # Wartungsmodus aktivieren
    echo call php %REMOTE_PATH%/artisan down --quiet 2^>^&1 ^|^| true
    echo.
    echo # Ordner synchronisieren
    echo echo Synchronisiere app/...
    echo synchronize remote -delete "%LOCAL_PATH%\app" "%REMOTE_PATH%/app"
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

echo [2/4] Lade Dateien hoch (SFTP)...
echo.

%WINSCP_PATH% /script="%WINSCP_SCRIPT%" /log="%TEMP%\winscp_deploy.log"

if %ERRORLEVEL% neq 0 (
    echo.
    echo [FEHLER] Upload fehlgeschlagen! Siehe Log: %TEMP%\winscp_deploy.log
    pause
    exit /b 1
)

echo.
echo [3/4] Dateien hochgeladen!
echo.

REM SSH-Befehle erstellen
set SSH_SCRIPT=%TEMP%\deploy_ssh.txt

(
    echo cd %REMOTE_PATH%
    echo echo "=== Composer Install ==="
    echo composer install --no-dev --optimize-autoloader --no-interaction
    echo echo "=== Migrationen ==="
    echo php artisan migrate --force
    echo echo "=== Cache leeren ==="
    echo php artisan cache:clear
    echo php artisan config:cache
    echo php artisan route:cache
    echo php artisan view:cache
    echo echo "=== Berechtigungen ==="
    echo chmod -R 775 storage bootstrap/cache
    echo echo "=== Wartungsmodus aus ==="
    echo php artisan up
    echo echo "=== FERTIG ==="
) > "%SSH_SCRIPT%"

echo [4/4] Fuehre Server-Befehle aus...
echo.
echo ══════════════════════════════════════════════════════════════════════════
echo  WICHTIG: Fuehre diese Befehle per SSH aus:
echo ══════════════════════════════════════════════════════════════════════════
echo.
type "%SSH_SCRIPT%"
echo.
echo ══════════════════════════════════════════════════════════════════════════
echo.
echo  SSH-Verbindung:
echo  ssh %SFTP_USER%@%SFTP_HOST% -p %SFTP_PORT%
echo.
echo ══════════════════════════════════════════════════════════════════════════

REM Aufräumen
del "%WINSCP_SCRIPT%" 2>nul

echo.
echo Deployment abgeschlossen!
echo.
pause
