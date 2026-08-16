<#
.SYNOPSIS
    SSH-Session zu Hostinger oeffnen
.EXAMPLE
    .\ssh.ps1
#>

param(
    [int]$Account = 0
)

$Accounts = @{
    1 = @{
        Name        = "Resch GmbH"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u192633638"
        SFTP_PORT   = 65002
        REMOTE_PATH = "/home/u192633638/domains/reschc.space/public_html"
        Color       = "Green"
    }
    2 = @{
        Name        = "Resch KG"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        REMOTE_PATH = "/home/u854179217/domains/christianresch.esy.es/public_html/martin"
        Color       = "Yellow"
    }
    3 = @{
        Name        = "Sandbox (Test)"
        SFTP_HOST   = "212.1.209.26"
        SFTP_USER   = "u854179217"
        SFTP_PORT   = 65002
        REMOTE_PATH = "/home/u854179217/domains/christianresch.esy.es/public_html/sandbox"
        Color       = "Cyan"
    }
}

Clear-Host
Write-Host ""
Write-Host ("=" * 50) -ForegroundColor Cyan
Write-Host "  SSH-SESSION - HOSTINGER" -ForegroundColor Cyan
Write-Host ("=" * 50) -ForegroundColor Cyan
Write-Host ""

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

Write-Host ""
Write-Host "  Verbinde mit $($Config.Name)..." -ForegroundColor $Config.Color
Write-Host "  Nach Login: cd $($Config.REMOTE_PATH)" -ForegroundColor Gray
Write-Host ""
Write-Host "  Nuetzliche Befehle:" -ForegroundColor White
Write-Host "    php artisan tinker" -ForegroundColor Gray
Write-Host "    php artisan migrate:status" -ForegroundColor Gray
Write-Host "    tail -f storage/logs/laravel.log" -ForegroundColor Gray
Write-Host "    php artisan up / down" -ForegroundColor Gray
Write-Host ""

# SSH starten - bleibt offen
$sshUser = $Config.SFTP_USER
$sshHost = $Config.SFTP_HOST
$sshPort = $Config.SFTP_PORT
$remotePath = $Config.REMOTE_PATH

ssh -p $sshPort -t "$sshUser@$sshHost" "cd $remotePath; exec bash"
