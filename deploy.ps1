<#
.SYNOPSIS
    Laravel Deploy Script (IONOS + Hostinger, Multi-Account)
.DESCRIPTION
    Laedt Dateien per SFTP hoch und fuehrt Server-Befehle aus
.PARAMETER DryRun
    Nur anzeigen was passieren wuerde
.PARAMETER Account
    1 = Resch GmbH (IONOS), 2 = Resch KG, 3 = Sandbox, 4 = Alle
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
    Write-Host '  set PW_su935318=DEIN_PASSWORT' -ForegroundColor Gray
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

# REMOTE_PATH = Pfad fuer SFTP-Upload
# SHELL_PATH  = Pfad fuer SSH-Befehle (bei IONOS verschieden, da SFTP "/" = Webspace-Wurzel)
# PHP         = PHP-CLI auf dem Server ("auto" = neueste php8.x automatisch suchen)
# COMPOSER    = Composer-Aufruf auf dem Server ($P = gefundenes PHP)
# COMPOSER_PHAR = $true -> composer.phar wird bei Bedarf automatisch in ~ installiert
$Accounts = @{
    1 = @{
        Name          = "Resch GmbH (IONOS)"
        SFTP_HOST     = "access-5021443468.webspace-host.com"
        SFTP_USER     = "su935318"
        SFTP_PORT     = 22
        REMOTE_PATH   = "/uschiweb"
        SHELL_PATH    = "~/uschiweb"
        PHP           = "php8.4"      # "auto" = neueste php8.x suchen
        COMPOSER      = '$P ~/composer.phar'
        COMPOSER_PHAR = $true
        WEBSITE_URL   = "https://uschiweb.it"
        Color         = "Green"
    }
    2 = @{
        Name          = "Resch KG"
        SFTP_HOST     = "212.1.209.26"
        SFTP_USER     = "u854179217"
        SFTP_PORT     = 65002
        REMOTE_PATH   = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        SHELL_PATH    = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        PHP           = "php"
        COMPOSER      = "composer"
        COMPOSER_PHAR = $false
        WEBSITE_URL   = "https://christianresch.esy.es/martin"
        Color         = "Yellow"
    }
    3 = @{
        Name          = "Sandbox (Test)"
        SFTP_HOST     = "212.1.209.26"
        SFTP_USER     = "u854179217"
        SFTP_PORT     = 65002
        REMOTE_PATH   = "/home/u854179217/domains/christianresch.esy.es/public_html/sandbox"
        SHELL_PATH    = "/home/u854179217/domains/christianresch.esy.es/public_html/sandbox"
        PHP           = "php"
        COMPOSER      = "composer"
        COMPOSER_PHAR = $false
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

function Get-OpenLine {
    param([hashtable]$AccountConfig)
    $pw = $Passwords[$AccountConfig.SFTP_USER]
    return "open sftp://$($AccountConfig.SFTP_USER):$pw@$($AccountConfig.SFTP_HOST):$($AccountConfig.SFTP_PORT) -hostkey=*`n"
}

# Sucht die neueste PHP 8.x CLI auf dem Server und legt sie in $P ab
$ShellDetectPhp = 'P=""; for c in php8.4-cli php8.3-cli php8.2-cli php8.4 php8.3 php8.2 /usr/bin/php8.4-cli /usr/bin/php8.3-cli /usr/bin/php8.2-cli /usr/bin/php8.4 /usr/bin/php8.3 /usr/bin/php8.2 php; do if command -v $c >/dev/null 2>&1; then P=$c; break; fi; done; echo "PHP: $P";'

# Erzeugt einen WinSCP "call"-Befehl.
# Laeuft in einer Subshell ( ... ), damit WinSCP danach NICHT versucht,
# ins Shell-Verzeichnis zu wechseln (bei IONOS ist SFTP ein anderer Pfad als SSH).
# Im Body steht `$P fuer das PHP-Binary.
function New-ShellCall {
    param(
        [hashtable]$Cfg,
        [string]$Body,
        [switch]$IgnoreErrors
    )
    if ($Cfg.PHP -eq "auto") { $prefix = $ShellDetectPhp } else { $prefix = "P=$($Cfg.PHP);" }
    $suffix = ""
    if ($IgnoreErrors) { $suffix = " || true" }
    return "call ( $prefix cd $($Cfg.SHELL_PATH) && $Body )$suffix`n"
}

function Show-AccountMenu {
    Write-Host ""
    Write-Host "  Waehle das Ziel-Konto:" -ForegroundColor White
    Write-Host ""
    Write-Host "  === PRODUKTION ===" -ForegroundColor White
    Write-Host "    [1] Resch GmbH    (uschiweb.it - IONOS)" -ForegroundColor Green
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

    $sp  = $AccountConfig.SHELL_PATH

    # WinSCP Script erstellen
    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $WinSCPScript += Get-OpenLine -AccountConfig $AccountConfig
    $WinSCPScript += "`n"

    # Remote-Ordner erstellen (inkl. storage-Struktur, die nicht synchronisiert wird)
    $dirs = @("storage/app/public", "storage/framework/cache/data", "storage/framework/sessions", "storage/framework/views", "storage/logs", "bootstrap/cache")
    foreach ($folder in $SyncFolders) {
        $localPath = Join-Path $GlobalConfig.LOCAL_PATH $folder.Local
        if (Test-Path $localPath) { $dirs += $folder.Remote }
    }
    $mkdirArgs = ($dirs | ForEach-Object { "$sp/$_" }) -join " "
    $WinSCPScript += "# Remote-Ordner erstellen (falls nicht vorhanden)`n"
    $WinSCPScript += "call ( mkdir -p $mkdirArgs ) || true`n"
    $WinSCPScript += "`n"

    # Wartungsmodus aktivieren (falls Projekt schon lauffaehig ist)
    $WinSCPScript += "# Wartungsmodus aktivieren`n"
    $WinSCPScript += New-ShellCall -Cfg $AccountConfig -Body "( test -f vendor/autoload.php && `$P artisan down 2>&1 ) || true" -IgnoreErrors
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
        Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue
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

    $comp = $AccountConfig.COMPOSER

    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $WinSCPScript += Get-OpenLine -AccountConfig $AccountConfig
    $WinSCPScript += "`n"

    # composer.phar bei Bedarf installieren (IONOS hat kein globales composer)
    if ($AccountConfig.COMPOSER_PHAR) {
        $WinSCPScript += New-ShellCall -Cfg $AccountConfig -Body "( test -f ~/composer.phar || ( echo '=== Installiere composer.phar ===' && curl -sS https://getcomposer.org/installer | `$P -- --install-dir=`$HOME ) )"
    }

    $WinSCPScript += New-ShellCall -Cfg $AccountConfig -Body "echo '=== Composer Install ===' && $comp install --no-dev --optimize-autoloader --no-interaction; echo '' && echo '=== Migrationen ===' && `$P artisan migrate --force; echo '' && echo '=== Optimize (Routen + Cache) ===' && `$P artisan optimize:clear && `$P artisan optimize; echo '' && echo '=== Berechtigungen ===' && chmod -R 775 storage bootstrap/cache; echo '' && echo '=== Wartungsmodus aus ===' && `$P artisan up && echo '' && echo '=== FERTIG ==='"
    $WinSCPScript += "`nclose`nexit`n"

    $WinSCPScriptPath = Join-Path $env:TEMP "deploy_migration.txt"
    $WinSCPScript | Out-File -FilePath $WinSCPScriptPath -Encoding ASCII

    $WinSCPLog = Join-Path $env:TEMP "winscp_migration.log"
    $process = Start-Process -FilePath $GlobalConfig.WINSCP_PATH -ArgumentList "/script=`"$WinSCPScriptPath`" /log=`"$WinSCPLog`"" -NoNewWindow -Wait -PassThru

    Remove-Item $WinSCPScriptPath -Force -ErrorAction SilentlyContinue

    if ($process.ExitCode -ne 0) {
        Show-Warning "Migration evtl. fehlgeschlagen - versuche Server hochzufahren..."
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

    if ($DryRun) {
        Write-Host "  [DRY-RUN] Wuerde Server hochfahren" -ForegroundColor Magenta
        return
    }

    $WinSCPScript = "option batch abort`n"
    $WinSCPScript += "option confirm off`n"
    $WinSCPScript += Get-OpenLine -AccountConfig $AccountConfig
    $WinSCPScript += New-ShellCall -Cfg $AccountConfig -Body "`$P artisan route:clear; `$P artisan route:cache; `$P artisan up 2>&1 && echo 'Server ist online + Routen aktualisiert'"
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
    Write-Host "  -- SSH-Verbindung --" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  ssh -p $($AccountConfig.SFTP_PORT) $($AccountConfig.SFTP_USER)@$($AccountConfig.SFTP_HOST)" -ForegroundColor Yellow
    Write-Host "  cd $($AccountConfig.SHELL_PATH)" -ForegroundColor Gray
    Write-Host "  PHP: $($AccountConfig.PHP)" -ForegroundColor Gray
    Write-Host ""

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

    if (-not $Passwords.ContainsKey($AccountConfig.SFTP_USER)) {
        Show-Err "Kein Passwort fuer $($AccountConfig.SFTP_USER) in deploy-passwords.env (Zeile: set PW_$($AccountConfig.SFTP_USER)=...)"
        return $false
    }

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

    $migrationOk = $result

    # SICHERHEIT: Server IMMER hochfahren (egal was vorher passiert ist)
    Ensure-ServerUp -AccountConfig $AccountConfig

    # SSH-Info anzeigen
    Show-SSHInfo -AccountConfig $AccountConfig

    Write-Host ""
    Write-Host "  Website testen: $($AccountConfig.WEBSITE_URL)" -ForegroundColor $color
    Write-Host ""

    return $migrationOk
}

# ==============================================================================
#  SCRIPT START
# ==============================================================================

Clear-Host
Show-Header "LARAVEL DEPLOYMENT - IONOS / HOSTINGER"

Write-Host "  Lokaler Pfad:  $($GlobalConfig.LOCAL_PATH)" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Show-Warning "DRY-RUN MODUS - Keine Aenderungen werden durchgefuehrt!"
    Write-Host ""
}

if (-not (Test-Path $GlobalConfig.WINSCP_PATH)) {
    Show-Err "WinSCP nicht gefunden: $($GlobalConfig.WINSCP_PATH)"
    Write-Host ""
    Write-Host "Bitte WinSCP installieren: https://winscp.net/eng/download.php"
    Read-Host "Druecke Enter zum Beenden"
    exit 1
}

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

foreach ($id in 1, 2, 3) {
    if ($Account -eq $id -or $Account -eq 4) {
        $result = Deploy-ToAccount -AccountId $id -AccountConfig $Accounts[$id]
        if (-not $result) { $success = $false }
    }
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

foreach ($id in 1, 2, 3) {
    if ($Account -eq $id -or $Account -eq 4) {
        Write-Host "  [$id] $($Accounts[$id].WEBSITE_URL)" -ForegroundColor $Accounts[$id].Color
    }
}

Write-Host ""
Write-Host "  Dauer: $($duration.Minutes) Min $($duration.Seconds) Sek" -ForegroundColor Gray
Write-Host ""
Read-Host "Druecke Enter zum Beenden"