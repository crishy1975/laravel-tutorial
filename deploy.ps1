<#
.SYNOPSIS
    Laravel Deploy Script fuer Hostinger (Multi-Account)
.DESCRIPTION
    Laedt Dateien per SFTP hoch und fuehrt Server-Befehle aus
.PARAMETER DryRun
    Nur anzeigen was passieren wuerde
.PARAMETER Account
    1 = Resch GmbH, 2 = Resch KG, 3 = Sandbox, 4 = Alle
.EXAMPLE
    .\deploy.ps1
    .\deploy.ps1 -Account 1
    .\deploy.ps1 -Account 3
    .\deploy.ps1 -Account 4
#>

param(
    [switch]$DryRun,
    [int]$Account = 0
)

# ==============================================================================
#  KONFIGURATION - ALLE KONTEN
# ==============================================================================

# ──────────────────────────────────────────────────────────────────────────
#  PASSWOERTER AUS EXTERNER DATEI LADEN (deploy-passwords.env)
#  Datei muss im selben Ordner liegen und in .gitignore stehen!
# ──────────────────────────────────────────────────────────────────────────
$PasswordFile = Join-Path $PSScriptRoot "deploy-passwords.env"
if (-not (Test-Path $PasswordFile)) {
    Write-Host "[FEHLER] Passwort-Datei nicht gefunden: $PasswordFile" -ForegroundColor Red
    Write-Host ""
    Write-Host "Erstelle die Datei 'deploy-passwords.env' mit folgendem Inhalt:" -ForegroundColor Yellow
    Write-Host '  set PW_u192633638=DEIN_PASSWORT' -ForegroundColor Gray
    Write-Host '  set PW_u854179217=DEIN_PASSWORT' -ForegroundColor Gray
    Write-Host ""
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
        Name          = "Resch GmbH"
        SFTP_HOST     = "212.1.209.26"
        SFTP_USER     = "u192633638"
        SFTP_PORT     = 65002
        REMOTE_PATH   = "/home/u192633638/domains/reschc.space/public_html"
        WEBSITE_URL   = "https://reschc.space"
        Color         = "Green"
    }
    2 = @{
        Name          = "Resch KG"
        SFTP_HOST     = "212.1.209.26"
        SFTP_USER     = "u854179217"
        SFTP_PORT     = 65002
        REMOTE_PATH   = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        WEBSITE_URL   = "https://christianresch.esy.es/martin"
        Color         = "Yellow"
    }
    3 = @{
        Name          = "Sandbox (Test)"
        SFTP_HOST     = "212.1.209.26"
        SFTP_USER     = "u854179217"
        SFTP_PORT     = 65002
        REMOTE_PATH   = "/home/u854179217/domains/christianresch.esy.es/public_html/sandbox"
        WEBSITE_URL   = "https://christianresch.esy.es/sandbox"
        Color         = "Cyan"
    }
}

# Dynamische Pfade - basierend auf Skript-Standort
$GlobalConfig = @{
    LOCAL_PATH    = $PSScriptRoot
    WINSCP_PATH   = "C:\Program Files (x86)\WinSCP\WinSCP.com"
    IMPORT_PATH   = Join-Path $PSScriptRoot "storage\import"
}

# ==============================================================================
#  ORDNER DIE SYNCHRONISIERT WERDEN
# ==============================================================================

$SyncFolders = @(
    @{ Local = "app";                  Remote = "app";                  Delete = $true  }
    @{ Local = "bootstrap";            Remote = "bootstrap";            Delete = $false }
    @{ Local = "config";               Remote = "config";               Delete = $true  }
    @{ Local = "database\migrations";  Remote = "database/migrations";  Delete = $false }
    @{ Local = "database\seeders";     Remote = "database/seeders";     Delete = $false }
    @{ Local = "resources";            Remote = "resources";            Delete = $true  }
    @{ Local = "routes";               Remote = "routes";               Delete = $true  }
    @{ Local = "public\css";           Remote = "public/css";           Delete = $true  }
    @{ Local = "public\js";            Remote = "public/js";            Delete = $true  }
    @{ Local = "public\build";         Remote = "public/build";         Delete = $true  }
    @{ Local = "public\images";        Remote = "public/images";        Delete = $false }
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

function Show-Info {
    param([string]$Text)
    Write-Host "[INFO] $Text" -ForegroundColor Cyan
}

function Show-AccountMenu {
    Write-Host ""
    Write-Host "  Waehle das Ziel-Konto:" -ForegroundColor White
    Write-Host ""
    Write-Host "  === PRODUKTION ===" -ForegroundColor White
    Write-Host "    [1] Resch GmbH    (reschc.space)" -ForegroundColor Green
    Write-Host "    [2] Resch KG      (christianresch.esy.es/martin)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  === TEST ===" -ForegroundColor White
    Write-Host "    [3] Sandbox       (christianresch.esy.es/sandbox)" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  === MEHRFACH ===" -ForegroundColor White
    Write-Host "    [4] ALLE Konten (1+2+3)" -ForegroundColor Magenta
    Write-Host ""
    Write-Host "    [0] Abbrechen" -ForegroundColor Gray
    Write-Host ""
    
    do {
        $choice = Read-Host "  Auswahl (0-4)"
    } while ($choice -notmatch '^[01234]$')
    
    return [int]$choice
}

# ==============================================================================
#  SCHRITT 1: LARAVEL-PROJEKT HOCHLADEN
# ==============================================================================

function Upload-LaravelProject {
    param(
        [hashtable]$AccountConfig,
        [int]$AccountId
    )
    
    Show-Header "SCHRITT 1: Laravel-Projekt hochladen"
    
    Write-Host "  Server:  $($AccountConfig.SFTP_HOST)" -ForegroundColor White
    Write-Host "  User:    $($AccountConfig.SFTP_USER)" -ForegroundColor White
    Write-Host "  Remote:  $($AccountConfig.REMOTE_PATH)" -ForegroundColor White
    Write-Host ""
    
    # WinSCP Script erstellen
    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $pw = $Passwords[$AccountConfig.SFTP_USER]
    $WinSCPScript += "open sftp://$($AccountConfig.SFTP_USER):$pw@$($AccountConfig.SFTP_HOST):$($AccountConfig.SFTP_PORT) -hostkey=*`n"
    $WinSCPScript += "`n"
    
    # Wartungsmodus aktivieren
    $WinSCPScript += "# Wartungsmodus aktivieren`n"
    $WinSCPScript += "call php $($AccountConfig.REMOTE_PATH)/artisan down --quiet 2>&1 || true`n"
    $WinSCPScript += "`n"
    
    # Remote-Ordner erstellen
    $WinSCPScript += "# Remote-Ordner erstellen (falls nicht vorhanden)`n"
    foreach ($folder in $SyncFolders) {
        $localPath = Join-Path $GlobalConfig.LOCAL_PATH $folder.Local
        $remotePath = "$($AccountConfig.REMOTE_PATH)/$($folder.Remote)"
        
        if (Test-Path $localPath) {
            $WinSCPScript += "call mkdir -p `"$remotePath`" 2>/dev/null || true`n"
        }
    }
    $WinSCPScript += "`n"
    
    # Ordner synchronisieren
    foreach ($folder in $SyncFolders) {
        $localPath = Join-Path $GlobalConfig.LOCAL_PATH $folder.Local
        $remotePath = "$($AccountConfig.REMOTE_PATH)/$($folder.Remote)"
        
        if (Test-Path $localPath) {
            $deleteFlag = ""
            if ($folder.Delete) { $deleteFlag = "-delete" }
            $WinSCPScript += "echo Synchronisiere $($folder.Local)/...`n"
            $WinSCPScript += "synchronize remote $deleteFlag `"$localPath`" `"$remotePath`"`n"
            $WinSCPScript += "`n"
        }
    }
    
    # Einzelne Dateien
    foreach ($file in $SyncFiles) {
        $localFile = Join-Path $GlobalConfig.LOCAL_PATH $file
        if (Test-Path $localFile) {
            $WinSCPScript += "echo Lade $file...`n"
            $WinSCPScript += "put `"$localFile`" `"$($AccountConfig.REMOTE_PATH)/`"`n"
        }
    }
    
    $WinSCPScript += "`nclose`nexit`n"
    
    $WinSCPScriptPath = Join-Path $env:TEMP "deploy_project_$AccountId.txt"
    $WinSCPScript | Out-File -FilePath $WinSCPScriptPath -Encoding ASCII
    
    Write-Host "  Lade Projekt-Dateien hoch..." -ForegroundColor Yellow
    
    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde Projekt hochladen" -ForegroundColor Magenta
        return $true
    }
    
    $WinSCPLog = Join-Path $env:TEMP "winscp_project_$AccountId.log"
    $process = Start-Process -FilePath $GlobalConfig.WINSCP_PATH -ArgumentList "/script=`"$WinSCPScriptPath`" /log=`"$WinSCPLog`"" -NoNewWindow -Wait -PassThru
    
    Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue
    
    if ($process.ExitCode -ne 0) {
        Show-Err "Upload fehlgeschlagen! Siehe Log: $WinSCPLog"
        return $false
    }
    
    Show-Success "Projekt-Dateien hochgeladen"
    return $true
}

# ==============================================================================
#  SCHRITT 2: MIGRATION + SERVER HOCHFAHREN
# ==============================================================================

function Run-Migration {
    param(
        [hashtable]$AccountConfig
    )
    
    Show-Header "SCHRITT 2: Migration + Server hochfahren"
    
    Write-Host "  Befehle:" -ForegroundColor White
    Write-Host "    - composer install --no-dev" -ForegroundColor Gray
    Write-Host "    - php artisan migrate --force" -ForegroundColor Gray
    Write-Host "    - php artisan optimize:clear" -ForegroundColor Gray
    Write-Host "    - php artisan optimize" -ForegroundColor Gray
    Write-Host "    - chmod -R 775 storage bootstrap/cache" -ForegroundColor Gray
    Write-Host "    - php artisan up" -ForegroundColor Gray
    Write-Host ""
    
    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde Migration starten" -ForegroundColor Magenta
        return $true
    }
    
    Write-Host "  Starte Migration per SSH..." -ForegroundColor Yellow
    Write-Host ""
    
    $remotePath = $AccountConfig.REMOTE_PATH
    $pw = $Passwords[$AccountConfig.SFTP_USER]
    
    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $WinSCPScript += "open sftp://$($AccountConfig.SFTP_USER):$pw@$($AccountConfig.SFTP_HOST):$($AccountConfig.SFTP_PORT) -hostkey=*`n"
    $WinSCPScript += "`n"
    $WinSCPScript += "call cd $remotePath && echo '=== Composer Install ===' && composer install --no-dev --optimize-autoloader --no-interaction && echo '' && echo '=== Migrationen ===' && php artisan migrate --force && echo '' && echo '=== Optimize ===' && php artisan optimize:clear && php artisan optimize && echo '' && echo '=== Berechtigungen ===' && chmod -R 775 storage bootstrap/cache; echo '' && echo '=== Wartungsmodus aus ===' && php artisan up && echo '' && echo '=== FERTIG ==='`n"
    $WinSCPScript += "`nclose`nexit`n"
    
    $WinSCPScriptPath = Join-Path $env:TEMP "deploy_migration.txt"
    $WinSCPScript | Out-File -FilePath $WinSCPScriptPath -Encoding ASCII
    
    $WinSCPLog = Join-Path $env:TEMP "winscp_migration.log"
    $process = Start-Process -FilePath $GlobalConfig.WINSCP_PATH -ArgumentList "/script=`"$WinSCPScriptPath`" /log=`"$WinSCPLog`"" -NoNewWindow -Wait -PassThru
    
    Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue
    
    if ($process.ExitCode -ne 0) {
        Show-Warning "Migration evtl. fehlgeschlagen - versuche Server hochzufahren..."
        # Sicherheits-Fallback: Server immer hochfahren
        Ensure-ServerUp -AccountConfig $AccountConfig
        return $false
    }
    
    Show-Success "Migration abgeschlossen - Server ist online"
    return $true
}

# ==============================================================================
#  SICHERHEIT: SERVER IMMER HOCHFAHREN
# ==============================================================================

function Ensure-ServerUp {
    param(
        [hashtable]$AccountConfig
    )
    
    Write-Host ""
    Show-Info "Stelle sicher dass der Server online ist..."
    
    $remotePath = $AccountConfig.REMOTE_PATH
    $pw = $Passwords[$AccountConfig.SFTP_USER]
    
    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $WinSCPScript += "open sftp://$($AccountConfig.SFTP_USER):$pw@$($AccountConfig.SFTP_HOST):$($AccountConfig.SFTP_PORT) -hostkey=*`n"
    $WinSCPScript += "call cd $remotePath && php artisan up 2>&1 && echo 'Server ist online'`n"
    $WinSCPScript += "`nclose`nexit`n"
    
    $WinSCPScriptPath = Join-Path $env:TEMP "deploy_serverup.txt"
    $WinSCPScript | Out-File -FilePath $WinSCPScriptPath -Encoding ASCII
    
    $process = Start-Process -FilePath $GlobalConfig.WINSCP_PATH -ArgumentList "/script=`"$WinSCPScriptPath`"" -NoNewWindow -Wait -PassThru
    
    Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue
    
    if ($process.ExitCode -eq 0) {
        Show-Success "Server ist online"
    } else {
        Show-Err "Server konnte nicht hochgefahren werden! Manuell pruefen: php artisan up"
    }
}

# ==============================================================================
#  SSH-SESSION INFO
# ==============================================================================

function Show-SSHInfo {
    param(
        [hashtable]$AccountConfig
    )
    
    Write-Host ""
    Write-Host "  ── SSH-Verbindung ──" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  ssh -p $($AccountConfig.SFTP_PORT) $($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)" -ForegroundColor Yellow
    Write-Host "  cd $($AccountConfig.REMOTE_PATH)" -ForegroundColor Gray
    Write-Host ""
    
    # In Zwischenablage kopieren
    $sshCmd = "ssh -p $($AccountConfig.SFTP_PORT) $($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)"
    $sshCmd | Set-Clipboard
    Show-Info "SSH-Befehl in Zwischenablage kopiert"
}

# ==============================================================================
#  HAUPT-DEPLOYMENT FUNKTION
# ==============================================================================

function Deploy-ToAccount {
    param(
        [int]$AccountId,
        [hashtable]$AccountConfig
    )
    
    $color = $AccountConfig.Color
    if (-not $color) { $color = "White" }
    
    Show-Header "DEPLOYMENT: $($AccountConfig.Name)"
    
    Write-Host "  Website: $($AccountConfig.WEBSITE_URL)" -ForegroundColor $color
    Write-Host ""
    
    # SCHRITT 1: Projekt hochladen
    $result = Upload-LaravelProject -AccountConfig $AccountConfig -AccountId $AccountId
    if (-not $result) {
        Show-Err "Upload fehlgeschlagen!"
        Ensure-ServerUp -AccountConfig $AccountConfig
        return $false
    }
    
    # SCHRITT 2: Migration + Server hochfahren
    $result = Run-Migration -AccountConfig $AccountConfig
    if (-not $result) {
        Show-Warning "Migration hatte Probleme"
    }
    
    # SICHERHEIT: Server IMMER hochfahren (egal was vorher passiert ist)
    Ensure-ServerUp -AccountConfig $AccountConfig
    
    # SSH-Info anzeigen
    Show-SSHInfo -AccountConfig $AccountConfig
    
    # ABSCHLUSS
    Write-Host ""
    Write-Host "  Website testen: $($AccountConfig.WEBSITE_URL)" -ForegroundColor $color
    Write-Host ""
    
    return $true
}

# ==============================================================================
#  SCRIPT START
# ==============================================================================

Clear-Host
Show-Header "LARAVEL DEPLOYMENT - HOSTINGER"

Write-Host "  Lokaler Pfad:  $($GlobalConfig.LOCAL_PATH)" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Show-Warning "DRY-RUN MODUS - Keine Aenderungen werden durchgefuehrt!"
    Write-Host ""
}

# Pruefen ob WinSCP existiert
if (-not (Test-Path $GlobalConfig.WINSCP_PATH)) {
    Show-Err "WinSCP nicht gefunden: $($GlobalConfig.WINSCP_PATH)"
    Write-Host ""
    Write-Host "Bitte WinSCP installieren: https://winscp.net/eng/download.php"
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

# Pruefen ob lokaler Pfad existiert
if (-not (Test-Path $GlobalConfig.LOCAL_PATH)) {
    Show-Err "Lokaler Pfad nicht gefunden: $($GlobalConfig.LOCAL_PATH)"
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

# Account-Auswahl (einzige Frage)
if ($Account -eq 0) {
    $Account = Show-AccountMenu
}

if ($Account -eq 0) {
    Write-Host "Abgebrochen."
    exit 0
}

# ==============================================================================
#  DEPLOYMENT AUSFUEHREN (keine weiteren Fragen)
# ==============================================================================

$startTime = Get-Date
$success = $true

if ($Account -eq 1 -or $Account -eq 4) {
    $result = Deploy-ToAccount -AccountId 1 -AccountConfig $Accounts[1]
    if (-not $result) { $success = $false }
}

if ($Account -eq 2 -or $Account -eq 4) {
    $result = Deploy-ToAccount -AccountId 2 -AccountConfig $Accounts[2]
    if (-not $result) { $success = $false }
}

if ($Account -eq 3 -or $Account -eq 4) {
    $result = Deploy-ToAccount -AccountId 3 -AccountConfig $Accounts[3]
    if (-not $result) { $success = $false }
}

# ==============================================================================
#  ABSCHLUSS
# ==============================================================================

$duration = (Get-Date) - $startTime
Show-Header "DEPLOYMENT ABGESCHLOSSEN"

if ($success) {
    Write-Host "  Alle Deployments erfolgreich!" -ForegroundColor Green
} else {
    Show-Warning "Einige Schritte hatten Fehler. Bitte pruefen!"
}

Write-Host ""

if ($Account -eq 1 -or $Account -eq 4) {
    Write-Host "  [1] $($Accounts[1].WEBSITE_URL)" -ForegroundColor Green
}
if ($Account -eq 2 -or $Account -eq 4) {
    Write-Host "  [2] $($Accounts[2].WEBSITE_URL)" -ForegroundColor Yellow
}
if ($Account -eq 3 -or $Account -eq 4) {
    Write-Host "  [3] $($Accounts[3].WEBSITE_URL)" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "  Dauer: $($duration.Minutes) Min $($duration.Seconds) Sek" -ForegroundColor Gray
Write-Host ""
Read-Host "Druecke Enter zum Beenden"
