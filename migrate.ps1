<#
.SYNOPSIS
    Laravel Migration Script (IONOS + Hostinger, Multi-Account)
.DESCRIPTION
    Fuehrt Migrationen auf dem Server aus und zeigt Fehler an.
.EXAMPLE
    .\migrate.ps1
    .\migrate.ps1 -Account 1
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

# SHELL_PATH = Pfad fuer SSH-Befehle
# PHP        = PHP-CLI auf dem Server
$Accounts = @{
    1 = @{
        Name        = "Resch GmbH (IONOS)"
        SFTP_HOST   = "access-5021443468.webspace-host.com"
        SFTP_USER   = "su935318"
        SFTP_PORT   = 22
        SHELL_PATH  = "~/uschiweb"
        PHP         = "php8.4"
        WEBSITE_URL = "https://uschiweb.it"
        Color       = "Green"
    }
    2 = @{
        Name        = "Resch KG"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        SHELL_PATH  = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        PHP         = "php"
        WEBSITE_URL = "https://christianresch.esy.es/martin"
        Color       = "Yellow"
    }
    3 = @{
        Name        = "Sandbox (Test)"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        SHELL_PATH  = "/home/u854179217/domains/christianresch.esy.es/public_html/sandbox"
        PHP         = "php"
        WEBSITE_URL = "https://christianresch.esy.es/sandbox"
        Color       = "Cyan"
    }
}

$WINSCP_PATH = "C:\Program Files (x86)\WinSCP\WinSCP.com"

# ==============================================================================
#  FUNKTIONEN
# ==============================================================================

# Fuehrt einen Befehl im Projektordner aus. Im Befehl steht `$P fuer das PHP-Binary.
# Laeuft in einer Subshell ( ... ), damit WinSCP danach nicht ins Shell-Verzeichnis
# wechselt (bei IONOS ist der SFTP-Pfad ein anderer als der SSH-Pfad).
function Run-WinSCPCommand {
    param(
        [hashtable]$Config,
        [string]$Command,
        [string]$Label
    )

    $pw = $Passwords[$Config.SFTP_USER]
    $script = "option batch abort`noption confirm off`n"
    $script += "open sftp://$($Config.SFTP_USER):$pw@$($Config.SFTP_HOST):$($Config.SFTP_PORT) -hostkey=*`n"
    $script += "call ( P=$($Config.PHP); cd $($Config.SHELL_PATH) && $Command )`n"
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
Write-Host "  LARAVEL MIGRATION - IONOS / HOSTINGER" -ForegroundColor Cyan
Write-Host ("=" * 60) -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path $WINSCP_PATH)) {
    Write-Host "[FEHLER] WinSCP nicht gefunden" -ForegroundColor Red
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

# Konto-Auswahl
if ($Account -eq 0) {
    Write-Host "  [1] Resch GmbH    (uschiweb.it - IONOS)" -ForegroundColor Green
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

if (-not $Passwords.ContainsKey($Config.SFTP_USER)) {
    Write-Host "[FEHLER] Kein Passwort fuer $($Config.SFTP_USER) in deploy-passwords.env (set PW_$($Config.SFTP_USER)=...)" -ForegroundColor Red
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

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

Run-WinSCPCommand -Config $Config -Label "status" -Command "echo '=== Migration Status ===' && `$P artisan migrate:status 2>&1" | Out-Null

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

$cmd = "echo '=== Migrationen ===' && `$P artisan migrate --force 2>&1; echo '' && echo '=== Route Cache ===' && `$P artisan route:clear; `$P artisan route:cache 2>&1; echo '' && echo '=== Optimize ===' && `$P artisan optimize:clear && `$P artisan optimize 2>&1; echo '' && echo '=== FERTIG ==='"

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

Run-WinSCPCommand -Config $Config -Label "verify" -Command "echo '=== Status ===' && `$P artisan migrate:status 2>&1 && echo '' && `$P artisan --version 2>&1" | Out-Null

# ==============================================================================
#  SSH-INFO
# ==============================================================================

Write-Host ""
Write-Host "  -- SSH-Verbindung --" -ForegroundColor Cyan
Write-Host "  ssh -p $($Config.SFTP_PORT) $($Config.SFTP_USER)@$($Config.SFTP_HOST)" -ForegroundColor Yellow
Write-Host "  cd $($Config.SHELL_PATH)" -ForegroundColor Gray
Write-Host "  PHP: $($Config.PHP)" -ForegroundColor Gray
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