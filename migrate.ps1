<#
.SYNOPSIS
    Laravel Migration Script fuer Hostinger (Multi-Account)
.DESCRIPTION
    Fuehrt Migrationen auf dem Server aus und zeigt Fehler an.
.EXAMPLE
    .\migrate.ps1
#>

param(
    [int]$Account = 0
)

# ==============================================================================
#  KONFIGURATION
# ==============================================================================

$PasswordFile = Join-Path $PSScriptRoot "deploy-passwords.env"
if (-not (Test-Path $PasswordFile)) {
    Write-Host "[FEHLER] Passwort-Datei nicht gefunden: $PasswordFile" -ForegroundColor Red
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

$Passwords = @{}
Get-Content $PasswordFile | ForEach-Object {
    if ($_ -match '^\s*set\s+PW_(\S+?)=(.+)$') {
        $Passwords[$Matches[1]] = $Matches[2].Trim()
    }
}

$Accounts = @{
    1 = @{
        Name        = "Resch GmbH"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u192633638"
        SFTP_PORT   = 65002
        REMOTE_PATH = "/home/u192633638/domains/reschc.space/public_html"
        WEBSITE_URL = "https://reschc.space"
        Color       = "Green"
    }
    2 = @{
        Name        = "Resch KG"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        REMOTE_PATH = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        WEBSITE_URL = "https://christianresch.esy.es/martin"
        Color       = "Yellow"
    }
    3 = @{
        Name        = "Sandbox (Test)"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        REMOTE_PATH = "/home/u854179217/domains/christianresch.esy.es/public_html/sandbox"
        WEBSITE_URL = "https://christianresch.esy.es/sandbox"
        Color       = "Cyan"
    }
}

$WINSCP_PATH = "C:\Program Files (x86)\WinSCP\WinSCP.com"

# ==============================================================================
#  FUNKTIONEN
# ==============================================================================

function Run-WinSCPCommand {
    param(
        [hashtable]$Config,
        [string]$Command,
        [string]$Label
    )

    $pw = $Passwords[$Config.SFTP_USER]
    $script = "option batch abort`noption confirm off`n"
    $script += "open sftp://$($Config.SFTP_USER):$pw@$($Config.SFTP_HOST):$($Config.SFTP_PORT) -hostkey=*`n"
    $script += "call cd $($Config.REMOTE_PATH) && $Command`n"
    $script += "close`nexit`n"

    $scriptPath = Join-Path $env:TEMP "migrate_$Label.txt"
    $logPath = Join-Path $env:TEMP "winscp_migrate_$Label.log"
    $script | Out-File -FilePath $scriptPath -Encoding ASCII

    $process = Start-Process -FilePath $WINSCP_PATH -ArgumentList "/script=`"$scriptPath`" /log=`"$logPath`"" -NoNewWindow -Wait -PassThru
    Remove-Item $scriptPath -Force -ErrorAction SilentlyContinue

    return $process.ExitCode -eq 0
}

# ==============================================================================
#  START
# ==============================================================================

Clear-Host
Write-Host ""
Write-Host ("=" * 60) -ForegroundColor Cyan
Write-Host "  LARAVEL MIGRATION - HOSTINGER" -ForegroundColor Cyan
Write-Host ("=" * 60) -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path $WINSCP_PATH)) {
    Write-Host "[FEHLER] WinSCP nicht gefunden" -ForegroundColor Red
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

# Konto-Auswahl
if ($Account -eq 0) {
    Write-Host "  [1] Resch GmbH    (reschc.space)" -ForegroundColor Green
    Write-Host "  [2] Resch KG      (christianresch.esy.es/martin)" -ForegroundColor Yellow
    Write-Host "  [3] Sandbox       (christianresch.esy.es/sandbox)" -ForegroundColor Cyan
    Write-Host "  [0] Abbrechen" -ForegroundColor Gray
    Write-Host ""

    do {
        $Account = Read-Host "  Auswahl (0-3)"
    } while ($Account -notmatch '^[0123]$')
    $Account = [int]$Account
}

if ($Account -eq 0) { Write-Host "Abgebrochen."; exit 0 }

$Config = $Accounts[$Account]
$color = $Config.Color

Write-Host ""
Write-Host ("=" * 60) -ForegroundColor $color
Write-Host "  MIGRATION: $($Config.Name)" -ForegroundColor $color
Write-Host ("=" * 60) -ForegroundColor $color
Write-Host ""

# ==============================================================================
#  SCHRITT 1: Status pruefen
# ==============================================================================

Write-Host "[1/3] Migration-Status pruefen..." -ForegroundColor Yellow
Write-Host ""

Run-WinSCPCommand -Config $Config -Label "status" -Command "echo '=== Migration Status ===' && php artisan migrate:status 2>&1"

Write-Host ""

# ==============================================================================
#  SCHRITT 2: Migration ausfuehren
# ==============================================================================

$confirm = Read-Host "  Migration jetzt ausfuehren? (j/n)"
if ($confirm -ne "j" -and $confirm -ne "J") {
    Write-Host "Abgebrochen."
    Read-Host "Druecke Enter zum Beenden"
    exit 0
}

Write-Host ""
Write-Host "[2/3] Fuehre Migration aus..." -ForegroundColor Yellow
Write-Host ""

$cmd = "echo '=== Migrationen ===' && php artisan migrate --force 2>&1; echo '' && echo '=== Route Cache ===' && php artisan route:clear && php artisan route:cache 2>&1; echo '' && echo '=== Optimize ===' && php artisan optimize:clear && php artisan optimize 2>&1; echo '' && echo '=== FERTIG ==='"

$success = Run-WinSCPCommand -Config $Config -Label "run" -Command $cmd

if (-not $success) {
    Write-Host ""
    Write-Host "[WARNUNG] Migration evtl. fehlgeschlagen!" -ForegroundColor Red
}

# ==============================================================================
#  SCHRITT 3: Ergebnis pruefen
# ==============================================================================

Write-Host ""
Write-Host "[3/3] Pruefe Ergebnis..." -ForegroundColor Yellow
Write-Host ""

Run-WinSCPCommand -Config $Config -Label "verify" -Command "echo '=== Status ===' && php artisan migrate:status 2>&1 && echo '' && php artisan --version 2>&1"

# ==============================================================================
#  SSH-INFO
# ==============================================================================

Write-Host ""
Write-Host "  -- SSH-Verbindung --" -ForegroundColor Cyan
Write-Host "  ssh -p $($Config.SFTP_PORT) $($Config.SFTP_USER)@$($Config.SFTP_HOST)" -ForegroundColor Yellow
Write-Host "  cd $($Config.REMOTE_PATH)" -ForegroundColor Gray
Write-Host ""

$sshCmd = "ssh -p $($Config.SFTP_PORT) $($Config.SFTP_USER)@$($Config.SFTP_HOST)"
$sshCmd | Set-Clipboard
Write-Host "[INFO] SSH-Befehl in Zwischenablage kopiert" -ForegroundColor Cyan

Write-Host ""
Write-Host ("=" * 60) -ForegroundColor $color
Write-Host "  MIGRATION ABGESCHLOSSEN" -ForegroundColor $color
Write-Host ("=" * 60) -ForegroundColor $color
Write-Host ""
Read-Host "Druecke Enter zum Beenden"
