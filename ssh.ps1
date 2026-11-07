<#
.SYNOPSIS
    SSH-Session oeffnen (IONOS + Hostinger)
.EXAMPLE
    .\ssh.ps1
    .\ssh.ps1 -Account 1
#>

param(
    [int]$Account = 0
)

$Accounts = @{
    1 = @{
        Name        = "Resch GmbH (IONOS)"
        SFTP_HOST   = "access-5021443468.webspace-host.com"
        SFTP_USER   = "su935318"
        SFTP_PORT   = 22
        SHELL_PATH  = "~/uschiweb"
        PHP         = "php8.4"
        Color       = "Green"
    }
    2 = @{
        Name        = "Resch KG"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        SHELL_PATH  = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        PHP         = "php"
        Color       = "Yellow"
    }
    3 = @{
        Name        = "Sandbox (Test)"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        SHELL_PATH  = "/home/u854179217/domains/christianresch.esy.es/public_html/sandbox"
        PHP         = "php"
        Color       = "Cyan"
    }
}

Clear-Host
Write-Host ""
Write-Host ("=" * 50) -ForegroundColor Cyan
Write-Host "  SSH-SESSION - IONOS / HOSTINGER" -ForegroundColor Cyan
Write-Host ("=" * 50) -ForegroundColor Cyan
Write-Host ""

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
$php = $Config.PHP

Write-Host ""
Write-Host "  Verbinde mit $($Config.Name)..." -ForegroundColor $Config.Color
Write-Host "  Ordner: $($Config.SHELL_PATH)  (wird automatisch geoeffnet)" -ForegroundColor Gray
Write-Host ""
Write-Host "  Nuetzliche Befehle:" -ForegroundColor White
Write-Host "    $php artisan tinker" -ForegroundColor Gray
Write-Host "    $php artisan migrate:status" -ForegroundColor Gray
Write-Host "    tail -f storage/logs/laravel.log" -ForegroundColor Gray
Write-Host "    $php artisan up / down" -ForegroundColor Gray
if ($php -ne "php") {
    Write-Host ""
    Write-Host "  (In dieser Session ist 'php' ein Alias fuer $php)" -ForegroundColor DarkGray
}
Write-Host ""

# SSH starten - bleibt offen. 'php' wird als Alias auf die richtige Version gesetzt.
$sshTarget = "$($Config.SFTP_USER)@$($Config.SFTP_HOST)"
$remoteCmd = "cd $($Config.SHELL_PATH) && exec bash --rcfile <(cat ~/.bashrc 2>/dev/null; echo 'alias php=$php')"

ssh -p $Config.SFTP_PORT -t $sshTarget $remoteCmd