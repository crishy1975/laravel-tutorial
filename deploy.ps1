<#
.SYNOPSIS
    Laravel Deploy Script fuer Hostinger (Multi-Account)
.DESCRIPTION
    Laedt Dateien per SFTP hoch und fuehrt Server-Befehle aus
.PARAMETER DryRun
    Nur anzeigen was passieren wuerde
.PARAMETER Force
    Ohne Bestaetigung ausfuehren
.PARAMETER Account
    1 = Resch GmbH, 2 = Resch KG, 3 = Beide
.EXAMPLE
    .\deploy.ps1
    .\deploy.ps1 -Account 1
    .\deploy.ps1 -Account 3 -Force
#>

param(
    [switch]$DryRun,
    [switch]$Force,
    [int]$Account = 0
)

# ==============================================================================
#  KONFIGURATION - BEIDE KONTEN
# ==============================================================================

$Accounts = @{
    1 = @{
        Name          = "Resch GmbH"
        SFTP_HOST     = "212.1.209.26"
        SFTP_USER     = "u192633638"
        SFTP_PORT     = 65002
        REMOTE_PATH   = "/home/u192633638/domains/reschc.space/public_html"
        WEBSITE_URL   = "https://reschc.space"
    }
    2 = @{
        Name          = "Resch KG"
        SFTP_HOST     = "212.1.209.26"
        SFTP_USER     = "u854179217"
        SFTP_PORT     = 65002
        REMOTE_PATH   = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        WEBSITE_URL   = "https://christianresch.esy.es/martin"
    }
}

$GlobalConfig = @{
    LOCAL_PATH    = "C:\Users\Christian\Documents\entwicklung\laravel-tutorial"
    WINSCP_PATH   = "C:\Program Files (x86)\WinSCP\WinSCP.com"
    IMPORT_PATH   = "C:\Users\Christian\Documents\entwicklung\laravel-tutorial\storage\import"
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
)

$SyncFiles = @(
    "composer.json"
    "composer.lock"
)

# XML-Dateien für Import
$ImportFiles = @(
    "Adressen.xml"
    "GebaeudeAbfrage.xml"
    "DatumAusfuehrung.xml"
    "FatturaPA.xml"
    "ArtikelGebaeude.xml"
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

function Show-Info {
    param([string]$Text)
    Write-Host "[INFO] $Text" -ForegroundColor Cyan
}

function Confirm-Step {
    param([string]$Question)
    $answer = Read-Host "$Question (j/n)"
    return ($answer -eq "j" -or $answer -eq "J" -or $answer -eq "y" -or $answer -eq "Y")
}

function Show-AccountMenu {
    Write-Host ""
    Write-Host "  Waehle das Ziel-Konto:" -ForegroundColor White
    Write-Host ""
    Write-Host "    [1] Resch GmbH    (reschc.space)" -ForegroundColor Yellow
    Write-Host "    [2] Resch KG      (christianresch.esy.es/martin)" -ForegroundColor Yellow
    Write-Host "    [3] BEIDE Konten" -ForegroundColor Magenta
    Write-Host ""
    Write-Host "    [0] Abbrechen" -ForegroundColor Gray
    Write-Host ""
    
    do {
        $choice = Read-Host "  Auswahl (1/2/3/0)"
    } while ($choice -notmatch '^[0123]$')
    
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
    $WinSCPScript += "open sftp://$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST):$($AccountConfig.SFTP_PORT) -hostkey=*`n"
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
#  SCHRITT 2: XML-DATEIEN HOCHLADEN
# ==============================================================================

function Upload-XmlFiles {
    param(
        [hashtable]$AccountConfig,
        [int]$AccountId
    )
    
    Show-Header "SCHRITT 2: XML-Dateien hochladen"
    
    # Pruefen welche XML-Dateien vorhanden sind
    $foundFiles = @()
    Write-Host "  Gefundene XML-Dateien in $($GlobalConfig.IMPORT_PATH):" -ForegroundColor White
    Write-Host ""
    
    foreach ($file in $ImportFiles) {
        $filePath = Join-Path $GlobalConfig.IMPORT_PATH $file
        if (Test-Path $filePath) {
            $fileInfo = Get-Item $filePath
            $size = [math]::Round($fileInfo.Length / 1KB, 1)
            Write-Host "    [x] $file ($size KB)" -ForegroundColor Green
            $foundFiles += $file
        } else {
            Write-Host "    [ ] $file (nicht gefunden)" -ForegroundColor Gray
        }
    }
    
    Write-Host ""
    
    if ($foundFiles.Count -eq 0) {
        Show-Warning "Keine XML-Dateien gefunden - ueberspringe Upload"
        return $true
    }
    
    # WinSCP Script erstellen
    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $WinSCPScript += "open sftp://$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST):$($AccountConfig.SFTP_PORT) -hostkey=*`n"
    $WinSCPScript += "`n"
    
    # Import-Ordner erstellen
    $remotePath = "$($AccountConfig.REMOTE_PATH)/storage/import"
    $WinSCPScript += "call mkdir -p `"$remotePath`" 2>/dev/null || true`n"
    $WinSCPScript += "`n"
    
    # XML-Dateien hochladen
    foreach ($file in $foundFiles) {
        $localFile = Join-Path $GlobalConfig.IMPORT_PATH $file
        $WinSCPScript += "echo Lade $file...`n"
        $WinSCPScript += "put `"$localFile`" `"$remotePath/`"`n"
    }
    
    $WinSCPScript += "`nclose`nexit`n"
    
    $WinSCPScriptPath = Join-Path $env:TEMP "deploy_xml_$AccountId.txt"
    $WinSCPScript | Out-File -FilePath $WinSCPScriptPath -Encoding ASCII
    
    Write-Host "  Lade XML-Dateien hoch..." -ForegroundColor Yellow
    
    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde $($foundFiles.Count) XML-Dateien hochladen" -ForegroundColor Magenta
        return $true
    }
    
    $WinSCPLog = Join-Path $env:TEMP "winscp_xml_$AccountId.log"
    $process = Start-Process -FilePath $GlobalConfig.WINSCP_PATH -ArgumentList "/script=`"$WinSCPScriptPath`" /log=`"$WinSCPLog`"" -NoNewWindow -Wait -PassThru
    
    Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue
    
    if ($process.ExitCode -ne 0) {
        Show-Err "XML-Upload fehlgeschlagen! Siehe Log: $WinSCPLog"
        return $false
    }
    
    Show-Success "$($foundFiles.Count) XML-Dateien hochgeladen"
    return $true
}

# ==============================================================================
#  SCHRITT 3: MIGRATION STARTEN
# ==============================================================================

function Run-Migration {
    param(
        [hashtable]$AccountConfig
    )
    
    Show-Header "SCHRITT 3: Datenbank-Migration"
    
    $SSHCommands = @"
cd $($AccountConfig.REMOTE_PATH)
echo '=== Composer Install ==='
composer install --no-dev --optimize-autoloader --no-interaction
echo ''
echo '=== Migrationen ==='
php artisan migrate --force
echo ''
echo '=== Cache leeren ==='
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo ''
echo '=== Berechtigungen ==='
chmod -R 775 storage bootstrap/cache
echo ''
echo '=== Wartungsmodus aus ==='
php artisan up
echo ''
echo '=== MIGRATION ABGESCHLOSSEN ==='
"@
    
    Write-Host "  Fuehre folgende Befehle aus:" -ForegroundColor White
    Write-Host ""
    Write-Host "    - composer install" -ForegroundColor Gray
    Write-Host "    - php artisan migrate --force" -ForegroundColor Gray
    Write-Host "    - Cache leeren & neu aufbauen" -ForegroundColor Gray
    Write-Host "    - Berechtigungen setzen" -ForegroundColor Gray
    Write-Host "    - Wartungsmodus beenden" -ForegroundColor Gray
    Write-Host ""
    
    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde Migrationen ausfuehren" -ForegroundColor Magenta
        return $true
    }
    
    Write-Host "  Verbinde per SSH..." -ForegroundColor Yellow
    Write-Host ""
    
    $SSHCommands | ssh -p $AccountConfig.SFTP_PORT "$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)"
    
    if ($LASTEXITCODE -ne 0) {
        Show-Warning "SSH-Befehle evtl. fehlgeschlagen"
        return $false
    }
    
    Show-Success "Migration abgeschlossen"
    return $true
}

# ==============================================================================
#  SCHRITT 4: XML-IMPORT STARTEN
# ==============================================================================

function Run-XmlImport {
    param(
        [hashtable]$AccountConfig
    )
    
    Show-Header "SCHRITT 4: XML-Import"
    
    Write-Host "  Import-Reihenfolge:" -ForegroundColor White
    Write-Host ""
    Write-Host "    1. Adressen" -ForegroundColor Gray
    Write-Host "    2. Gebaeude" -ForegroundColor Gray
    Write-Host "    3. Timeline (Reinigungen 2024+2025)" -ForegroundColor Gray
    Write-Host "    4. Rechnungen (FatturaPA)" -ForegroundColor Gray
    Write-Host "    5. Artikel/Positionen" -ForegroundColor Gray
    Write-Host ""
    
    $SSHCommands = @"
cd $($AccountConfig.REMOTE_PATH)
echo ''
echo '=========================================='
echo '  1. ADRESSEN IMPORTIEREN'
echo '=========================================='
php artisan import:access storage/import/Adressen.xml --adressen 2>/dev/null || echo 'Adressen.xml nicht gefunden oder Fehler'
echo ''
echo '=========================================='
echo '  2. GEBAEUDE IMPORTIEREN'
echo '=========================================='
php artisan import:access storage/import/GebaeudeAbfrage.xml --gebaeude 2>/dev/null || echo 'GebaeudeAbfrage.xml nicht gefunden oder Fehler'
echo ''
echo '=========================================='
echo '  3. TIMELINE IMPORTIEREN (2024+2025)'
echo '=========================================='
php artisan import:timeline storage/import/DatumAusfuehrung.xml 2>/dev/null || echo 'DatumAusfuehrung.xml nicht gefunden oder Fehler'
echo ''
echo '=========================================='
echo '  4. RECHNUNGEN IMPORTIEREN'
echo '=========================================='
php artisan import:rechnungen storage/import/FatturaPA.xml 2>/dev/null || echo 'FatturaPA.xml nicht gefunden oder Fehler'
echo ''
echo '=========================================='
echo '  5. ARTIKEL IMPORTIEREN'
echo '=========================================='
php artisan import:access storage/import/ArtikelGebaeude.xml --positionen 2>/dev/null || echo 'ArtikelGebaeude.xml nicht gefunden oder Fehler'
echo ''
echo '=========================================='
echo '  IMPORT ABGESCHLOSSEN'
echo '=========================================='
"@
    
    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde XML-Import starten" -ForegroundColor Magenta
        return $true
    }
    
    Write-Host "  Starte Import per SSH..." -ForegroundColor Yellow
    Write-Host ""
    
    $SSHCommands | ssh -p $AccountConfig.SFTP_PORT "$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)"
    
    if ($LASTEXITCODE -ne 0) {
        Show-Warning "Import evtl. fehlgeschlagen"
        return $false
    }
    
    Show-Success "XML-Import abgeschlossen"
    return $true
}

# ==============================================================================
#  SCHRITT 5: SSH-SESSION STARTEN
# ==============================================================================

function Start-SSHSession {
    param(
        [hashtable]$AccountConfig
    )
    
    Show-Header "SCHRITT 5: SSH-Session"
    
    Write-Host "  Oeffne interaktive SSH-Verbindung..." -ForegroundColor White
    Write-Host ""
    Write-Host "  Nuetzliche Befehle:" -ForegroundColor Yellow
    Write-Host "    cd $($AccountConfig.REMOTE_PATH)" -ForegroundColor Gray
    Write-Host "    php artisan tinker" -ForegroundColor Gray
    Write-Host "    php artisan queue:work" -ForegroundColor Gray
    Write-Host "    tail -f storage/logs/laravel.log" -ForegroundColor Gray
    Write-Host "    exit  (zum Beenden)" -ForegroundColor Gray
    Write-Host ""
    
    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde SSH-Session starten" -ForegroundColor Magenta
        return $true
    }
    
    # Interaktive SSH-Session starten
    ssh -p $AccountConfig.SFTP_PORT "$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)"
    
    Show-Success "SSH-Session beendet"
    return $true
}

# ==============================================================================
#  HAUPT-DEPLOYMENT FUNKTION
# ==============================================================================

function Deploy-ToAccount {
    param(
        [int]$AccountId,
        [hashtable]$AccountConfig
    )
    
    Show-Header "DEPLOYMENT: $($AccountConfig.Name)"
    
    Write-Host "  Website: $($AccountConfig.WEBSITE_URL)" -ForegroundColor Cyan
    Write-Host ""
    
    # ══════════════════════════════════════════════════════════════
    # SCHRITT 1: Projekt hochladen (immer)
    # ══════════════════════════════════════════════════════════════
    
    $result = Upload-LaravelProject -AccountConfig $AccountConfig -AccountId $AccountId
    if (-not $result) { return $false }
    
    # ══════════════════════════════════════════════════════════════
    # SCHRITT 2: XML-Dateien hochladen?
    # ══════════════════════════════════════════════════════════════
    
    Write-Host ""
    if (Confirm-Step "  XML-Dateien hochladen?") {
        $result = Upload-XmlFiles -AccountConfig $AccountConfig -AccountId $AccountId
        if (-not $result) { 
            Show-Warning "XML-Upload fehlgeschlagen, fahre trotzdem fort..."
        }
    } else {
        Show-Info "XML-Upload uebersprungen"
    }
    
    # ══════════════════════════════════════════════════════════════
    # SCHRITT 3: Migration starten?
    # ══════════════════════════════════════════════════════════════
    
    Write-Host ""
    if (Confirm-Step "  Datenbank-Migration starten?") {
        $result = Run-Migration -AccountConfig $AccountConfig
        if (-not $result) {
            Show-Warning "Migration evtl. fehlgeschlagen, fahre trotzdem fort..."
        }
    } else {
        Show-Info "Migration uebersprungen"
        # Trotzdem Wartungsmodus beenden
        Write-Host "  Beende Wartungsmodus..." -ForegroundColor Yellow
        "cd $($AccountConfig.REMOTE_PATH) && php artisan up" | ssh -p $AccountConfig.SFTP_PORT "$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)" 2>$null
    }
    
    # ══════════════════════════════════════════════════════════════
    # SCHRITT 4: XML-Import starten?
    # ══════════════════════════════════════════════════════════════
    
    Write-Host ""
    if (Confirm-Step "  XML-Import starten?") {
        $result = Run-XmlImport -AccountConfig $AccountConfig
        if (-not $result) {
            Show-Warning "Import evtl. fehlgeschlagen"
        }
    } else {
        Show-Info "XML-Import uebersprungen"
    }
    
    # ══════════════════════════════════════════════════════════════
    # SCHRITT 5: SSH-Session starten?
    # ══════════════════════════════════════════════════════════════
    
    Write-Host ""
    if (Confirm-Step "  SSH-Session starten?") {
        Start-SSHSession -AccountConfig $AccountConfig
    } else {
        Show-Info "SSH-Session uebersprungen"
    }
    
    # ══════════════════════════════════════════════════════════════
    # ABSCHLUSS
    # ══════════════════════════════════════════════════════════════
    
    Write-Host ""
    Write-Host "  ----------------------------------------" -ForegroundColor Gray
    Write-Host "  Website testen: $($AccountConfig.WEBSITE_URL)" -ForegroundColor Green
    Write-Host ""
    
    return $true
}

# ==============================================================================
#  SCRIPT START
# ==============================================================================

Clear-Host
Show-Header "LARAVEL DEPLOYMENT - HOSTINGER"

Write-Host "  Lokaler Pfad:  $($GlobalConfig.LOCAL_PATH)" -ForegroundColor White
Write-Host "  Import-Pfad:   $($GlobalConfig.IMPORT_PATH)" -ForegroundColor White
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

# Account-Auswahl
if ($Account -eq 0) {
    $Account = Show-AccountMenu
}

if ($Account -eq 0) {
    Write-Host "Abgebrochen."
    exit 0
}

# Bestaetigung
if (-not $Force -and -not $DryRun) {
    $targetText = switch ($Account) {
        1 { "Resch GmbH" }
        2 { "Resch KG" }
        3 { "BEIDE KONTEN" }
    }
    Write-Host ""
    $confirm = Read-Host "Deployment auf [$targetText] starten? (j/n)"
    if ($confirm -ne "j" -and $confirm -ne "J") {
        Write-Host "Abgebrochen."
        exit 0
    }
}

# ==============================================================================
#  DEPLOYMENT AUSFUEHREN
# ==============================================================================

$success = $true

if ($Account -eq 1 -or $Account -eq 3) {
    $result = Deploy-ToAccount -AccountId 1 -AccountConfig $Accounts[1]
    if (-not $result) { $success = $false }
}

if ($Account -eq 2 -or $Account -eq 3) {
    $result = Deploy-ToAccount -AccountId 2 -AccountConfig $Accounts[2]
    if (-not $result) { $success = $false }
}

# ==============================================================================
#  ABSCHLUSS
# ==============================================================================

Show-Header "DEPLOYMENT ABGESCHLOSSEN"

if ($success) {
    Write-Host "  Alle Deployments erfolgreich!" -ForegroundColor Green
} else {
    Show-Warning "Einige Schritte hatten Fehler. Bitte pruefen!"
}

Write-Host ""

if ($Account -eq 1 -or $Account -eq 3) {
    Write-Host "  [1] $($Accounts[1].WEBSITE_URL)" -ForegroundColor Cyan
}
if ($Account -eq 2 -or $Account -eq 3) {
    Write-Host "  [2] $($Accounts[2].WEBSITE_URL)" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "  SSH-Verbindung (falls noetig):" -ForegroundColor Gray
if ($Account -eq 1 -or $Account -eq 3) {
    Write-Host "    ssh $($Accounts[1].SFTP_USER)@$($Accounts[1].SFTP_HOST) -p $($Accounts[1].SFTP_PORT)" -ForegroundColor DarkGray
}
if ($Account -eq 2 -or $Account -eq 3) {
    Write-Host "    ssh $($Accounts[2].SFTP_USER)@$($Accounts[2].SFTP_HOST) -p $($Accounts[2].SFTP_PORT)" -ForegroundColor DarkGray
}

Write-Host ""
Read-Host "Druecke Enter zum Beenden"
