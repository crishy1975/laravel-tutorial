<#
.SYNOPSIS
    Laravel Deploy Script fuer Hostinger (Multi-Account)
.DESCRIPTION
    Laedt Dateien per SFTP hoch und fuehrt Server-Befehle aus
.PARAMETER DryRun
    Nur anzeigen was passieren wuerde
.PARAMETER SkipSSH
    Nur Upload, keine SSH-Befehle
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
    [switch]$SkipSSH,
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

function Deploy-ToAccount {
    param(
        [int]$AccountId,
        [hashtable]$AccountConfig,
        [switch]$DryRun,
        [switch]$SkipSSH
    )
    
    Show-Header "DEPLOYMENT: $($AccountConfig.Name)"
    
    Write-Host "  Server:  $($AccountConfig.SFTP_HOST)" -ForegroundColor White
    Write-Host "  User:    $($AccountConfig.SFTP_USER)" -ForegroundColor White
    Write-Host "  Remote:  $($AccountConfig.REMOTE_PATH)" -ForegroundColor White
    Write-Host "  Website: $($AccountConfig.WEBSITE_URL)" -ForegroundColor Cyan
    Write-Host ""
    
    # ========================================
    # WINSCP SCRIPT ERSTELLEN
    # ========================================
    
    Show-Step 1 4 "Erstelle Upload-Script..."
    
    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $WinSCPScript += "open sftp://$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST):$($AccountConfig.SFTP_PORT) -hostkey=*`n"
    $WinSCPScript += "`n"
    $WinSCPScript += "# Wartungsmodus aktivieren`n"
    $WinSCPScript += "call php $($AccountConfig.REMOTE_PATH)/artisan down --quiet 2>&1 || true`n"
    $WinSCPScript += "`n"
    
    # Ordner hinzufuegen
    foreach ($folder in $SyncFolders) {
        $localPath = Join-Path $GlobalConfig.LOCAL_PATH $folder.Local
        $remotePath = "$($AccountConfig.REMOTE_PATH)/$($folder.Remote)"
        
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
        $localFile = Join-Path $GlobalConfig.LOCAL_PATH $file
        if (Test-Path $localFile) {
            $WinSCPScript += "echo Lade $file...`n"
            $WinSCPScript += "put `"$localFile`" `"$($AccountConfig.REMOTE_PATH)/`"`n"
            $WinSCPScript += "`n"
        }
    }
    
    $WinSCPScript += "close`n"
    $WinSCPScript += "exit`n"
    
    $WinSCPScriptPath = Join-Path $env:TEMP "deploy_winscp_$AccountId.txt"
    $WinSCPScript | Out-File -FilePath $WinSCPScriptPath -Encoding ASCII
    
    Show-Success "Upload-Script erstellt"
    
    # ========================================
    # DATEIEN HOCHLADEN
    # ========================================
    
    Show-Step 2 4 "Lade Dateien hoch (SFTP)..."
    Write-Host ""
    
    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde folgende Ordner synchronisieren:" -ForegroundColor Magenta
        foreach ($folder in $SyncFolders) {
            $localPath = Join-Path $GlobalConfig.LOCAL_PATH $folder.Local
            if (Test-Path $localPath) {
                Write-Host "    -> $($folder.Local)/" -ForegroundColor White
            }
        }
        Write-Host ""
    } else {
        $WinSCPLog = Join-Path $env:TEMP "winscp_deploy_$AccountId.log"
        
        $processArgs = "/script=`"$WinSCPScriptPath`" /log=`"$WinSCPLog`""
        $process = Start-Process -FilePath $GlobalConfig.WINSCP_PATH -ArgumentList $processArgs -NoNewWindow -Wait -PassThru
        
        if ($process.ExitCode -ne 0) {
            Show-Err "Upload fehlgeschlagen! Siehe Log: $WinSCPLog"
            return $false
        }
    }
    
    Show-Success "Dateien hochgeladen"
    
    # ========================================
    # SSH BEFEHLE AUSFUEHREN
    # ========================================
    
    Show-Step 3 4 "Server-Befehle (SSH)..."
    Write-Host ""
    
    $SSHCommands = @"
cd $($AccountConfig.REMOTE_PATH)
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
    
    if ($SkipSSH) {
        Show-Warning "SSH-Befehle uebersprungen"
        Write-Host ""
        Write-Host "  Manuell verbinden:" -ForegroundColor Yellow
        Write-Host "  ssh $($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST) -p $($AccountConfig.SFTP_PORT)" -ForegroundColor Cyan
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
        $SSHCommands | ssh -p $AccountConfig.SFTP_PORT "$($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)"
        
        if ($LASTEXITCODE -ne 0) {
            Write-Host ""
            Show-Warning "SSH-Befehle evtl. fehlgeschlagen. Pruefe die Website!"
        }
    }
    
    Show-Success "Server-Befehle ausgefuehrt"
    
    # ========================================
    # AUFRAEUMEN
    # ========================================
    
    Show-Step 4 4 "Aufraeumen..."
    Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue
    
    Write-Host ""
    Write-Host "  Website testen: $($AccountConfig.WEBSITE_URL)" -ForegroundColor Green
    Write-Host ""
    
    return $true
}

# ==============================================================================
#  SCRIPT START
# ==============================================================================

Clear-Host
Show-Header "LARAVEL DEPLOYMENT - HOSTINGER (Multi-Account)"

Write-Host "  Lokaler Pfad: $($GlobalConfig.LOCAL_PATH)" -ForegroundColor White
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
    if ($confirm -ne "j" -and $confirm -ne "J" -and $confirm -ne "y" -and $confirm -ne "Y") {
        Write-Host "Abgebrochen."
        exit 0
    }
}

# ==============================================================================
#  DEPLOYMENT AUSFUEHREN
# ==============================================================================

$success = $true

if ($Account -eq 1 -or $Account -eq 3) {
    $result = Deploy-ToAccount -AccountId 1 -AccountConfig $Accounts[1] -DryRun:$DryRun -SkipSSH:$SkipSSH
    if (-not $result) { $success = $false }
}

if ($Account -eq 2 -or $Account -eq 3) {
    $result = Deploy-ToAccount -AccountId 2 -AccountConfig $Accounts[2] -DryRun:$DryRun -SkipSSH:$SkipSSH
    if (-not $result) { $success = $false }
}

# ==============================================================================
#  ABSCHLUSS
# ==============================================================================

Show-Header "DEPLOYMENT ABGESCHLOSSEN"

if ($success) {
    Write-Host "  Alle Deployments erfolgreich!" -ForegroundColor Green
} else {
    Show-Warning "Einige Deployments hatten Fehler. Bitte pruefen!"
}

Write-Host ""

if ($Account -eq 1 -or $Account -eq 3) {
    Write-Host "  [1] $($Accounts[1].WEBSITE_URL)" -ForegroundColor Cyan
}
if ($Account -eq 2 -or $Account -eq 3) {
    Write-Host "  [2] $($Accounts[2].WEBSITE_URL)" -ForegroundColor Cyan
}

# ==============================================================================
#  ACCESS IMPORT (OPTIONAL)
# ==============================================================================

if (-not $DryRun -and -not $SkipSSH) {
    Write-Host ""
    Write-Host "  ----------------------------------------" -ForegroundColor Gray
    $importConfirm = Read-Host "  Access-Import starten? (php artisan import:access) (j/n)"
    
    if ($importConfirm -eq "j" -or $importConfirm -eq "J" -or $importConfirm -eq "y" -or $importConfirm -eq "Y") {
        Write-Host ""
        
        if ($Account -eq 1 -or $Account -eq 3) {
            Show-Header "ACCESS IMPORT: $($Accounts[1].Name)"
            Write-Host "  Starte Import auf $($Accounts[1].Name)..." -ForegroundColor Yellow
            Write-Host ""
            
            $ImportCmd = "cd $($Accounts[1].REMOTE_PATH) && php artisan import:access"
            $ImportCmd | ssh -p $Accounts[1].SFTP_PORT "$($Accounts[1].SFTP_USER)@$($Accounts[1].SFTP_HOST)"
            
            if ($LASTEXITCODE -eq 0) {
                Show-Success "Import auf $($Accounts[1].Name) abgeschlossen"
            } else {
                Show-Warning "Import auf $($Accounts[1].Name) evtl. fehlgeschlagen"
            }
        }
        
        if ($Account -eq 2 -or $Account -eq 3) {
            Show-Header "ACCESS IMPORT: $($Accounts[2].Name)"
            Write-Host "  Starte Import auf $($Accounts[2].Name)..." -ForegroundColor Yellow
            Write-Host ""
            
            $ImportCmd = "cd $($Accounts[2].REMOTE_PATH) && php artisan import:access"
            $ImportCmd | ssh -p $Accounts[2].SFTP_PORT "$($Accounts[2].SFTP_USER)@$($Accounts[2].SFTP_HOST)"
            
            if ($LASTEXITCODE -eq 0) {
                Show-Success "Import auf $($Accounts[2].Name) abgeschlossen"
            } else {
                Show-Warning "Import auf $($Accounts[2].Name) evtl. fehlgeschlagen"
            }
        }
    } else {
        Write-Host "  Import uebersprungen." -ForegroundColor Gray
    }
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
