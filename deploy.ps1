<#
.SYNOPSIS
    Laravel Deploy Script fuer Hostinger
.DESCRIPTION
    Laedt Dateien per SFTP hoch und fuehrt Server-Befehle aus
.PARAMETER DryRun
    Nur anzeigen was passieren wuerde
.PARAMETER SkipSSH
    Nur Upload, keine SSH-Befehle
.PARAMETER Force
    Ohne Bestaetigung ausfuehren
.EXAMPLE
    .\deploy.ps1
    .\deploy.ps1 -DryRun
    .\deploy.ps1 -Force
#>

param(
    [switch]$DryRun,
    [switch]$SkipSSH,
    [switch]$Force
)

# ==============================================================================
#  KONFIGURATION - HIER ANPASSEN!
# ==============================================================================

$Config = @{
    # SSH/SFTP-Zugangsdaten (aus Hostinger hPanel -> SSH-Zugang)
    SFTP_HOST     = "212.1.209.26"
    # SFTP_USER     = "u192633638" # resch gmbh
    SFTP_USER     = "u854179217" # resch kg

    SFTP_PORT     = 65002
    
    # Pfade
    LOCAL_PATH    = "C:\Users\Christian\Documents\entwicklung\laravel-tutorial"
    # REMOTE_PATH   = "/home/u192633638/domains/reschc.space/public_html" # resch gmbh
    REMOTE_PATH   = "/home/u854179217/domains/christianresch.esy.es/public_html/martin" # resch kg
    
    # WinSCP Pfad
    WINSCP_PATH   = "C:\Program Files (x86)\WinSCP\WinSCP.com"
}

# ==============================================================================
#  ORDNER DIE SYNCHRONISIERT WERDEN
# ==============================================================================

$SyncFolders = @(
    @{ Local = "app";                  Remote = "app";                  Delete = $true  }
    @{ Local = "config";               Remote = "config";               Delete = $true  }
    @{ Local = "database\migrations";  Remote = "database/migrations";  Delete = $false }
    @{ Local = "database\seeders";     Remote = "database/seeders";     Delete = $false }
    @{ Local = "resources";            Remote = "resources";            Delete = $true  }
    @{ Local = "routes";               Remote = "routes";               Delete = $true  }
    @{ Local = "public\css";           Remote = "public/css";           Delete = $true  }
    @{ Local = "public\js";            Remote = "public/js";            Delete = $true  }
    @{ Local = "public\build";         Remote = "public/build";         Delete = $true  }
    @{ Local = "public\images";        Remote = "public/images";        Delete = $false }
    @{ Local = "storage\import";       Remote = "storage/import";       Delete = $false }
)

$SyncFiles = @(
    "composer.json"
    "composer.lock"
)

# ==============================================================================
#  FUNKTIONEN
# ==============================================================================

function Show-Header {
    param([string]$Text)
    Write-Host ""
    Write-Host ("=" * 70) -ForegroundColor Cyan
    Write-Host "  $Text" -ForegroundColor Cyan
    Write-Host ("=" * 70) -ForegroundColor Cyan
    Write-Host ""
}

function Show-Step {
    param([int]$Num, [int]$Total, [string]$Text)
    Write-Host "[$Num/$Total] $Text" -ForegroundColor Yellow
}

function Show-Success {
    param([string]$Text)
    Write-Host "[OK] $Text" -ForegroundColor Green
}

function Show-Err {
    param([string]$Text)
    Write-Host "[FEHLER] $Text" -ForegroundColor Red
}

function Show-Warning {
    param([string]$Text)
    Write-Host "[WARNUNG] $Text" -ForegroundColor Yellow
}

# ==============================================================================
#  SCRIPT START
# ==============================================================================

Clear-Host
Show-Header "LARAVEL DEPLOYMENT - HOSTINGER"

Write-Host "  Server:  $($Config.SFTP_HOST)" -ForegroundColor White
Write-Host "  User:    $($Config.SFTP_USER)" -ForegroundColor White
Write-Host "  Local:   $($Config.LOCAL_PATH)" -ForegroundColor White
Write-Host "  Remote:  $($Config.REMOTE_PATH)" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Show-Warning "DRY-RUN MODUS - Keine Aenderungen werden durchgefuehrt!"
    Write-Host ""
}

# Pruefen ob WinSCP existiert
if (-not (Test-Path $Config.WINSCP_PATH)) {
    Show-Err "WinSCP nicht gefunden: $($Config.WINSCP_PATH)"
    Write-Host ""
    Write-Host "Bitte WinSCP installieren: https://winscp.net/eng/download.php"
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

# Pruefen ob lokaler Pfad existiert
if (-not (Test-Path $Config.LOCAL_PATH)) {
    Show-Err "Lokaler Pfad nicht gefunden: $($Config.LOCAL_PATH)"
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

# Bestaetigung
if (-not $Force -and -not $DryRun) {
    $confirm = Read-Host "Deployment starten? (j/n)"
    if ($confirm -ne "j" -and $confirm -ne "J" -and $confirm -ne "y" -and $confirm -ne "Y") {
        Write-Host "Abgebrochen."
        exit 0
    }
}

# ==============================================================================
#  WINSCP SCRIPT ERSTELLEN
# ==============================================================================

Show-Step 1 4 "Erstelle Upload-Script..."

$WinSCPScript = "option batch abort`n"
$WinSCPScript += "option confirm off`n"
# FTP oder SFTP je nach Port
if ($Config.SFTP_PORT -eq 21) {
    # FTP (unverschluesselt) - kein Wartungsmodus moeglich
    $WinSCPScript += "open ftp://$($Config.SFTP_USER)@$($Config.SFTP_HOST):$($Config.SFTP_PORT)`n"
} else {
    # SFTP (verschluesselt)
    $WinSCPScript += "open sftp://$($Config.SFTP_USER)@$($Config.SFTP_HOST):$($Config.SFTP_PORT) -hostkey=*`n"
    $WinSCPScript += "`n"
    $WinSCPScript += "# Wartungsmodus aktivieren`n"
    $WinSCPScript += "call php $($Config.REMOTE_PATH)/artisan down --quiet 2>&1 || true`n"
}
$WinSCPScript += "`n"

# Ordner hinzufuegen
foreach ($folder in $SyncFolders) {
    $localPath = Join-Path $Config.LOCAL_PATH $folder.Local
    $remotePath = "$($Config.REMOTE_PATH)/$($folder.Remote)"
    
    if (Test-Path $localPath) {
        $deleteFlag = ""
        if ($folder.Delete) { 
            $deleteFlag = "-delete" 
        }
        $WinSCPScript += "echo Synchronisiere $($folder.Local)/...`n"
        $WinSCPScript += "synchronize remote $deleteFlag `"$localPath`" `"$remotePath`"`n"
        $WinSCPScript += "`n"
    }
}

# Einzelne Dateien hinzufuegen
foreach ($file in $SyncFiles) {
    $localFile = Join-Path $Config.LOCAL_PATH $file
    if (Test-Path $localFile) {
        $WinSCPScript += "echo Lade $file...`n"
        $WinSCPScript += "put `"$localFile`" `"$($Config.REMOTE_PATH)/`"`n"
        $WinSCPScript += "`n"
    }
}

$WinSCPScript += "close`n"
$WinSCPScript += "exit`n"

$WinSCPScriptPath = Join-Path $env:TEMP "deploy_winscp.txt"
$WinSCPScript | Out-File -FilePath $WinSCPScriptPath -Encoding ASCII

Show-Success "Upload-Script erstellt"

# ==============================================================================
#  DATEIEN HOCHLADEN
# ==============================================================================

Show-Step 2 4 "Lade Dateien hoch (SFTP)..."
Write-Host ""

if ($DryRun) {
    Write-Host "  [DRY-RUN] Wuerde folgende Ordner synchronisieren:" -ForegroundColor Magenta
    foreach ($folder in $SyncFolders) {
        $localPath = Join-Path $Config.LOCAL_PATH $folder.Local
        if (Test-Path $localPath) {
            Write-Host "    -> $($folder.Local)/" -ForegroundColor White
        }
    }
    Write-Host ""
} else {
    $WinSCPLog = Join-Path $env:TEMP "winscp_deploy.log"
    
    $processArgs = "/script=`"$WinSCPScriptPath`" /log=`"$WinSCPLog`""
    $process = Start-Process -FilePath $Config.WINSCP_PATH -ArgumentList $processArgs -NoNewWindow -Wait -PassThru
    
    if ($process.ExitCode -ne 0) {
        Show-Err "Upload fehlgeschlagen! Siehe Log: $WinSCPLog"
        Read-Host "Druecke Enter zum Beenden"
        exit 1
    }
}

Show-Success "Dateien hochgeladen"

# ==============================================================================
#  SSH BEFEHLE AUSFUEHREN
# ==============================================================================

Show-Step 3 4 "Server-Befehle..."
Write-Host ""

$SSHCommands = @"
cd $($Config.REMOTE_PATH)
echo '=== Composer Install ==='
composer install --no-dev --optimize-autoloader --no-interaction
echo '=== Migrationen ==='
php artisan migrate --force
echo '=== Cache ==='
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo '=== Berechtigungen ==='
chmod -R 775 storage bootstrap/cache
echo '=== Wartungsmodus aus ==='
php artisan up
echo '=== FERTIG ==='
"@

# FTP hat kein SSH - Befehle muessen manuell ausgefuehrt werden
$isFTP = ($Config.SFTP_PORT -eq 21)

if ($isFTP) {
    Show-Warning "FTP-Modus: Server-Befehle muessen manuell per SSH ausgefuehrt werden!"
    Write-Host ""
    Write-Host "  Verbinde dich per SSH und fuehre aus:" -ForegroundColor White
    Write-Host ""
    Write-Host $SSHCommands -ForegroundColor Gray
    Write-Host ""
    Write-Host "  SSH-Verbindung:" -ForegroundColor Yellow
    Write-Host "  ssh u192633638@212.1.209.26 -p 65002" -ForegroundColor Cyan
    Write-Host ""
} elseif ($SkipSSH) {
    Show-Warning "SSH-Befehle uebersprungen"
    Write-Host ""
    Write-Host "  Fuehre diese Befehle manuell per SSH aus:" -ForegroundColor White
    Write-Host ""
    Write-Host $SSHCommands -ForegroundColor Gray
    Write-Host ""
} elseif ($DryRun) {
    Write-Host "  [DRY-RUN] Wuerde folgende SSH-Befehle ausfuehren:" -ForegroundColor Magenta
    Write-Host ""
    Write-Host $SSHCommands -ForegroundColor Gray
    Write-Host ""
} else {
    Write-Host "  Verbinde per SSH..." -ForegroundColor White
    Write-Host ""
    
    # SSH ausfuehren
    $SSHCommands | ssh -p $Config.SFTP_PORT "$($Config.SFTP_USER)@$($Config.SFTP_HOST)"
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Show-Warning "SSH-Befehle evtl. fehlgeschlagen. Pruefe die Website!"
        Write-Host ""
        Write-Host "  Manuell verbinden:" -ForegroundColor Yellow
        Write-Host "  ssh $($Config.SFTP_USER)@$($Config.SFTP_HOST) -p $($Config.SFTP_PORT)" -ForegroundColor Cyan
    }
}

Show-Success "Server-Befehle ausgefuehrt"

# ==============================================================================
#  ABSCHLUSS
# ==============================================================================

Show-Step 4 4 "Aufraeumen..."

Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue

Show-Header "DEPLOYMENT ABGESCHLOSSEN"

if (-not $DryRun) {
    Write-Host "  Website testen: https://$($Config.SFTP_HOST)" -ForegroundColor Green
    Write-Host ""
}

# Log anzeigen
$WinSCPLog = Join-Path $env:TEMP "winscp_deploy.log"
if (Test-Path $WinSCPLog) {
    Write-Host "  Upload-Log: $WinSCPLog" -ForegroundColor Gray
}

Write-Host ""
Read-Host "Druecke Enter zum Beenden"
